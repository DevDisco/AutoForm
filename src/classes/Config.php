<?php

/**
 * Reads config.json and assigns its properties to itself,
 * ie: every json key becomes a key of the config object
 */
class Config
{

    private string $currentTable = "";
    private int|bool $currentId = false;

    public function __construct()
    {
       
        $this->readConfig();

        //override with prod settings
        if ($_SERVER['SERVER_NAME'] !== "localhost") {

            $this->readConfig(CONFIG_FILE_PROD);
        }
        
        $this->patchUploadFolder();
        $this->initCurrentId();   
        $this->initCurrentTable();
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

    /**
     * Returns editor settings for currently selected table
     */
    public function getEditorFields(): bool|array
    {
        $currentTable = $this->getCurrentTable();

        $fields = $this->EDITOR->SHOW_TABLE_FIELDS->$currentTable ?? [];

        return empty($fields) ? false : $fields;
    }
    
    /**
     * Reads json config file and stores its values on this object
     */
    private function readConfig( string $file = CONFIG_FILE_DEV ): void{

        $configFile = file_get_contents($file);

        $json = json_decode($configFile, false);

        foreach ($json as $key => $value) {

            $this->$key = $value;
        }        
    }

    /**
     * Adds / to the end of the path if needed
     */
    private function patchUploadFolder():void{
    
        $end = substr( $this->UPLOAD_FOLDER, -1 );
        
        if ( $end !== "\\" ){

            $this->UPLOAD_FOLDER = $this->UPLOAD_FOLDER."\\";
        }
    }
    
    /**
     * Stores currently selected table on this object
     */
    private function initCurrentTable():void{

        $currentTable = Request::getTable();
        
        //if no current table is selected, pick the first one from the list
        if (!$currentTable) {

            $firstKey = array_key_first((array)$this->TABLE);
            $this->currentTable = $firstKey;
        } else {

            $this->currentTable = $currentTable;
        }
    }

    /**
     * Stores current id on this object
     */
    private function initCurrentId(){

        $currentId = Request::getID();
        
        if ($currentId) {
            $this->currentId = $currentId;
        }           
    }
}
