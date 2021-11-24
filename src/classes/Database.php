<?php

/**
 * Creates a connection to the database when instanciated.
 * Use run() to select one or more records from a table.
 */
class Database
{

    //do this with constructor property propagation in php 8.1
    public PDO $pdo;

    // This api is so small that I am 100% sure this is only called once
    // In a larger project I need to check if a PDO connection has been made already
    public function __construct(public Config $config, public SimpleError $error)
    {
        $dsn = $config->DB_DSN ?? '';
        $user = $config->DB_USER ?? '';
        $pwd = $config->DB_PWD ?? '';

        $this->pdo = new PDO($dsn, $user, $pwd);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function runSQL( string $sql, array|bool $params=false ):array{

        if ($params === FALSE) {

            $stmt = $this->pdo->query($sql);
            
        } else {

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$params]);
        } 
        
        if ($stmt === FALSE ){
            return $this->pdo->errorInfo();
        }
        else{
            return $stmt->fetchAll(PDO::FETCH_ASSOC); 
        }
              
    }
    
    public function showTable( string $table ):array|bool{

        return $this->runSQL("SHOW FULL COLUMNS FROM ".$this->config->TABLE);
    }
    
    public function insertAutoForm( array $cleanPost ):array{

        
        $table = $this->config->TABLE;
    
        $keys = array_keys($cleanPost);
        $columnNames = implode(",", $keys);
        $namedParams = implode(",", array_map(fn ($attr) => ":$attr", $keys));
        
        $sql = "INSERT INTO $table ($columnNames) VALUES ($namedParams)";
        
        Logger::toLog($sql);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($cleanPost);
        
        //return $this->runSQL( $sql, $cleanPost );
        return [];
    }
}
