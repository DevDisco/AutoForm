<?php

/**
 * Reads config.json and assigns its properties to itself,
 * so a json key becomes a key of the config object
 */
class Config{

    public function __construct(){

        $configFile = file_get_contents("../config.json");
        $json = json_decode($configFile);

        foreach ($json as $key => $value) {
            
            $this->$key = $value;
        }
    }
}