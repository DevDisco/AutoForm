<?php

class Navigation{

    public function __construct( private Config $config, private bool $hasTables=FALSE ){

        $this->table = $config->TABLE;
        
        if ( is_array($this->table) && count($this->table)> 1){
            
            $this->hasTables = TRUE;
        }
    }

    public function getListOfTables() : array{

        $table = $this->config->TABLE;

        if ($this->hasTables){
            
            return $table;
        }
        else{
            
            return [];
        }
    }
    
    public function getCurrentTable(){
    
        return $this->config->getCurrentTable();
    }

    public function getCurrentTableLabel()
    {

        return $this->table[$this->config->getCurrentTable()];
    }
}