<?php

class AutoForm
{
    private Database $database;
    private array $fieldList;

    public function __construct(private Fields $fields) {
        
        $this->fieldList = $fields->get();
        $this->database = $fields->database;

        //Logger::toLog($this->table, "table");
    }


    public function createForm(): string
    {
        $form = file_get_contents("../src/templates/components/form.php");
        $inputs = "";
        $enctype = "";
        Logger::toLog($this->fieldList, "fieldList");

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

            if ($component === "input_image") {
                
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
}
