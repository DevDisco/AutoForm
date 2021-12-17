<?php

use function PHPSTORM_META\type;

class Editor{
    
    private Config $config;

    public function __construct( private Database $database ){
        
        $this->config = $database->config;
    }

    public function showAll(): bool|string{

        $tableData = $this->database->selectEditRows();
        
        if (!$tableData){
            
            return false;
        }
        
        $return = "<table class='table table-light table-striped'>\n<thead>\n";
        
        $return .= $this->createShowAllHeader($tableData);
        
        $return .= "</thead>\n<tbody>\n";
        
        foreach($tableData as $key => $row ){
            
            $return .= $this->createShowAllRow($row);
        }

        $return .= "</tbody>\n</table>\n";
        
        return $return;
    }

    private function createShowAllHeader($tableData)
    {
        //I just need the first row to get the keys for the table headers
        $key = array_key_first($tableData);
        $row = $tableData[$key];
        $return = "\t<tr><th>&nbsp;</th>";
        foreach ($row as $field => $value) {

            $return .= "<th scope='col'>" . $field . "</th>";
        }
        $return .= "<th>&nbsp;</th></tr>\n";

        return $return;
    }
    
    private function createShowAllRow( $row ):string{

        $return = "\t<tr>". $this->createShowAllCheckbox($row['id']);
        foreach ($row as $field => $value) {

            $return .= "<td title=\"$field\">".$value. "</td>";
        }
        $return .= $this->createShowAllButtons($row['id']) . "</tr>\n";

        return $return;
    }

    private function createShowAllCheckbox(string $id): string
    {

        $return = "<td><input type='checkbox' value='1' name='checked[$id]' ></td>";

        return $return;
    }
    
    private function createShowAllButtons( string $id ):string{
    
        $t = $this->config->getCurrentTable();
        
        $return = "<td><a href='index.php?t=$t&id=$id'>Aanpassen</a></td>";
        
        return $return;
    }
    
    public function setPrefill( ):bool{

        $t = $this->config->getCurrentTable();
        $id = $this->config->getCurrentId();
        
        if ( $id === false ){
            
            return false;
        }
        
        $record = $this->database->selectRecord($t, $id);
        
        if ($record === false ){

            return false;
        }
        
        Session::setPrefill($record[0]);
        Logger::toLog(Session::getPrefill(), "setPrefill");
        return true;
    }
}