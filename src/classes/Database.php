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
    // In a larger project I need to check if a PDO connection has been made alfetchy
    public function __construct(public Config $config, public SimpleError $error)
    {
        $dsn = $config->DB_DSN ?? '';
        $user = $config->DB_USER ?? '';
        $pwd = $config->DB_PWD ?? '';

        try {
            $this->pdo = new PDO($dsn, $user, $pwd);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
        } catch (PDOException $th) {
            Logger::toLog($th->getMessage());
            $error->setError("Database: connection failed.");
            $error->showAndAbort();
        }
    }

    private function fetch(string $sql, array $params = [], int $fetchMode = PDO::FETCH_ASSOC): bool|array
    {

        $stmt = $this->prepareExecute($sql, $params);

        try {
            return $stmt->fetchAll($fetchMode);
        } catch (\PDOException $th) {
            Logger::toLog($th->getMessage());
            $this->error->setError("Database: fetch query failed.", $th->getCode());
            $this->error->showAndAbort();
        }
    }

    private function run(string $sql, array $params = []): bool
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
            Logger::toLog($sql, "query");
            $this->error->setError("Database: prepare query failed.", $th->getCode());
            $this->error->showAndAbort();
        }

        try {
            $stmt->execute($params);
        } catch (\PDOException $th) {
            Logger::toLog($th->getMessage());
            Logger::toLog($sql, "query");
            $this->error->setError("Database: execute query failed.", $th->getCode());
            $this->error->showAndAbort();
        }

        return $stmt;
    }

    public function showTable(): array|bool
    {
        return $this->fetch("SHOW FULL COLUMNS FROM " . $this->config->getCurrentTable());
    }

    public function selectEditRows(): array|bool
    {
        $fields = $this->config->getEditorFields();

        if (!$fields) {

            return false;
        }

        $fields = "id," . implode(",", $fields);

        return $this->fetch("SELECT $fields FROM " . $this->config->getCurrentTable());
    }

    public function insertFormData(Request $request): bool
    {
        $cleanPost =  $request->getCleanPost();
        $fieldList = $request->fields->get();
        $table = Session::getCurrentTable();

        //I could integrate this with getCleanPost()
        $cleanPost = FileUploads::processFiles($request, $cleanPost, $fieldList);

        $keys = array_keys($cleanPost);
        $columnNames = implode(",", $keys);
        $namedParams = implode(",", array_map(fn ($attr) => ":$attr", $keys));

        $sql = "INSERT INTO $table ($columnNames) VALUES ($namedParams)";

        return $this->run($sql, $cleanPost);
    }

    public function updateFormData(Request $request): bool
    {
        $cleanPost =  $request->getCleanPost();
        $fieldList = $request->fields->get();
        $table = Session::getCurrentTable();
        $id = Session::getCurrentId();

        //I could integrate this with getCleanPost()
        $cleanPost = FileUploads::processFiles($request, $cleanPost, $fieldList);
        
        $params = array_merge($cleanPost, ["id" => $id]);
        $keys = array_keys($cleanPost);
        $namedParams = implode(",", array_map(fn ($attr) => "$attr=:$attr", $keys));

        $sql = "UPDATE $table SET $namedParams WHERE id=:id";

        return $this->run($sql, $params);
    }

    public function getOptionsFromDb(array $field): array|bool
    {

        $table = $field['options']['table'];
        $name = $field['options']['nameColumn'];
        $value = $field['options']['valueColumn'] ?? false;

        if ($value) {

            $sql = "SELECT $value, $name FROM $table LIMIT 100";
            $options = $this->fetch($sql, [], PDO::FETCH_KEY_PAIR);
        } else {

            $sql = "SELECT $name FROM $table LIMIT 100";
            $options = $this->fetch($sql, [], PDO::FETCH_COLUMN);
        }

        if (is_array($options)) {

            return $options;
        } else {
            return false;
        }
    }
    
    public function selectRecord(string $table, int $id): bool|array
    {
        $sql = "SELECT * FROM $table WHERE id=:id";
        $params = [":id" => $id];
        return $this->fetch($sql, $params);
    }

    public function deleteRecord(Request $request): array|bool
    {

        $table = $request->getCleanGet()['t'] ?? false;
        $id = $request->getCleanGet()['id'] ?? false;
        $delCheck = $request->getCleanGet()['d'] ?? false;

        if (!$delCheck || empty($delCheck || !$table || !$id)) {

            $this->error->setError("Database: unexpected or invalid input.", 500);
            Logger::toLog($request->getCleanGet(), "getCleanGet");
            return false;
        }

        $key = array_key_first($delCheck);
        $params = [":id" => $id, ":$key" => $delCheck[$key]];

        //get record for deleting files later on
        $sql = "SELECT * FROM $table WHERE id=:id AND $key=:$key LIMIT 1";
        $record = $this->fetch($sql, $params)[0];

        //remove record
        $sql = "DELETE FROM $table WHERE id=:id AND $key=:$key LIMIT 1";
        $result = $this->run($sql, $params);

        //remove files only if the record has been successfully removed
        if ($result) {

            FileUploads::deleteLinkedFiles($request, $record);
            return true;
        }

        return false;
    }


}
