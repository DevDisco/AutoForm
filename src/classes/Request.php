<?php

class Request
{

    private array $cleanPost = [];
    private array $cleanGet = [];
    private array $fieldList = [];

    public function __construct(public Fields $fields, public SimpleError $error)
    {
        $this->fieldList = $fields->get();
        //Logger::toLog($fields,"fields");
    }

    /**
     * Returns true if all values in POST are validated,
     * Filters fields (including errored) and puts them in the cleanPost array
     */
    public function validatePost(): bool
    {
        $succes = true;

        foreach ($this->fieldList as $field) {

            Logger::toLog($field, "field");

            $succes = !$this->validateInput($field) ? false : $succes;

            $this->cleanPost[$field['name']] = $this->sanitizeInput($field);
        }

        Session::setCleanPost($this->cleanPost);
        
        if( !$succes){
            Logger::toLog($_POST, "_POST");
            Logger::toLog($_FILES, "_FILES");
            Logger::toLog($this->cleanPost, "cleanPost");
        }

        return $succes;
    }

    public function getCleanPost(): array
    {

        return $this->cleanPost;
    }

    public function getCleanGet(): array
    {

        return $this->cleanGet;
    }
    private function sanitizeInput(array $fieldArray): mixed
    {

        extract($fieldArray);

        //when a checkbox set is left empty, there will be no post value.
        $postValue = $_POST[$name] ?? "";

        if (is_array($postValue)) {

            $postValue = implode("|", $postValue);
        }

        $postValue = match ($type) {

            "number" => filter_var(
                $postValue,
                FILTER_SANITIZE_NUMBER_INT),
            "url" => filter_var($postValue, FILTER_SANITIZE_URL),
            "email" => filter_var($postValue, FILTER_SANITIZE_EMAIL),
            default => $postValue
        };

        return $postValue;
    }

    private function validateInput(array $field): bool
    {
       extract($field);

        $postValue = $_POST[$name] ?? null;

        //I force checkboxes to act as non-required, whatever the database says
        //file inputs are always empty, their contents are in the $_FILES array
        if ($postValue === null && $required && $type !== "checkbox" && $type !== "file") {

            $this->error->setError("Upload: Missing required value'" . $name . "'.");
            return false;
        }

        if (is_array($postValue)) {

            $postValue = implode("|", $postValue);
            $type = "text";
        }

        if ($type === "number" && $max && intval($postValue) > $max) {

            $this->error->setError("Upload: The value of '" . $name . "' cannot be larger than " . $max . ". The given value is " . $postValue . ".");
            return false;
        }

        if (isset($maxlength) && strlen($postValue) > $maxlength) {

            $this->error->setError("Upload: Sorry, '" . $name . "' cannot be longer than " . $maxlength . " characters. The given '" . $postValue . "' contains " . strlen($postValue) . " characters.");
            return false;
        }

        if ($type === "url" && filter_var($postValue, FILTER_VALIDATE_URL) === false) {

            $this->error->setError("Upload: The value of '" . $name . "' should be a valid URL.  The given value is " . $postValue . ".");
            return false;
        }

        if ($type === "email" && filter_var($postValue, FILTER_VALIDATE_EMAIL) === false) {

            $this->error->setError("Upload: The value of '" . $name . "' should be a valid e-mail address. The given value is " . $postValue . ".");
            return false;
        }

        if ($type === "file") {

            if (!isset($_FILES[$name])) {

                $this->error->setError("Upload: No file can be found for " . $name . ".");
                return false;
            } else if ($_FILES[$name]['size'] > $maxfilesize) {

                $this->error->setError("Upload: The uploaded file for " . $name . " is too large.");
                return false;
            } else if ( $component === "input_image"){

                $imageArray = getimagesize($_FILES[$name]['tmp_name']);
                Logger::toLog($imageArray);

                $width ?? false;
                $heigth ?? false;

                if ( !Core::checkAgainstDimension($width, $imageArray[0]) ||
                     !Core::checkAgainstDimension($height, $imageArray[1]) ) {

                    $error = "There is something wrong with the dimensions of the uploaded image. The image should meet these conditions: width must be ".$width." pixels, height must be ".$height." pixels";

                    $this->error->setError(rtrim($error, ",").". ");
                    
                    return false;                    
                }
            }
        }

        return true;
    }

    /**
     * Returns the table name from a GET or POST request as a string
     * Returns false if no valid table name is found
     * A table name must be lowercase alphanumeric and may include underscores
     * It can't start with a number and can't end with an underscore.
     */
    public static function getTable(): string|bool
    {

        //navbar links
        if ($_SERVER['REQUEST_METHOD'] === "GET" && self::isValidTable($_GET['t'] ?? false)) {

            unset($_SESSION['currentTable']);
            return $_GET['t'];
        }
        //link to process page from form
        else if ($_SERVER['REQUEST_METHOD'] === "POST" && self::isValidTable($_POST['t'] ?? false)) {

            return $_POST['t'];
        }
        //link from result page in case of an error
        else if (self::isValidTable($_SESSION['currentTable'] ?? false)) {

            $table = $_SESSION['currentTable'];
            unset($_SESSION['currentTable']);
            return $table;
        } 

        return false;
    }

    /**
     * Helper function for getTable
     */
    private static function isValidTable(string|bool $table): bool
    {
        //only accept lowercase characters, numbers and underscore
        //tablename can't start with a number or end with an underscore
        if (preg_match("/^[a-z_][A-Za-z0-9_]+[a-z0-9]$/", $table) && $table !== false) {

            return true;
        }

        return false;
    }
}
