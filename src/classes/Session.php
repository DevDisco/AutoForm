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

    public static function setCurrentTable(string $currentTable): void
    {

        $_SESSION['currentTable'] = $currentTable;
    }

    public static function getCurrentTable(): string
    {

        return $_SESSION['currentTable'];
    }


    public static function unsetCleanForm()
    {

        unset($_SESSION['cleanPost']);
    }
}
