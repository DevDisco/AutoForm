<?php

class Request
{
    private $fieldList = [];
    private $cleanPost = [];
    private $cleanGet = [];

    public function __construct(private AutoForm $form, public SimpleError $error)
    {

        $this->fieldList = $form->getFieldList();
    }

    /**
     * Returns TRUE if all values in POST are validated,
     * Filters fields (including errored) and puts them in the cleanPost array
     */
    public function validatePost(): bool
    {
        $succes = TRUE;

        foreach ($this->fieldList as $field) {

            // if (!$this->validateInput($field)) {

            //     $succes = FALSE;
            // }

            $succes = !$this->validateInput($field) ? FALSE : $succes;

            $this->cleanPost[$field['name']] = $this->sanitizeInput($field);
        }

        Session::setCleanPost($this->cleanPost);

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
        //file inputs are always empty, their contents are in the $_FILES array
        if ($postValue === null && $required && $type !== "checkbox" && $type !== "file") {

            $this->error->setError("Missing required value'" . $name . "'.");
            return FALSE;
        }

        if (is_array($postValue)) {

            $postValue = implode("|", $postValue);
            $type = "text";
        }

        if ($type === "number" && $max && intval($postValue) > $max) {

            $this->error->setError("The value of '" . $name . "' cannot be larger than " . $max . ". The given value is " . $postValue . ".");
            return FALSE;
        }

        if (isset($maxlength) && strlen($postValue) > $maxlength) {

            $this->error->setError("Sorry, '" . $name . "' cannot be longer than " . $maxlength . " characters. The given '" . $postValue . "' contains " . strlen($postValue) . " characters.");
            return FALSE;
        }

        if ($type === "url" && filter_var($postValue, FILTER_VALIDATE_URL) === FALSE) {

            $this->error->setError("The value of '" . $name . "' should be a valid URL.  The given value is " . $postValue . ".");
            return FALSE;
        }

        if ($type === "email" && filter_var($postValue, FILTER_VALIDATE_EMAIL) === FALSE) {

            $this->error->setError("The value of '" . $name . "' should be a valid e-mail address. The given value is " . $postValue . ".");
            return FALSE;
        }

        if ($type === "file") {

            if (!isset($_FILES[$name])) {

                $this->error->setError("No file can be found for " . $name . ".");
                return FALSE;
            } else if ($_FILES[$name]['size'] > $maxfilesize) {

                $this->error->setError("The uploaded file for " . $name . " is too large.");
                return FALSE;
            } else {

                $imageArray = getimagesize($_FILES[$name]['tmp_name']);
                Logger::toLog($imageArray);

                $maxwidth ?? FALSE;
                $minwidth ?? FALSE;
                $maxheigth ?? FALSE;
                $minheigth ?? FALSE;

                if (
                    ($maxwidth && $imageArray[0] > $maxwidth )||
                    ($minwidth && $imageArray[0] < $minwidth) ||
                    ($maxheigth && $imageArray[1] > $maxheigth) ||
                    ($minheigth && $imageArray[1] < $minheigth) ) {

                    $error = "There is something wrong with the dimensions of the uploaded image. The image should meet these conditions: ";
                    $error .= ($minwidth) ? " minimum width = ". $minwidth." pixels," : "";
                    $error .= ($maxwidth) ? " maximum width = ". $maxwidth. " pixels," : "";
                    $error .= ($minheigth) ? " minimum width = ". $minheigth. " pixels," : "";
                    $error .= ($maxheigth) ? " maximum width = ". $maxheigth. " pixels," : "";

                    $this->error->setError(rtrim($error, ",").". ");
                    
                    return FALSE;                    
                }
            }
        }

        return TRUE;
    }

    /**
     * Returns the table name from a GET or POST request as a string
     * Returns FALSE if no valid table name is found
     * A table name must be lowercase alphanumeric and may include underscores
     * It can't start with a number and can't end with an underscore.
     */
    public static function getTable(): string|bool
    {

        //NB: $this->isValidTable($_GET['t'] ?? FALSE is a shortcut for a check on isset()
        if ($_SERVER['REQUEST_METHOD'] === "GET" && self::isValidTable($_GET['t'] ?? FALSE)) {

            return $_GET['t'];
        } else if ($_SERVER['REQUEST_METHOD'] === "POST" && self::isValidTable($_POST['t'] ?? FALSE)) {

            return $_POST['t'];
        }

        return FALSE;
    }

    /**
     * Helper function for getTable
     */
    private static function isValidTable(string|bool $table): bool
    {
        //only accept lowercase characters, numbers and underscore
        //tablename can't start with a number or end with an underscore
        if (preg_match("/^[a-z_][A-Za-z0-9_]+[a-z0-9]$/", $table) && $table !== FALSE) {

            return TRUE;
        }

        return FALSE;
    }
}
