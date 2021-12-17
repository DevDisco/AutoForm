<?php

class Navigation{
    
    //todo: I converted the config readout from arrays to objects; deal with this in a better way

    public function __construct( private Config $config, private bool $hasTables=false ){

        $this->table = $config->TABLE;
        
        if ( is_object($this->table) && count((array)$this->table)> 1){
            
            $this->hasTables = true;
        }
    }

    public function getListOfTables() : array{

        $table = $this->config->TABLE;

        if ($this->hasTables){
            
            return (array)$table;
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