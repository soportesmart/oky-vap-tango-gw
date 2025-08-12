<?php
namespace BusinessRules;
use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;
/**
 * Description of BusinessRules
 *
 * @author jperez
 */
class TangoServiceBR {
    
    public function __construct($dbquery, $logger, $curl, $tango_auth) {
        $this->dbquery = $dbquery;
	    $this->logger = $logger;
        $this->numverify = $numverify;
        $this->curl = $curl;
        $this->tango_auth = $tango_auth;
    }

    private function sendRequestTango(string $url, array $bodyData = []): array
    {
        // Validar campos mínimos
        foreach (['externalRefID','amount','utid','purchaseOrderNumber'] as $k) {
            if (!array_key_exists($k, $bodyData)) {
                return [
                    'success' => false,
                    'error'   => "Falta el campo requerido: {$k}",
                    'status'  => null,
                ];
            }
        }

        // Armar payload
        $data = [
            'externalRefID'       => $bodyData['externalRefID'],
            'amount'              => $bodyData['amount'],
            'utid'                => $bodyData['utid'],
            'sendEmail'           => false,
            'purchaseOrderNumber' => $bodyData['purchaseOrderNumber'],
            'accountIdentifier'   => $this->tango_auth->accountIdentifierTango,
            'customerIdentifier'  => $this->tango_auth->customerIdentifierTango,
        ];

        // Inicializar cURL
        $ch = curl_init($url);
        if ($ch === false) {
            return ['success' => false, 'error' => 'No se pudo inicializar cURL', 'status' => null];
        }

        $basicAuth  = base64_encode($this->tango_auth->userNameTango . ':' . $this->tango_auth->passwordTango);
        //$authHeader = 'Authorization: Basic ' . $basicAuth;
        $authHeader = 'Authorization: Basic T0tZQXBwLVRFU1Q6RVdXeCRDSEBNTFFWQm1heW1ATnlRbkZ5anVSSnljT2NvY0t6b3BJanZqUA==';
        //Basic T0tZQXBwLVRFU1Q6RVdXeCRDSEBNTFFWQm1heW1ATnlRbkZ5anVSSnljT2NvY0t6b3BJanZqUA==
        // Opciones
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                $authHeader,
            ],
            CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
            // Autenticación Basic (recomendado en lugar del header manual):
            //CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            //CURLOPT_USERPWD        => $this->tango_auth->userNameTango . ':' . $this->tango_auth->passwordTango,
        ]);

        // Ejecutar
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'error'   => $error,
                'status'  => $httpCode ?: 0,
            ];
        }

        curl_close($ch);

        $decoded = null;
        if ($response !== '' && $response !== null) {
            $decoded = json_decode($response, true);
        }

        return [
            'success'  => ($httpCode >= 200 && $httpCode < 300),
            'response' => $decoded ?? $response, // por si no es JSON
            'status'   => $httpCode,
        ];
    }

    
    public function createOrder($body) {
        $this->logger->info("TangoServiceBR.createOrder: Entering... ");
        $data = json_decode($body);

        if (!isset($data->utId) || !isset($data->amount) || !isset($data->source) || !isset($data->transactionId) || !isset($data->productId)) {
            $response = new \BusinessRules\GenericResponse();
            $response->operationResult = "error";
            $response->message = "missing parameters";
            return $response;
        }

        $response = new \BusinessRules\GenericResponse();

        $url = $this->tango_auth->apiUrl;
        $bodyParams = [
            'externalRefID' => $data->transactionId,
            'amount' => $data->amount,
            'utid' => $data->utId,
            'purchaseOrderNumber' => $data->transactionId
        ];

        // Insert transaction
        $id = $this->dbquery->insertTransaction([
            'id_transacciones' => $data->transactionId,
            'request' => $bodyParams
        ]);

        $startTime = microtime(true);
        $result = $this->sendRequestTango($url, $bodyParams);
        $endTime = microtime(true);
        $response_time = $endTime - $startTime;
        
        if ($id != 0) {
            $this->dbquery->updateTransaction(json_encode($result), $response_time, $id);
        }

        if (!isset($result['status'])) {
            $response->operationResult = "error";
            $response->message = "Invalid response from Tango.";
            return $response;
        }

        // Respuesta exitosa
        if ($result['status'] == 200 || $result['status'] == 201) {
            $dataResponse = $result['response'];
            $this->transformMapping ($dataResponse, $data->productId, $response_time, $data->transactionId);

            $response->operationResult = "success";
            $response->message = "Transaction approved.";
            $response->data = [
                'referenceOrderID' => $result['response']['referenceOrderID'],
                'amountCharged' => $result['response']['amountCharged']['value'],
                'externalRefID' => $result['response']['externalRefID']
            ];
            return $response;
        }else {
            // Respuesta de error de Tango
            $response->operationResult = "error";
            $response->message = $result['response']['message'] ?? 'Unknown error';
            $response->data = [
                'Code' => $result['status'] ?? null,
                'requestId' => $result['response']['requestId'] ?? null
            ];
            return $response;
        }

        // Cualquier otro caso no esperado
        $response->operationResult = "error";
        $response->message = "Unhandled response from Tango.";
        $response->data = $result;
        return $response;
    }

    private function transformMapping( array $dataResponse, $idGiftcard, $response_time, $purchaseId) {
        // 1) Tomar lista de credenciales de la respuesta
        $credentialList = $dataResponse['reward']['credentialList'] ?? [];
        if (!is_array($credentialList)) {
            $credentialList = [];
        }

        // 2) Obtener y normalizar el mapper desde BD
        $mapperRaw = $this->dbquery->findJSONByGiftcard($idGiftcard);
        $fulfilment_flag = $this->dbquery->findFulfilmentFlagByGiftcard($idGiftcard);

        // Acepta tanto array ya decodificado como string JSON
        if (is_string($mapperRaw)) {
            $decoded = json_decode($mapperRaw, true);
            $dataMapper = is_array($decoded) ? $decoded : [];
        } elseif (is_array($mapperRaw)) {
            $dataMapper = $mapperRaw;
        } else {
            $dataMapper = [];
        }

        $mapperData = isset($dataMapper['data']) && is_array($dataMapper['data'])
            ? $dataMapper['data']
            : [];

        // 3) Preconstruir mapa: credentialType => label
        $labelByType = [];
        foreach ($mapperData as $dmItem) {
            $ct = $dmItem['credentialType'] ?? null;
            $lbl = $dmItem['label'] ?? null;
            if ($ct !== null && $lbl !== null) {
                $labelByType[(string)$ct] = (string)$lbl;
            }
        }

        // 4) Filtrar y mapear
        $codeArray = [];
        foreach ($credentialList as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label          = $item['label'] ?? null;
            $value          = $item['value'] ?? null;
            $type           = $item['type'] ?? null;
            $credentialType = $item['credentialType'] ?? null;

            // Incluir solo si value no es null ni cadena vacía (preserva "0")
            if ($value === null || $value === '') {
                continue;
            }

            // Reetiquetar desde el mapper si aplica
            if ($credentialType !== null) {
                $label = $labelByType[(string)$credentialType] ?? $label;
            }

            $codeArray[] = [
                'label'          => $label,
                'value'          => $value,
                'type'           => $type,
                'credentialType' => $credentialType,
            ];
        }

        $transformedData = ['code' => array_values($codeArray)];

        // 5) Generar código (UUID v4) y preparar purchaseId
        $purchaseId = $purchaseId ?? ($dataResponse['idCompra'] ?? null);
        $code = self::uuidV4();

        // 6) Guardar en BD
        $this->dbquery->insertCodeGiftcard($purchaseId, $code, $fulfilment_flag, json_encode($transformedData));

        // 7) Retornar algo útil
       /*  return [
            'code'            => $code,
            'purchaseId'      => $purchaseId,
            'transformedData' => $transformedData,
        ]; */
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        // Ajustar bits de version (4) y variant (RFC 4122)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
}
