<?php
namespace Util;

class CurlClient {

    private $ch;
    private $response;
    private $httpStatusCode;
	
    public function __construct($logger) {
        $this->logger = $logger;
    }
        
    private function init() {
        $this->ch = curl_init();      
        curl_setopt($this->ch, CURLOPT_VERBOSE, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    }
	
    public function getRequest($url) {
        $this->logger->info("CurlClient.getRequest.url: ".$url);
        $this->init();
	$headers = array('Content-Type: application/json; charset=UTF-8');
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($this->ch, CURLOPT_URL, $url);
	$this->exec();
	}

    public function postRequest($url, $data, $headers) {
        $this->logger->info("CurlClient.postRequest.url: ".$url);
        $this->init();
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        $this->exec();
    }
    
    public function postRequestUserPass($url, $data, $headers, $user, $password) {
        $this->logger->info("CurlClient.postRequest.url: ".$url);
        $this->init();
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->ch, CURLOPT_USERPWD, $user.':'.$password);
        $this->exec();
    }

    public function putRequest($url, $data) {
        $this->logger->info("CurlClient.putRequest.url: ".$url);
	$this->init();
	$headers = array('Content-Type: application/json; charset=UTF-8', 'Content-Length: '.strlen($data));
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");	
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        $this->exec();
    }	
	
    public function exec() {
        $this->response = curl_exec($this->ch);
	$this->httpStatusCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);     
	curl_close($this->ch);
    }
	
    public function execClose() {
	curl_close($this->ch);
    }

    public function response() {
        return $this->response;
    }
	
    public function getHttpStatusCode() {
	return $this->httpStatusCode;
    }
	
    public function getCh() {
	return $this->ch;
    }
}

