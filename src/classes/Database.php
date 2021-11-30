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

        try {
            $this->pdo = new PDO($dsn, $user, $pwd);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $th) {
            Logger::toLog($th->getMessage());
            $error->setError("Database: connection failed.");
            $error->showAndAbort();
        }
    }
    
    public function read(string $sql, array $params = [], int $fetchMode = PDO::FETCH_ASSOC ): bool|array{
    
        $stmt = $this->prepareExecute($sql, $params );
         
        try {
            return $stmt->fetchAll($fetchMode); 
        } catch (\PDOException $th) {
            Logger::toLog($th->getMessage());
            $this->error->setError("Database: fetch query failed.", $th->getCode());
            $this->error->showAndAbort();
        }        
    }


    public function write(string $sql, array $params = []): bool
    {
        try {
            $this->prepareExecute($sql, $params);
        } catch (\PDOException $th) {
            Logger::toLog($th->getMessage());
            $this->error->setError("Database: fetch query failed.", $th->getCode());
            $this->error->showAndAbort();
        } 
        return true; 
    }


    private function prepareExecute(string $sql, array $params = []): PDOStatement|bool
    {

        try {
            $stmt = $this->pdo->prepare($sql);
        } catch (\PDOException $th) {
            Logger::toLog($th->getMessage());
            $this->error->setError("Database: prepare query failed.", $th->getCode() );
            $this->error->showAndAbort();
        }

        try {
            $stmt->execute($params);
        } catch (\PDOException $th) {
            Logger::toLog($th->getMessage());
            $this->error->setError("Database: execute query failed.", $th->getCode());
            $this->error->showAndAbort();
        }
        
        return $stmt;
    }

    public function showTable(): array|bool
    {
        return $this->read("SHOW FULL COLUMNS FROM " . $this->config->getCurrentTable());
    }

    public function insertFormData( Request $request ): bool
    {

        $cleanPost =  $request->getCleanPost();
        $fieldList = $request->fields->get();
        $table = $this->config->getCurrentTable();

        foreach ($fieldList as $field) {

            if ($field['type'] === "file") {

                //images and other files will be stored inside db
                $image_base64 = base64_encode(file_get_contents($_FILES[$field['name']]['tmp_name']));
                $image = "data:" . $_FILES[$field['name']]['type'] . ";base64," . $image_base64;
                $cleanPost[$field['name']] = $image;
            }
        }

        $keys = array_keys($cleanPost);
        $columnNames = implode(",", $keys);
        $namedParams = implode(",", array_map(fn ($attr) => ":$attr", $keys));

        $sql = "INSERT INTO $table ($columnNames) VALUES ($namedParams)";
        
        return $this->write($sql, $cleanPost);
        
        //$stmt = $this->pdo->prepare($sql);
        //return $stmt->execute($cleanPost);
    }

    public function getOptionsFromDb(array $field): array|bool
    {

        $table = $field['options']['table'];
        $name = $field['options']['nameColumn'];
        $value = $field['options']['valueColumn'] ?? false;

        if ($value) {

            $sql = "SELECT $value, $name FROM $table LIMIT 100";
            $options = $this->read($sql, [], PDO::FETCH_KEY_PAIR);
        } else {

            $sql = "SELECT $name FROM $table LIMIT 100";
            $options = $this->read($sql, [], PDO::FETCH_COLUMN);
        }

        if (is_array($options)) {

            return $options;
        } else {
            return false;
        }
    }


    public function getImage(int $id = 3): string
    {
        $sql = "SELECT image_file FROM files WHERE id=3";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
