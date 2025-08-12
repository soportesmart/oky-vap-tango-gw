<?php

namespace Persistence;

/**
 * Description of DatabaseAccess
 * @author hgmena
 */
abstract class DBAccess {

    private $message;

    protected $mapper;
    protected $pdo;
    protected $logger;

    protected $id;

    protected function deleteById($id, $sql) 
        {
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam('id', $id);
        $rc = $this->execute($stmt);
        if($rc)
            {
            return new Response($id, "00", "ID : '$id' DELETE SUCCESSFUL", $stmt->rowCount());
            }
        else
            {
            return new Response("-1", "-1", $this->getMessage(), $stmt->rowCount());
            }
        }

    protected function execute(\PDOStatement $stmt) 
        {
        try 
            {
            return $stmt->execute();
            } 
        catch(\PDOException $e) 
            {
            $this->logger->debug("oky.persistence.execute.stmt : " . print_r($stmt->debugDumpParams(), true));
            $this->message = $e->getMessage();
            return false;
            }
        }

    protected  function findById($id, $sql) 
        {
        $this->logger->debug("oky.persistence.getBydId.id : " . $id);
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam('id', $id);
        return $stmt;
        }

    protected function findAll($sql) 
        {
        $stmt = $this->pdo->prepare($sql);
        return $stmt;
        }

    protected function getResult($stmt) 
        {
        $rc = $this->execute($stmt);
        if($rc) 
            {
            $result = $stmt->fetchObject();
            /*
            $result = array();
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC))
                {
                array_push($result, $row);
                }
            */	
            $this->logger->debug(print_r($result, true));
            return $result;
            } 
        else
            {
            return $this->getMessage();
            }
    }
    
    protected function getAll($stmt) 
        {
        $rc = $this->execute($stmt);
        if($rc) 
            {
            $result = array();
            while ($row = $stmt->fetch(\PDO::FETCH_OBJ))
                {
                array_push($result, $row);
                }
         
            $this->logger->debug(print_r($result, true));
            return $result;
            } 
        else
            {
            return $this->getMessage();
            }
    }
    
    protected function insert($stmt) 
        {
        $rc = $this->execute($stmt);
        if($rc)
            {
            return new Response($this->pdo->lastInsertId(), "00", "ID : '".$this->pdo->lastInsertId()."' INSERT SUCCESFUL", $stmt->rowCount());
            } 
        else
            {
            $this->logger->info("DBAccess.insert: ".print_r($this->getMessage(),true));
                
            return new Response("-1", "-1", $this->getMessage(), $stmt->rowCount());
            }
        }

    protected function update($stmt) 
        {
        $rc = $this->execute($stmt);
        if($rc)
            {
            return new Response($this->pdo->lastInsertId(), "00", "UPDATE SUCCESSFUL", $stmt->rowCount());
            }
        else
            {
            return new Response("-1", "-1", $this->getMessage(), $stmt->rowCount());
            }
        }

    /**
     * Getters and Setters Section
     */

    public function setId($id) {
        $this->id  = $id;
    }

    public function getId() {
        return $this->$id;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setLogger($logger) {
        $this->logger = $logger;
    }

    public function setPdo($pdo) {
        $this->pdo = $pdo;
    }
}

class Response {
	
    public $id;
    public $errorCode;
    public $message;
    public $rowCount;

    public function __construct($id, $errorCode, $message, $rowCount) {
            $this->id = $id;
            $this->errorCode = $errorCode;
            $this->message = $message;
            $this->rowCount = $rowCount;
    }
}