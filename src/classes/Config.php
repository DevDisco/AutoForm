<?php

/**
 * Reads config.json and assigns its properties to itself,
 * so a json key becomes a key of the config object
 */
class Config
{

    private string $currentTable = "";
    private int|bool $currentId = false;

    public function __construct()
    {
        $currentTable = Request::getTable();
        $currentId = Request::getID();
        $configFile = file_get_contents("../config.json");
        $json = json_decode($configFile, false);

        //store the contents of the config file on this object
        foreach ($json as $key => $value) {

            $this->$key = $value;
        }

        //if no current table is selected, pick the first one from the list
        if (!$currentTable) {

            $firstKey = array_key_first((array)$this->TABLE);
            $this->currentTable = $firstKey;
        } else {

            $this->currentTable = $currentTable;
        }

        if ($currentId) {
            $this->currentId = $currentId;
        }
    }

    public function getCurrentTable(): string|bool
    {
        return $this->currentTable;
    }
    public function setCurrentTable(string $table): void
    {
        $this->currentTable = $table;
    }

    public function getCurrentId(): int|bool
    {
        return $this->currentId;
    }

    public function getEditorFields(): bool|array
    {
        $currentTable = $this->getCurrentTable();

        $fields = $this->EDITOR->SHOW_TABLE_FIELDS->$currentTable ?? [];

        return empty($fields) ? false : $fields;
    }
}
