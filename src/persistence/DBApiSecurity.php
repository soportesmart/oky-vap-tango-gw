<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Persistence;

/**
 * Description of DBApiSecurity
 *
 * @author hgmena
 */
class DBApiSecurity extends DBAccess{
    
    public function __construct($pdo, $logger) {
        $this->pdo = $pdo;
	$this->logger = $logger;
    }
    
    public function findToken($token) 
        {
        $stmt = $this->pdo->prepare('SELECT id_empresa
                                     FROM tb_empresa
                                     WHERE token_api = :token');
        $stmt->bindParam("token", $token);
        return $this->getResult($stmt);
	}
}
