<?php

class Fields
{
    private array $fieldList = [];
    private array $ignoredList = [];
    private array $tableView = [];

    public function __construct(public Database $database) {

        //show form
        $table = $this->database->config->getCurrentTable();
        $this->tableView = $database->showTable($table);

        //Logger::toLog($this->table, "table");

        $this->create();
    }

    public function get()
    {

        return $this->fieldList;
    }

    private function create(): void
    {
        $fl = [];

        foreach ($this->tableView as $key => $field) {

            //id and auto-insert datefields can be ignored
            if ( $this->canIgnoreField($field) ){
                continue;
            }
            
            extract($field);

            //convert sql field info to an array that will be used to 
            //create and validate the form
            $fl[$key]['name'] = $Field;
            $fl[$key]['value'] = $Default ?? false;

            //default component is a standard text input with html5 types based
            //on sql type  
            $fl[$key]['component'] = "input_default";

            //the type field contains information about type, length and sign
            $parsedType = $this->parseSqlType($Type);
            $fl[$key]['type'] = $this->getInputType($parsedType);

            //correct input and type for longer texts
            if ($fl[$key]['type'] === 'text' && $parsedType[1] > 64) {

                $fl[$key]['component'] = "textarea";
            }

            //set maxima related to type
            if ($fl[$key]['type'] === 'text') {
                $fl[$key]['maxlength'] =  $parsedType[1];
            } else if ($fl[$key]['type'] === 'number') {
                $fl[$key]['max'] = $this->getAttributeMaxValue($parsedType);
            } else if ($fl[$key]['type'] === 'file') {
                $fl[$key]['maxfilesize'] = $this->getMaxFileSize($Type);
                $fl[$key]['component'] = "input_file";
            }

            //get required
            $fl[$key]['required'] = ($Null === "NO") ? true : false;

            //override or expand with custom field settings
            $table = $this->database->config->getCurrentTable();
            $optionsFile = "../src/customfields/$table/$Field.json";

            if (is_file($optionsFile)) {

                //array_merge will overwrite keys in $fl[$key] with values in $overrideArray
                $override = json_decode(file_get_contents($optionsFile), true) ?? [];
                $fl[$key] = array_merge($fl[$key], $override);
            }
        }

        $this->fieldList = $fl;
    }
    
    private function canIgnoreField( array $field ):bool{

        //ignore autoincrementing primary keys 
        if ($field['Field'] === "id" || ($field['Key'] === "PRI" && strpos($field['Extra'], "auto_increment") !== false)) {

            $this->ignoredList[] = $field['Field'];
            return true;
        }

        //ignore date fields with default set to current_timestamp()
        if ($field['Default'] === "current_timestamp()") {

            $this->ignoredList[] = $field['Field'];
            return true;
        }  
        
        return false;
    }

    private function getAttributeMaxValue(array $typeArray): int|bool
    {
        $isSigned = $typeArray[2];
        $max = false;

        $power = match ($typeArray[0]) {

            "tinyint" => 8,
            "smallint" => 16,
            "mediumint" => 24,
            "mediumint" => 32,
            "bigint" => 64,
            default => false,
        };

        if ($power) {

            if ($isSigned) {

                $max = pow(2, ($power - 1)) - 1;
            } else {

                $max = pow(2, $power) - 1;
            }
        } else {

            //not a number, abort
            return $max;
        }

        //correct for field length if that's set to a smaller value than default
        //there is probably a fancy math way of doing this...:(
        $maxByLength = intval(str_repeat("9", $typeArray[1]));

        $max = ($maxByLength < $max) ? $maxByLength : $max;

        return $max;
    }

    private function getInputType(array $typeArray): string|bool
    {
        if ($typeArray === false) {

            return false;
        }

        $type = match ($typeArray[0]) {

            "int" => "number",
            "tinyint" => "number",
            "smallint" => "number",
            "mediumint" => "number",
            "date" => "number",
            "datetime" => "datetime-local",
            "file" => "file",
            default => "text",
        };

        return $type;
    }
    
    private function getMaxFileSize(string $type): int
    {
        $maxPostSize = Core::getSizeInBytes(ini_get('post_max_size'));
        $maxUploadSize = Core::getSizeInBytes(ini_get('upload_max_filesize'));
        $maxPhpSize = ($maxUploadSize > $maxPostSize) ? $maxPostSize : $maxUploadSize;

        $maxSqlSize = match ($type) {
            "blob" => 65535,
            "mediumblob" => 16777215,
            "longblob" => 4294967295,
            default => 4194304,  //4MB
        };

        //you can't upload anything larger than the max post size
        return ($maxSqlSize > $maxPhpSize) ? $maxPhpSize : $maxSqlSize;
    }

    private function parseSqlType($sqlType): array
    {
        //text and blob types have no length value
        $typeArray = match ($sqlType) {

            "tinytext" => ['text', 255, false],
            "text" => ['text', 65535, false],
            "datetime" => ['datetime', 20, false],
            "blob" => ['file', "", false],
            "mediumblob" => ['file', "", false],
            "longblob" => ['file', "", false],
            default => []
        };

        if (!empty($typeArray)) {

            return $typeArray;
        }

        if (strpos($sqlType, ") ")) {

            //bit hacky, but it works
            $sqlType = str_replace(") ", "(", $sqlType);
            $typeArray =  explode("(", $sqlType);
        } else if (strpos($sqlType, ")")) {

            $sqlType = trim($sqlType, ")");
            $typeArray =  explode("(", $sqlType);
            $typeArray[2] = "";
        } else {

            return ["", "", false];
        }

        //I just want to check if a field is signed or not
        $typeArray[2] = ($typeArray[2] === "unsigned") ? false : true;

        //todo: checks
        return $typeArray;
    }
}
