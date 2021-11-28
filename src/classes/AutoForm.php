<?php

class AutoForm
{

    public function __construct(
        private Database $database
    ) {

        //show form
        $table = $this->database->config->getCurrentTable();
        $this->tableData = $database->showTable($table);

        //Logger::toLog($this->table, "table");

        $this->createFieldList();
    }

    public function getFieldList()
    {

        return $this->fieldList;
    }

    public function createForm(): string
    {
        $form = file_get_contents("../src/templates/components/form.php");
        $inputs = "";
        $enctype = "";

        foreach ($this->fieldList as $field) {

            $inputType = $field['component'];
            $input = file_get_contents("../src/templates/components/$inputType.php");

            if (strpos($input, "[[repeat]]") && !strpos($input, "<option")) {

                //this is for checkboxes and radio buttons
                $input = $this->createRepeatingInput($field, $input);
            } else {

                $input = $this->createSingleInput($field, $input);
            }

            if ($field['type'] === "file") {

                $enctype = "enctype='multipart/form-data'";
            }

            $inputs .= $input;
        }



        $table = $this->database->config->getCurrentTable();
        $form = str_replace("{{table}}", $table, $form);
        $form = str_replace("{{inputs}}", $inputs, $form);
        $form = str_replace("{{enctype}}", $enctype, $form);

        Session::setCurrentTable($table);
        //Session::unsetCleanForm();

        return $form;
    }

    private function parseAttributes(array $field, string $input, bool $setRequired = true): string
    {

        foreach ($field as $key => $value) {

            if (is_array($value)) {

                //ignore, these are option lists
            } else if ($key === "required") {

                if ($value && $setRequired) {

                    $input = str_replace("{{" . $key . "}}", $key, $input);
                }
            } else {

                $input = str_replace("{{" . $key . "}}", $value, $input);
            }
        }

        return $input;
    }

    private function cleanUp(string $input): string
    {
        //remove unused placeholders
        $input =  preg_replace("/\s?{{[^}]*}}/", "", $input);

        //remove unused max and maxvalue to comply with html5 standards
        $input = str_replace(" maxlength=\"\"", "", $input);
        $input = str_replace(" max=\"\"", "", $input);

        return $input;
    }

    private function getPresetValue(array $field): string
    {

        $value = $field['value'] ?? "";

        $prefill = Session::getCleanPost()[$field['name']] ?? false;

        //Logger::toLog(Session::getCleanPost(), "getCleanPost");

        if ($prefill) {

            $value = $prefill;
        }

        return $value;
    }

    private function isSelected(array $field, string $option): bool
    {
        $prefill = Session::getCleanPost()[$field['name']] ?? false;

        if (strpos($prefill, "|")) {

            $prefillArray = explode("|", $prefill);
            return in_array($option, $prefillArray) ?: false;
        } else if ($prefill == $option) {

            return true;
        }

        return false;
    }

    private function createSingleInput(array $field, string $input): string
    {
        $label = $this->cleanLabel($field['name'], $field);
        $input = $this->insertOptions($field, $input);
        $value = $this->getPresetValue($field);

        if ($field['required']) {

            $label .= " *";
        }

        $input = str_replace("{{label}}", $label, $input);
        $input = str_replace("{{instructions}}", $this->createInstructions($field), $input);
        $input = str_replace("{{id}}", $field['name'], $input);
        $input = str_replace("{{value}}", $value, $input);
        $input = $this->parseAttributes($field, $input);
        $input = $this->cleanUp($input);

        return $input . "\n";
    }

    private function createRepeatingInput(array $field, string $input): string
    {
        $item = "";
        $input = rtrim($input);
        $return = "";

        //parse input into fixed and repeated parts
        $parts = explode("[[repeat]]", $input);

        $label = $this->cleanLabel($field['name'], $field);
        $label = $field['required'] ? ($label .= " *") : $label;
        $instructions = $this->createInstructions($field);
        $label = $parts[0] = str_replace("{{label}}", $label, $parts[0]);

        foreach ($field['options'] as $key => $option) {

            $id = $field['name'] . "_" . $key;
            $label = $this->cleanLabel($option);
            $item = str_replace("{{id}}", $id, $parts[1]);
            $item = str_replace("{{label}}", $label, $item);
            $item = str_replace("{{value}}", $option, $item);
            $item = str_replace("{{instructions}}", $instructions, $item);

            //if ($field['value'] == $option) {
            if ($this->isSelected($field, $option)) {

                $item = str_replace("{{checked}}", "checked", $item);
            }

            //checkboxes don't go along with required
            $setRequired = ($key === 0 && $field['type'] === "radio");

            $item = $this->parseAttributes($field, $item, $setRequired);
            $item = $this->cleanUp($item);

            $return .= $item . "\n";
        }

        return $parts[0] . $return . $parts[2] . "\n";
    }

    private function createInstructions(array $field): string
    {
        extract($field);
        $instructions = "";
        $config = $this->database->config;

        if (is_array($options ?? false)) {

            //selects & checkboxes
            $instructions = $config->INSTRUCTIONS_CHOICE;
        } else if ($type === "number" && $max > 0) {

            $instructions = str_replace("{{?}}", $max, $config->INSTRUCTIONS_NUMBER);
        } else if ($type === "email") {

            $instructions = $config->INSTRUCTIONS_EMAIL;
        } else if ($type === "file") {

            //todo: accepted file extensions? Seems to be restricted by input already.
            $maxFileSize = Core::getBytesAsSize($maxfilesize);

            if ($component === "image") {
                
                $instructions = str_replace("{{?}}", $maxFileSize, $config->INSTRUCTIONS_IMAGE);
                $instructions = str_replace("{{1}}", $width, $instructions);
                $instructions = str_replace("{{2}}", $height, $instructions);
            } else {

                $instructions = str_replace("{{?}}", $maxFileSize, $config->INSTRUCTIONS_FILE);
            }
        } else if ($type === "url") {

            $instructions = $config->INSTRUCTIONS_URL;
        } else if ($type === "password") {

            $instructions = $config->INSTRUCTIONS_PASSWORD;
        } else if ($type === "text" && $maxlength > 0) {
            $instructions =
                str_replace("{{?}}", $maxlength, $config->INSTRUCTIONS_TEXT);
        }

        return $instructions;
    }

    private function cleanLabel(string $fieldName, array $field = []): string
    {
        $label = $field['label'] ?? false;

        if (!$label) {

            $label = ucwords(str_replace("_", " ", $fieldName));
        }

        return $label;
    }

    private function insertOptions(array|bool $field, string $input): string
    {
        $parts = explode("[[repeat]]", $input);
        $insert = "";
        $options = [];

        //return input unaltered if the option option (duh) isn't set
        if (empty($field['options'])) {

            return $input;
        }

        if (isset($field['options']['table']) && isset($field['options']['nameColumn'])) {

            $options = $this->database->getOptionsFromDb($field);
        } else {

            $options = $field['options'];
        }

        //Logger::toLog($options, "options");

        foreach ($options as $key => $value) {

            $label = $value;
            $valueColumn = $field['options']['valueColumn'] ?? false;
            $value = $valueColumn ? $key : $value;
            $part = rtrim($parts[1]);

            $part = str_replace("{{value}}", $value, $part);
            $part = str_replace("{{label}}", $label, $part);

            if ($this->isSelected($field, $value)) {

                $part = str_replace("{{selected}}", "selected", $part);
            }

            $insert .= $part;
        }

        return $parts[0] . $insert . $parts[2];
    }

    private function createFieldList(): void
    {
        $fl = [];

        foreach ($this->tableData as $key => $field) {

            //ignore autoincrementing primary keys 
            if ($field['Field'] === "id" || ($field['Key'] === "PRI" && strpos($field['Extra'], "auto_increment") !== false)) {

                continue;
            }

            //ignore date fields with default set to current_timestamp()
            if (($field['Type'] === "datetime" && $field['Default'] === "current_timestamp()")) {

                continue;
            }

            //convert sql field info to an array that will be used to 
            //create and validate the form
            $fl[$key]['name'] = $field['Field'];
            $fl[$key]['value'] = $field['Default'] ?? false;

            //the type field contains information about type, length and sign
            $typeArray = $this->parseSqlType($field['Type']);

            //default is a standard text input with html5 types based on sql field type
            $fl[$key]['component'] = "default";
            $fl[$key]['type'] = $this->getInputType($typeArray);

            //correct input and type for longer texts
            if ($fl[$key]['type'] === 'text' && $typeArray[1] > 64) {

                $fl[$key]['component'] = "textarea";
            }

            //set maxima related to type
            if ($fl[$key]['type'] === 'text') {
                $fl[$key]['maxlength'] = $typeArray[1];
            } else if ($fl[$key]['type'] === 'number') {
                $fl[$key]['max'] = $this->getAttributeMaxValue($typeArray);
            } else if ($fl[$key]['type'] === 'file') {
                $fl[$key]['maxfilesize'] = $this->getMaxFileSize($field['Type']);
                $fl[$key]['component'] = "file";
            }

            //get required
            $fl[$key]['required'] = ($field['Null'] === "NO") ? true : false;

            //override or expand with custom field settings
            $table = $this->database->config->getCurrentTable();
            $optionsFile = "../src/customfields/$table/" . $field['Field'] . ".json";

            if (is_file($optionsFile)) {

                //array_merge will overwrite keys in $fl[$key] with values in $overrideArray
                $overrideArray = json_decode(file_get_contents($optionsFile), true) ?? [];
                $fl[$key] = array_merge($fl[$key], $overrideArray);
            }
        }

        $this->fieldList = $fl;
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

            "tinytext" => ['text', 255, ""],
            "text" => ['text', 65535, ""],
            "datetime" => ['datetime', 20, ""],
            "blob" => ['file', "", ""],
            "mediumblob" => ['file', "", ""],
            "longblob" => ['file', "", ""],
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

            return ["", "", ""];
        }

        //I just want to check if a field is signed or not
        $typeArray[2] = ($typeArray[2] === "unsigned") ? false : true;

        //todo: checks
        return $typeArray;
    }
}
