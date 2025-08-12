<?php
namespace BusinessRules;
use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;
/**
 * Description of BusinessRules
 *
 * @author hgmena
 */
class RiskServiceBR {
    
    public function __construct($dbquery, $logger, $numverify, $curl, $twilio_auth, $countries, $messaging, $ipqs) {
        $this->dbquery = $dbquery;
	$this->logger = $logger;
        $this->numverify = $numverify;
        $this->curl = $curl;
        $this->twilio_auth = $twilio_auth;
        $this->countries = $countries;
        $this->messaging = $messaging;
        $this->ipqs = $ipqs;
    }
    
    // Internal Aux Functions
    private function getCountryName($countryCode) {
        $url = $this->countries->apiUrl.$countryCode;
        $this->curl->getRequest($url);   
        $countryName = json_decode($this->curl->response());
        return $countryName->name;
    }
         
    private function getCountryPrefix($phoneNumber) {
        if(strlen($phoneNumber) == 11) {
            if(substr($phoneNumber, 0, 1) == "1") {
                $phone['code'] = substr($phoneNumber, 0, 1);
                $phone['number'] = substr($phoneNumber, 1, 10);
            } else if(substr($phoneNumber, 0, 2) == "50") {
                $phone['code'] = substr($phoneNumber, 0, 3);
                $phone['number'] = substr($phoneNumber, 3, 8);
            }
        } else {
            $phone['code'] = substr($phoneNumber, 0, 2);
            $phone['number'] = substr($phoneNumber, 2, strlen($phoneNumber)-2);
        }           
        return $phone['code'];
    }
    
    private function getNumverifyValidation($phoneNumber) {
        $url = $this->numverify->apiUrl."?access_key=".$this->numverify->accessKey."&number=".$phoneNumber;
        $method = "validate";
        $this->curl->getRequest($url);
        $this->dbquery->addRiskEvent($method, $this->curl->response());
        
        return $this->curl->response();
    }
    
    private function getPhoneVerification($phoneNumber) {
        $sid    = $this->twilio_auth->apiSid;
        $token  = $this->twilio_auth->apiSecret;
        $twilio = new Client($sid, $token);
        $firstDigit = substr($phoneNumber, 0, 1);
        
        if($firstDigit != "+") {
           $phoneNumber = "+".$phoneNumber;
        }
        
        if(substr($phoneNumber, 0, 5) == '+5021') {
            $specialPhoneNumber = substr($phoneNumber, 4, strlen($phoneNumber));
            $valid = $this->dbquery->findCarrierByPhoneNumber($specialPhoneNumber);
            $response = new \BusinessRules\ValidateNumberResponse();
            
            if($valid) {
                $response->valid = true;
                $response->message = 'Valid phone number';
                $response->number = $specialPhoneNumber;
                $response->local_format = $specialPhoneNumber;
                $response->international_format = $phoneNumber;
                $response->country_prefix = substr($phoneNumber, 0, 4);
                $response->country_code = substr($phoneNumber, 1, 3);
                $response->carrier = $valid->operator;
                $response->line_type = $valid->operator_type;
                $response->validation_provider = 'oky';

                $this->dbquery->addRiskEvent('oky','cat_notification_operator', $number, json_encode($response));
            } else {
                $response->valid = false;
                $response->message = 'Unknown numbering plan';
                $response->number = '';
                $response->local_format =  '';
                $response->international_format = '';
                $response->country_prefix = '';
                $response->country_code = $result->countryCode;
                $response->carrier = '';
                $response->line_type = '';
                $response->validation_provider = 'oky';

                $this->dbquery->addRiskEvent('oky','cat_notification_operator', $number, json_encode($response));
            }
          
        } else {
            $number = json_encode(['number'=>$phoneNumber]);
            $result = $twilio->lookups->v1->phoneNumbers($phoneNumber)->fetch(["type" => ["carrier"]]);
            $response = new \BusinessRules\ValidateNumberResponse();
            
            if($result->phoneNumber == null){
                $response->valid = false;
                $response->message = 'Phone number not found';
                $this->dbquery->addRiskEvent('twilio','validate/lookups', $number, json_encode($response));
            } else if($result->countryCode == 'CA') {
               $url = $this->ipqs->apiUrl.$this->ipqs->apiKey.'/'.$phoneNumber;
               $this->curl->getRequest($url);   
               $ipqs = json_decode($this->curl->response());
               
               $this->dbquery->addRiskEvent('ipqs','validate/phonevalidation', $number, json_encode($ipqs));
                if($ipqs->valid == true && $ipqs->fraud_score < 85 && $ipqs->recent_abuse == false && $ipqs->VOIP == false && $ipqs->risky == false) {
                    $response->valid = true;
                    $response->message = 'Valid phone number';
                    $response->number = $phoneNumber;
                    $response->local_format = $result->nationalFormat;
                    $response->international_format = $result->phoneNumber;
                    $response->country_prefix = $this->getCountryPrefix($phoneNumber);
                    $response->country_code = $result->countryCode;
                    //$response->country_name = $this->getCountryName($result->countryCode);
                    $response->carrier = $ipqs->carrier;
                    $response->line_type = 'mobile';
                    $response->validation_provider = 'ipqs';
                } else {
                    $response->valid = false;
                    $response->message = 'Invalid phone number';
                    $response->number = $phoneNumber;
                    $response->local_format = $result->nationalFormat;
                    $response->international_format = $result->phoneNumber;
                    $response->country_prefix = "+".$this->getCountryPrefix($phoneNumber);
                    $response->country_code = $result->countryCode;
                    //$response->country_name = $this->getCountryName($result->countryCode);
                    $response->carrier = $ipqs->carrier;
                    $response->line_type = $ipqs->line_type;
                    $response->validation_provider = 'ipqs';
                }
            } else if($result->carrier['type'] == 'landline' || $result->carrier['type'] == 'voip' || $result->carrier['type'] == null)  {
                 $response->valid = false;
                 $response->message = 'Invalid carrier type';
                 $this->dbquery->addRiskEvent('twilio','validate/lookups', $number, json_encode($response));
            } else {
                $blacklist = $this->dbquery->findBlacklistedCarrierByCarrierName($result->carrier['name']);
                if($blacklist) {
                    $response->valid = false;
                    $response->message = 'Blacklisted carrier';
                    $response->number = $phoneNumber;
                    $response->local_format = $result->nationalFormat;
                    $response->international_format = $result->phoneNumber;
                    $response->country_prefix = "+".$this->getCountryPrefix($phoneNumber);
                    $response->country_code = $result->countryCode;
                    //$response->country_name = $this->getCountryName($result->countryCode);
                    $response->carrier = $result->carrier['name'];
                    $response->line_type = $result->carrier['type'];
                    $response->validation_provider = 'twilio';

                    $this->dbquery->addRiskEvent('twilio','validate/lookups', $number, json_encode($response));
                } else {
                    if($result->countryCode != 'US') {
                        $response->valid = true;
                        $response->message = 'Valid phone number';
                        $response->number = $phoneNumber;
                        $response->local_format = $result->nationalFormat;
                        $response->international_format = $result->phoneNumber;
                        $response->country_prefix = "+".$this->getCountryPrefix($phoneNumber);
                        $response->country_code = $result->countryCode;
                        //$response->country_name = $this->getCountryName($result->countryCode);
                        $response->carrier = $result->carrier['name'];
                        $response->line_type = $result->carrier['type'];
                        $response->validation_provider = 'twilio';

                        $this->dbquery->addRiskEvent('twilio','validate/lookups', $number, json_encode($response));
                    } else {
                        $url = $this->ipqs->apiUrl.$this->ipqs->apiKey.'/'.$phoneNumber;
                        $this->curl->getRequest($url);   
                        $ipqs = json_decode($this->curl->response());
                        $this->dbquery->addRiskEvent('ipqs','validate/phonevalidation', $number, json_encode($ipqs));
                        if($ipqs->valid == true && $ipqs->fraud_score < 85 && $ipqs->recent_abuse == false && $ipqs->VOIP == false && $ipqs->risky == false) {
                            $response->valid = true;
                            $response->message = 'Valid phone number';
                            $response->number = $phoneNumber;
                            $response->local_format = $result->nationalFormat;
                            $response->international_format = $result->phoneNumber;
                            $response->country_prefix = "+".$this->getCountryPrefix($phoneNumber);
                            $response->country_code = $result->countryCode;
                            //$response->country_name = $this->getCountryName($result->countryCode);
                            $response->carrier = $ipqs->carrier;
                            $response->line_type = $result->carrier['type'];
                            $response->validation_provider = 'ipqs';
                        } else {
                            $response->valid = false;
                            $response->message = 'Invalid phone number';
                            $response->number = $phoneNumber;
                            $response->local_format = $result->nationalFormat;
                            $response->international_format = $result->phoneNumber;
                            $response->country_prefix = "+".$this->getCountryPrefix($phoneNumber);
                            $response->country_code = $result->countryCode;
                            //$response->country_name = $this->getCountryName($result->countryCode);
                            $response->carrier = $ipqs->carrier;
                            $response->line_type =$result->carrier['type'];
                            $response->validation_provider = 'ipqs';
                        }
                    }
                }
            }
        }
        
        return $response;
        
    }

    public function validateNumber($body){
        $this->logger->info("RiskServiceBR.checkStatus: Entering... ");
        $data = json_decode($body);
        $result = $this->getPhoneVerification($data->number);
        
        return $result;
    }
    
    public function verifyVelocityRules($body) {
         $this->logger->info("RiskServiceBR.verifyVelocityRules: Entering... ");
         
         
         
    }
    
    public function verifyAmountRules($body) {
         $this->logger->info("RiskServiceBR.verifyAmountRules: Entering... ");
         
         
         
    }
    
    public function verifyAccumulatedAmountRules($body) {
        $this->logger->info("RiskServiceBR.verifyAmountRules: Entering... ");
        $data = json_decode($body);
        $method = 'accumulated';
        
        if($data->serviceType == 'topup') {
            $type = 1;
        } else {
            $type = 2;
        }
        
        $customer = $this->dbquery->findCustomerByAccountId($data->accountId);
        $rules = $this->dbquery->findAccumulatedAmountRulesByAmount($data->serviceType);
        $retailer = $this->dbquery->findRetailerUserByUsernameAndBranch($data->username, $data->branchId);
        
        if($customer) {
            if($rules) {
                foreach ($rules as $values) {
                    $result = $this->dbquery->findTransactionAmountByDays($values->days, $type, $data->accountId);
                    if(($result->monto+$data->amount) >= $values->amount) {
                        if($values->block_flag == 1) {
                            $this->dbquery->updateBlockCustomerAccount($data->accountId);

                            $mailSubject = 'Alerta de Bloqueo de Cuenta - '.$retailer->empresa.' - '.$customer->codigo_tel.$customer->telefono;
                            $mailMessage = '<body><h4>Alerta de Bloqueo de Cuenta</h4><br>'.
                                           'Se ha bloqueado una cuenta al violar una regla de riesgo: <br>'.
                                           '<ul><li>Alerta Activada: '.$values->alert_name.'</li>'.
                                           '<ul><li>Sucursal: '.$retailer->sucursal.'</li>'.
                                           '<li>Tel&eacute;fono: '.$customer->codigo_tel.$customer->telefono.'</li>'.
                                           '<li>Fecha: '.date("Y-m-d h:m:s").'</li>'.
                                           '<li>Monto de Recarga: '.$data->amount.'</li>'.
                                           '<li>Monto Acumulado: '.$result->monto.'</li>'.
                                           '<li>Usuario: '.$data->username.'</li></ul><br>'.
                                           '<p style="font-size:10px;">AVISO: Si no reconoces esta transacci&oacute;n, comun&iacute;cate por tel&eacute;fono o Whatsapp al +13054795530 o escr&iacute;benos a soporte@okyapp.com</p></body>';

                            $this->messaging->sendMail($values->alert_email, $values->alert_email, $mailSubject, $mailMessage);

                            $response = new \BusinessRules\ScoringResponse();
                            $response->risk = $values->risk;
                            $response->score = $values->score;
                            $response->operationResult = 'Ok';
                            $response->message ='The account was blocked by the system for violating a rule';

                            $this->dbquery->addTransactionAlert($values, $data, $customer, $values, $retailer, $result, 'Yes', 'Yes');
                            $this->dbquery->addRiskEvent($method, $body, json_encode($response));

                            return $response;
                        } else if($values->email_flag == 1) {

                            $mailSubject = 'Alerta de L=?UTF-8?Q?=C3=AD?=mite de Consumo de Cuenta - '.$retailer->empresa.' - '.$customer->codigo_tel.$customer->telefono;
                            $mailMessage = '<body><h4>Alerta de L&iacute;mite de Consumo de Cuenta</h4><br>'.
                                           'Se ha activado una alerta por llegar al l&iacute;mite de consumo de cuenta con la siguiente informaci&oacute;n: <br>'.
                                           '<ul><li>Alerta Activada: '.$values->alert_name.'</li>'.
                                           '<li>Sucursal: '.$retailer->sucursal.'</li>'.
                                           '<li>Tel&eacute;fono: '.$customer->codigo_tel.$customer->telefono.'</li>'.
                                           '<li>Fecha: '.date("Y-m-d h:m:s").'</li>'.
                                           '<li>Monto de Recarga: '.$data->amount.'</li>'.
                                           '<li>Monto Acumulado: '.$result->monto.'</li>'.
                                           '<li>Usuario: '.$data->username.'</li></ul><br>'.
                                           '<p style="font-size:10px;">AVISO: Si no reconoces esta transacci&oacute;n, comun&iacute;cate por tel&eacute;fono o Whatsapp al +13054795530 o escr&iacute;benos a soporte@okyapp.com</p></body>';

                            $this->messaging->sendMail($values->alert_email, $values->alert_email, $mailSubject, $mailMessage);

                            $response = new \BusinessRules\ScoringResponse();
                            $response->risk = $values->risk;
                            $response->score = $values->score;
                            $response->operationResult = 'Ok';
                            $response->message ='An alert was generated by the system'; 

                            $this->dbquery->addTransactionAlert($values, $data, $customer, $values, $retailer, $result, 'Yes', 'No');
                            $this->dbquery->addRiskEvent($method, $body, json_encode($response));

                            return $response;
                        } else {
                            $response = new \BusinessRules\ScoringResponse();
                            $response->risk = $values->risk;
                            $response->score = $values->score;
                            $response->operationResult = 'Ok';
                            $response->message ='An alert was generated by the system'; 

                            $this->dbquery->addTransactionAlert($values, $data, $customer, $values, $retailer, $result, 'No', 'No');
                            $this->dbquery->addRiskEvent($method, $body, json_encode($response));

                            return $response;
                        }
                    } 
                }
            } else {
                $response = new \BusinessRules\ScoringResponse();
                $response->risk = 'No Risk';
                $response->score = '0';
                $response->operationResult = 'Ok';
                $response->message ='Success'; 

                $this->dbquery->addRiskEvent($method, $body, json_encode($response));

                return $response;
            }
        } else {
            $response = new \BusinessRules\ScoringResponse();
            $response->risk = 'Block';
            $response->score = '100';
            $response->operationResult = 'Ok';
            $response->message ='Success'; 

            $this->dbquery->addRiskEvent($method, $body, json_encode($response));

            return $response;
        }
    }
    
}
