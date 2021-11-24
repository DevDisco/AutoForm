<?php

class AutoForm
{

    public function __construct(
        private Database $database,
        public bool|array $table = FALSE,
        private array $fieldList = []
    ) {

        //show form
        $this->table = $database->showTable($this->database->config->TABLE);

        //Logger::toLog($this->table, "table");

        $this->createFieldList();
        $this->createForm();
    }

    public function getFieldList()
    {

        return $this->fieldList;
    }

    public function createForm(): string
    {
        $form = file_get_contents("../src/templates/components/form.php");
        $inputs = "";

        foreach ($this->fieldList as $field) {

            $inputType = $field['component'];
            $input = file_get_contents("../src/templates/components/$inputType.php");

            if (strpos($input, "[[repeat]]") && !strpos($input, "<option")) {

                //this is for checkboxes and radio buttons
                $input = $this->createRepeatingInput($field, $input);
            } else {

                $input = $this->createSingleInput($field, $input);
            }

            $inputs .= $input;
        }

        $form = str_replace("{{inputs}}", $inputs, $form);
        return $form;
    }

    private function parseAttributes(array $field, string $input, bool $setRequired = TRUE): string
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
        $input = str_replace(" maxlength=\"\"","",$input);
        $input = str_replace(" max=\"\"", "", $input);

        return $input;
    }

    private function createSingleInput(array $field, string $input): string
    {
        $label = $this->cleanLabel($field['name'], $field);
        $input = $this->insertOptions($field, $input);
        $value = $field['value'] ?? "";

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

        foreach ($field['options'] as $key => $value) {

            $id = $field['name'] . "_" . $key;
            $label = $this->cleanLabel($value);
            $item = str_replace("{{id}}", $id, $parts[1]);
            $item = str_replace("{{label}}", $label, $item);
            $item = str_replace("{{value}}", $value, $item);
            $item = str_replace("{{instructions}}", $instructions, $item);

            if ($field['value'] == $value) {

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

        $instructions = "";
        $config = $this->database->config;

        if (is_array($field['options'] ?? FALSE)) {

            //selects & checkboxes
            $instructions = $config->INSTRUCTIONS_CHOICE;
        } else if ($field['type'] === "number" && $field['max'] > 0) {

            $instructions = str_replace("{{?}}", $field['max'], $config->INSTRUCTIONS_NUMBER);
        } else if ($field['type'] === "email") {

            $instructions = $config->INSTRUCTIONS_EMAIL;
        } else if ($field['type'] === "url") {

            $instructions = $config->INSTRUCTIONS_URL;
        } else if ($field['type'] === "password") {

            $instructions = $config->INSTRUCTIONS_PASSWORD;
        } else if ($field['type'] === "text" && $field['maxlength'] > 0) {
            $instructions =
                str_replace("{{?}}", $field['maxlength'], $config->INSTRUCTIONS_TEXT);
        }


        return $instructions;
    }

    private function cleanLabel(string $fieldName, array $field = []): string
    {

        $label = $field['label'] ?? FALSE;

        if (!$label) {

            $label = ucwords(str_replace("_", " ", $fieldName));
        }


        return $label;
    }

    private function insertOptions(array|bool $fieldArray, string $input): string
    {

        $parts = explode("[[repeat]]", $input);
        $options = "";

        if (empty($fieldArray['options'])) {

            return $input;
        }

        foreach ($fieldArray['options'] as $value) {

            $options .= str_replace("{{value}}", $value, rtrim($parts[1]));
        }

        return $parts[0] . $options . $parts[2];
    }

    private function createFieldList()
    {
        $fl = [];

        foreach ($this->table as $key => $field) {

            //ignore autoincrementing primary keys 
            if ($field['Field'] === "id" || ($field['Key'] === "PRI" && strpos($field['Extra'], "auto_increment") !== FALSE)) {

                continue;
            }

            //ignore date fields with default set to current_timestamp()
            if (($field['Type'] === "datetime" && $field['Default'] === "current_timestamp()")) {

                continue;
            }

            //convert sql field info to an array that will be used to 
            //create and validate the form
            $fl[$key]['name'] = $field['Field'];
            $fl[$key]['value'] = $field['Default'] ?? FALSE;

            //the type field contains information about type, length and sign
            $typeArray = $this->parseSqlType($field['Type']);

            //default is a standard text input with html5 types based on sql field type
            $fl[$key]['component'] = "default";
            $fl[$key]['type'] = $this->getInputType($typeArray);

            //correct input and type for longer texts
            if ($fl[$key]['type'] === 'text' && $typeArray[1] > 64) {

                $fl[$key]['component'] = "textarea";
            }

            //set maxlength and max
            if ($fl[$key]['type'] === 'text') {
                $fl[$key]['maxlength'] = intval($typeArray[1]);
            }

            $fl[$key]['max'] = $this->getAttributeMaxValue($typeArray);

            //get required
            $fl[$key]['required'] = ($field['Null'] === "NO") ? TRUE : FALSE;

            //override or expand with custom field settings
            $optionsFile = "../src/fields/" . $field['Field'] . ".json";

            if (is_file($optionsFile)) {

                //json
                $overrideArray = json_decode(file_get_contents($optionsFile), TRUE) ?? [];
                $fl[$key] = array_merge($fl[$key], $overrideArray);
            }
        }

        $this->fieldList = $fl;
    }

    private function getAttributeMaxValue(array $typeArray): int|bool
    {

        $isSigned = $typeArray[2];
        $max = FALSE;

        $power = match ($typeArray[0]) {

            "tinyint" => 8,
            "smallint" => 16,
            "mediumint" => 24,
            "mediumint" => 32,
            "bigint" => 64,
            default => FALSE,
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

        //correct for field length
        //there probably a fancy math way of doing this...:(
        $maxByLength = intval(str_repeat("9", $typeArray[1]));

        $max = ($maxByLength < $max) ? $maxByLength : $max;

        return $max;
    }

    private function getInputType(array $typeArray): string|bool
    {

        if ($typeArray === FALSE) {

            return FALSE;
        }

        $type = match ($typeArray[0]) {

            "int" => "number",
            "tinyint" => "number",
            "smallint" => "number",
            "mediumint" => "number",
            "date" => "number",
            "datetime" => "datetime-local",
            default => "text",
        };

        return $type;
    }



    private function parseSqlType($sqlType)
    {

        //text types have no length value
        $typeArray = match ($sqlType) {

            "tinytext" => ['text', 255, ""],
            "text" => ['text', 65535, ""],
            "datetime" => ['datetime', 20, ""],
            default => []
        };

        if (!empty($typeArray)) {

            return $typeArray;
        }

        if (strpos($sqlType, ") ") !== FALSE) {

            //bit hacky, but it works
            $sqlType = str_replace(") ", "(", $sqlType);
            $typeArray =  explode("(", $sqlType);
        } else {

            $sqlType = trim($sqlType, ")");
            $typeArray =  explode("(", $sqlType);
            $typeArray[2] = "";
        }

        //I just want to check if a field is signed or not
        $typeArray[2] = ($typeArray[2] === "unsigned") ? FALSE : TRUE;



        //todo: checks
        return $typeArray;
    }
}
