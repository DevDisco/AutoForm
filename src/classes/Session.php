<?php

class Session
{

    public function __construct()
    {

        session_start();
    }

    public static function setCleanPost(array $cleanPost): void
    {

        $_SESSION['cleanPost'] = $cleanPost;
    }

    public static function getCleanPost(): array
    {

        return $_SESSION['cleanPost'] ?? [];
    }

    public static function unsetCleanPost()
    {

        unset($_SESSION['cleanPost']);
    }

    //if prefill is false, copy cleanPost
    public static function setPrefill(array|bool $prefill=false): void
    {
        if (!$prefill ){
            $_SESSION['prefill'] = $_SESSION['cleanPost'];
        }
        else{ $_SESSION['prefill'] = $prefill;}
    }

    public static function getPrefill(): array
    {

        return $_SESSION['prefill'] ?? [];
    }
    

    public static function unsetPrefill()
    {

        unset($_SESSION['prefill']);
    }

    public static function setCurrentTable(string $currentTable): void
    {

        $_SESSION['currentTable'] = $currentTable;
    }

    public static function getCurrentTable(): string
    {

        return $_SESSION['currentTable'] ?? "";
    }

    public static function setCurrentId(int|bool $setCurrentId): void
    {

        $_SESSION['currentId'] = $setCurrentId;
    }

    public static function getCurrentId(): int|bool
    {

        return $_SESSION['currentId'];
    }

    
}

