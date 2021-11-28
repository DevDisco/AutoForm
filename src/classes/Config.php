<?php

/**
 * Reads config.json and assigns its properties to itself,
 * so a json key becomes a key of the config object
 */
class Config{

    public function __construct( private string $currentTable=""){

        $configFile = file_get_contents("../config.json");
        $json = json_decode($configFile, true);

        //store the contents of the config file on this object
        foreach ($json as $key => $value) {
            
            $this->$key = $value;
        }

        //if no current table is selected, pick the first one from the list
        if (!$currentTable) {
            
            $firstKey = array_key_first($this->TABLE);
            $this->currentTable = $firstKey;
        }
    }
    
    public function getCurrentTable( ):string{
    
        return $this->currentTable;
    }


    public function setCurrentTable( string $table ):void
    {

        $this->currentTable = $table;
    }    
}