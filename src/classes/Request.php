<?php

class Request
{
    private $fieldList = [];
    private $cleanPost = [];

    public function __construct(private AutoForm $form, public SimpleError $error)
    {

        $this->fieldList = $form->getFieldList();
    }

    public function validatePost(): bool
    {
        foreach ($this->fieldList as $field) {

            if ($this->validateInput($field)) {
                
                $this->cleanPost[$field['name']] = $this->sanitizeInput($field);
            }
            else{
                
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    public function getCleanPost():array{
    
        return $this->cleanPost;
    }
    
    private function sanitizeInput(array $fieldArray): mixed{

        extract($fieldArray);

        //when a checkbox set is left empty, there will be no post value.
        $postValue = $_POST[$name] ?? "";
        
        if (is_array($postValue)) {

            $postValue = implode(",", $postValue);
        }
        
        $filter = match ($type) {

            "text" => FILTER_SANITIZE_STRING,
            "number" => FILTER_SANITIZE_NUMBER_INT,
            "url" => FILTER_SANITIZE_URL,
            "email" => FILTER_SANITIZE_EMAIL,
            default => FILTER_SANITIZE_STRING,            
        };

        return filter_var($postValue, $filter);
    }

    private function validateInput(array $fieldArray): bool
    {

        extract($fieldArray);

        $postValue = $_POST[$name] ?? null;

        //I force checkboxes to act as non-required, whatever the database says
        if ($postValue === null && $required && $type !== "checkbox" ) {

            $this->error->setError("Missing required value'" . $name . "'.");
            return FALSE;
        }
        
        if ( is_array($postValue ) ){

            $postValue = implode(",", $postValue);
            $type = "text";
        }

        if ($type === "number" && $max && intval($postValue) > $max) {

            $this->error->setError("The value of '" . $name . "' cannot be larger than " . $max . ". The given value is ". $postValue.".");
            return FALSE;
        }

        if ($maxlength && strlen($postValue) > $maxlength) {

            $this->error->setError("Sorry, '" . $name . "' cannot be longer than " . $maxlength . " characters. The given '". $postValue."' contains ".strlen($postValue)." characters.");
            return FALSE;
        }

        if ($type === "url" && filter_var($postValue, FILTER_VALIDATE_URL) === FALSE) {

            $this->error->setError("The value of '" . $name ."' should be a valid URL.  The given value is " . $postValue . ".");
            return FALSE;
        }

        if ($type === "email" && filter_var($postValue, FILTER_VALIDATE_EMAIL) === FALSE) {

            $this->error->setError("The value of '" . $name ."' should be a valid e-mail address. The given value is " . $postValue . ".");
            return FALSE;
        }

        return TRUE;
    }
}
