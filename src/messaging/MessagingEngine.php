<?php

namespace Messaging;

/**
 * Description of MessagingBR
 *
 * @author hgmena
 */
class MessagingEngine {
    
    public function __construct($dbquery, $curl, $logger, $oky_comm) {
        $this->dbquery = $dbquery;
        $this->curl = $curl;
	$this->logger = $logger;
        $this->oky_comm = $oky_comm;
    }

    public function sendSMS($phoneNumber, $message, $type) {
        $apiToken = 'token: '.$this->oky_comm->apiToken;
        $apiServiceName = 'sms';  
        $body = (object) [
            'phoneNumber' => $phoneNumber,
            'message' => $message,
            'countryId' => $type
            ];
        
        $encodedBody = json_encode($body);
        $header = array('Content-Type: application/json; charset=UTF-8', 'Content-Length: '.strlen($encodedBody), $apiToken);
        $this->curl->postRequest($this->oky_comm->apiUrl.$apiServiceName, $encodedBody, $header);
        $response = $this->curl->response();
        return $response;
    }
    
    public function sendPush($phoneNumber, $title, $message) {
        $apiToken = 'token: '.$this->oky_comm->apiToken;
        $apiServiceName = 'push';  
        $body = (object) [
            'phoneNumber' => $phoneNumber,
            'title' => $title,
            'message' => $message
            ];
        
        $encodedBody = json_encode($body);
        $header = array('Content-Type: application/json; charset=UTF-8', 'Content-Length: '.strlen($encodedBody), $apiToken);
        $this->curl->postRequest($this->oky_comm->apiUrl.$apiServiceName, $encodedBody, $header);
        $response = $this->curl->response();
        return $response;
    }
    
    public function sendMail($email, $ccEmail, $subject, $message) {
        $apiToken = 'token: '.$this->oky_comm->apiToken;
        $apiServiceName = 'email';  
        
        $ccAddress = (object) [
              'ccEmail' => $ccEmail,
              'ccName' => $ccEmail
        ];
        
        $body = (object) [
            'senderEmail' => $this->oky_comm->senderMail,
            'senderName' => $this->oky_comm->senderName,
            'recipientEmail' => $email,
            'recipientName' => $email,
            'ccAddress' => [$ccAddress],
            'subject' => $subject,
            'message' => $message
        ];
        
        $encodedBody = json_encode($body);
        $apiHeader = array('Content-Type: application/json; charset=UTF-8', 'Content-Length: '.strlen($encodedBody), $apiToken);
        $this->curl->postRequest($this->oky_comm->apiUrl.$apiServiceName, $encodedBody, $apiHeader);
        $response = $this->curl->response();
        
        return $response;
    }

}
