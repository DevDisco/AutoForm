<?php

class AutoForm
{
    private Database $database;
    private Config $config;
    private array $fieldList;
    private int $currentId;
    private string $currentTable;
    private array $blockedTags;

    public function __construct(private Fields $fields)
    {

        $this->fieldList = $fields->get();
        $this->database = $fields->database;
        $this->config = $fields->database->config;
        $table = $this->config->getCurrentTable();
        $this->currentTable = $table;
        $this->currentId = $this->config->getCurrentId();
        $this->blockedTags = $this->config->EDITOR->LOCK_TABLE_FIELDS->$table ?? [];
    }


    public function createForm(string $form="insert"): string
    {
        $form = file_get_contents(TEMPLATES_FOLDER . "components/form_$form.php");
        $inputs = "";
        $enctype = "";
        Logger::toLog(empty(Session::getPrefill()), "prefill");

        foreach ($this->fieldList as $field) {

            $inputType = $field['component'];
            $input = file_get_contents(TEMPLATES_FOLDER . "components/$inputType.php");

            if (strpos($input, "[[repeat]]") && !strpos($input, "<option")) {

                //this is for checkboxes and radio buttons
                $input = $this->createRepeatingInput($field, $input);
            } else if ($field['type'] === "file") {

                $enctype = "enctype='multipart/form-data'";
                $input = $this->createFileInput($field, $input);
            } else {

                $input = $this->createSingleInput($field, $input);
            }

            $inputs .= $input;
        }

        $form = str_replace("{{table}}", $this->currentTable, $form);
        $form = str_replace("{{id}}", $this->currentId, $form);
        $form = str_replace("{{inputs}}", $inputs, $form);
        $form = str_replace("{{enctype}}", $enctype, $form);

        Session::setCurrentTable($this->currentTable);
        Session::setCurrentId($this->currentId);
        Session::unsetPrefill();

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

        $prefill = Session::getPrefill()[$field['name']] ?? false;

        //Logger::toLog(Session::getCleanPost(), "getCleanPost");

        if ($prefill) {

            $value = $prefill;
        }

        return $value;
    }

    private function isSelected(array $field, string $value): bool
    {
        //preset from database or custom field
        $default = $field['value'];

        //used to fill a field when redirected to form after a failed post
        $prefill = Session::getPrefill()[$field['name']] ?? null;

        

        if ($prefill !== null && strpos($prefill, "|")) {

            $prefillArray = explode("|", $prefill);
            return in_array($value, $prefillArray) ?: false;
        } else if ($prefill !== null && $prefill === $value) {
            //Logger::toLog($field['name'] . ", value=$value, default=" . $field['value'] . ", prefill=" . $prefill, "test1");
            return true;
        } else if ($prefill === null && $default === $value) {
            //Logger::toLog($field['name'] . ", value=$value, default=" . $field['value'] . ", prefill=" . $prefill, "test2");
            return true;
        }

        return false;
    }

    private function getReadOnly(array $field, string $input): string
    {
        $input =  false;  
        //Logger::toLog(, "getDisabled");

        if ( in_array( $field['name'], $this->blockedTags ) && $this->currentId > 0 && $input !== "" ){
            
            return "readonly";
        }
        return "";
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
        $input = str_replace("{{readonly}}", $this->getReadOnly($field, $value), $input);
        $input = str_replace("{{id}}", $field['name'], $input);
        $input = str_replace("{{value}}", $value, $input);
        $input = $this->parseAttributes($field, $input);
        $input = $this->cleanUp($input);

        return $input . "\n";
    }


    private function createFileInput(array $field, string $input): string
    {
        $label = $this->cleanLabel($field['name'], $field);
        $value = $this->getPresetValue($field);

        if ($field['required']) {

            $label .= " *";
        }

        if ($value) {

            $input = str_replace("{{disabled}}", "disabled", $input);
            $input = str_replace("{{class_input}}", "d-none", $input);
            $input = str_replace("{{class_prefill}}", "d-flex", $input);
        } else {

            $input = str_replace("{{class_input}}", "d-block", $input);
            $input = str_replace("{{class_prefill}}", "d-none", $input);
        }

        $input = str_replace("{{label}}", $label, $input);
        $input = str_replace("{{instructions}}", $this->createInstructions($field), $input);
        $input = str_replace("{{id}}", $field['name'], $input);
        $input = str_replace("{{value}}", $value, $input);
        $input = $this->parseAttributes($field, $input);
        $input = $this->cleanUp($input);

        return $input . "\n";
    }

    //creates a radio or checkbox group from a one-dimensional array
    //ie: value and label are the same
    private function createRepeatingInput(array $field, string $input): string
    {
        $item = "";
        $input = rtrim($input);
        $return = "";
        $i = 0;

        //parse input into fixed and repeated parts
        $parts = explode("[[repeat]]", $input);

        $label = $this->cleanLabel($field['name'], $field);
        $label = $field['required'] ? ($label .= " *") : $label;
        $instructions = $this->createInstructions($field);
        $label = $parts[0] = str_replace("{{label}}", $label, $parts[0]);


        $options = $this->getOptionsList($field);

        //return input unaltered if the option option (duh) isn't set
        if ($options === FALSE) {

            return $input;
        }

        //Logger::toLog($options, "options");

        foreach ($options as $key => $value) {

            $id = $field['name'] . "_" . $i;
            $label = $this->cleanLabel($value);
            $label = $value;
            $valueColumn = $field['options']['valueColumn'] ?? false;
            $value = $valueColumn ? $key : $value;
            $item = rtrim($parts[1]);

            $item = str_replace("{{id}}", $id, $item);
            $item = str_replace("{{label}}", $label, $item);
            $item = str_replace("{{value}}", $value, $item);
            $item = str_replace("{{instructions}}", $instructions, $item);

            //if ($field['value'] == $option) {
            if ($this->isSelected($field, $value)) {
                //Logger::toLog($field['name']."=".$value, "isSelected" );
                $item = str_replace("{{checked}}", "checked", $item);
            }

            //checkboxes don't go along with required
            $setRequired = ($key === 0 && $field['type'] === "radio");

            $item = $this->parseAttributes($field, $item, $setRequired);
            $item = $this->cleanUp($item);

            $return .= $item . "\n";

            ++$i;
        }

        return $parts[0] . $return . $parts[2] . "\n";
    }

    private function createInstructions(array $field): string
    {
        extract($field);
        $instructions = "";
        $config = $this->config;

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
                $instructions = str_replace("{{1}}", htmlentities($width), $instructions);
                $instructions = str_replace("{{2}}", htmlentities($height), $instructions);
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

    //create the options for a select from an associative array
    private function insertOptions(array|bool $field, string $input): string
    {
        $parts = explode("[[repeat]]", $input);
        $insert = "";
        $options = $this->getOptionsList($field);

        //return input unaltered if the option option (duh) isn't set
        if ($options === FALSE) {

            return $input;
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

    function getOptionsList(array $field): array|bool
    {

        //return input unaltered if the option option (duh) isn't set
        if (empty($field['options'])) {

            return false;
        }

        if (isset($field['options']['table']) && isset($field['options']['nameColumn'])) {

            return $this->database->getOptionsFromDb($field);
        } else {

            return $field['options'];
        }
    }

    //checks if  [LOCK_TABLE_FIELDS]->table->field is set in config
    private function isDisabled(array $field)
    {
    }
}
