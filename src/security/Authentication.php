<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Security;

/**
 * Description of Authentication
 *
 * @author hgmena
 */
class Authentication {
    
    public function __construct($dbapisecurity, $logger) {
        $this->dbapisecurity = $dbapisecurity;
	$this->logger = $logger;
    }
    
    public function __invoke($request, $response, $next)
        {
        $authResponse = new \BusinessRules\GenericResponse();
        
        if(empty($request->getHeader('token')[0]))
            {
            $authResponse->errorCode = '-1';
            $authResponse->message = 'ERROR: Authentication Data Unavailable';
            $authResponse->timestamp = time();
            $response->withHeader('Content-type', 'application/json');
            $response->getBody()->write(json_encode($authResponse));
            }
        else
            {
            $findTokenResult = $this->dbapisecurity->findToken($request->getHeader('token')[0]);
        
            if($findTokenResult)
                {
                $response = $next($request, $response);
                }
            else
                {
                $authResponse->errorCode = '-1';
                $authResponse->message = 'ERROR: Could not authenticate retailer';
                $authResponse->timestamp = time();
                $response->withHeader('Content-type', 'application/json');
                $response->getBody()->write(json_encode($authResponse));
                }
            }
        return $response;
        }
}
