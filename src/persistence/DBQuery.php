<?php

namespace Persistence;

/**
 * Description of DBQuerys
 * 
 * Class where all CRUD statements are located
 *
 * @author hgmena
 */
class DBQuery extends DBAccess {
    
    public function __construct($pdo, $logger) {
        $this->pdo = $pdo;
	$this->logger = $logger;
    }
    
    //CREATE
     public function insertTransaction(array $tx) {


        try {
            $stmt = $this->pdo->prepare('INSERT INTO log_tango SET
               id_transacciones =:id_transacciones,
               request =:request,
               insert_date = NOW();');
            $stmt->bindParam(':id_transacciones', $tx['id_transacciones']);
            $stmt->bindParam(':request', json_encode($tx['request']));

            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log('message : ->'. $e->getMessage() . '<-');
            return 0;
        }
     }

     public function insertCodeGiftcard($id_transacciones, $code, $fulfillment_flag, $response) {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO tb_redemption_code SET
               id_transacciones =:id_transacciones,
               internal_code =:internal_code,
               fulfillment_flag =:fulfillment_flag,
               redemption_code =:redemption_code,
               fecha_i = NOW();');
            $stmt->bindParam(':id_transacciones', $id_transacciones);
            $stmt->bindParam(':internal_code', $code);
            $stmt->bindParam(':fulfillment_flag', $fulfillment_flag);
            $stmt->bindParam(':redemption_code', json_encode($response));

            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log('message : ->'. $e->getMessage() . '<-');
            return 0;
        }
     }
            
    //READ
    public function findJSONByGiftcard($id_giftcard) {
        $stmt = $this->pdo->prepare('SELECT field_mapping
                                     FROM cat_esp_giftcard
                                     WHERE id_giftcard = :id_giftcard');
        $stmt->bindParam("id_giftcard", $id_giftcard);
        return $this->getResult($stmt)->field_mapping;
    }

    public function findFulfilmentFlagByGiftcard($id_giftcard) {
        $stmt = $this->pdo->prepare('SELECT fulfilment_flag
                                     FROM cat_esp_giftcard
                                     WHERE id_giftcard = :id_giftcard');
        $stmt->bindParam("id_giftcard", $id_giftcard);
        return $this->getResult($stmt)->field_mapping;
    }
    
    //UPDATE
    public function updateTransaction($response, $response_time, $id) {
        $stmt = $this->pdo->prepare('UPDATE log_tango 
                                     SET 
                                     response = :response,
                                     time_elapsed = :response_time
                                     WHERE id_log = :id');
        $stmt->bindParam("response", $response);
        $stmt->bindParam("response_time", $response_time);
        $stmt->bindParam("id", $id);
        return $this->update($stmt);
    }

    //$this->dbquery->updateCodeGiftcard($purchaseId, $code, $fulfilment_flag, json_encode($transformedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    public function updateCodeGiftcard($id_transacciones, $code, $response_time, $id, $response) {
        $stmt = $this->pdo->prepare('UPDATE tb_redemption_code 
                                     SET 
                                     response = :response,
                                     time_elapsed = :response_time
                                     WHERE id = :id');
        $stmt->bindParam("response", $response);
        $stmt->bindParam("response_time", $response_time);
        $stmt->bindParam("id", $id);
        return $this->update($stmt);
    }
    
    //DELETE
    public function deleteRetailerInvoice($invoiceId) {
        $stmt = $this->pdo->prepare('DELETE
                                     FROM tb_invoice 
                                     WHERE id_saldo_empresa = :invoiceId');
        $stmt->bindParam("inoviceId", $invoiceId);
        return $this->update($stmt);
    }
    
 
    
}

