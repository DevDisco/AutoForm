<?php

class Request
{

    private array $cleanPost = [];
    private array $cleanGet = [];
    private array $fieldList = [];
    public Config $config;


    //todo: can I get rid of public and use SimpleError as above?
    public function __construct(public Fields $fields, public SimpleError $error)
    {
        $this->fieldList = $fields->get();
        $this->config = $fields->database->config;

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

            //Logger::toLog($field, "field");

            $succes = !$this->validateInput($field) ? false : $succes;

            $this->cleanPost[$field['name']] = $this->sanitizeInput($field);
        }

        Session::setCleanPost($this->cleanPost);
        Logger::toLog($this->cleanPost, "cleanPost");

        if (!$succes) {
            Logger::toLog($_POST, "_POST");
            Logger::toLog($_FILES, "_FILES");
        }

        return $succes;
    }

    public function validateGet(): bool
    {
        $succes = true;

        if (!$this->cleanGet['t'] = $this->getTable()) {

            $succes = false;
        }

        if (!$this->cleanGet['id'] = $this->getId()) {

            $succes = false;
        }

        if (!$this->cleanGet['d'] = $this->getDelCheck()) {

            $succes = false;
        }

        if (!$succes) {
            Logger::toLog($_GET, "_GET");
            Logger::toLog($this->cleanGet, "this->cleanGet");
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

            "number" => $this->sanitizeInt($postValue),
            "url" => filter_var($postValue, FILTER_SANITIZE_URL),
            "email" => filter_var($postValue, FILTER_SANITIZE_EMAIL),
            default => $postValue
        };

        return $postValue;
    }

    private function sanitizeInt($postValue)
    {

        $postValue = filter_var(
            $postValue,
            FILTER_SANITIZE_NUMBER_INT
        );

        if ($postValue == "") {

            $postValue = 0;
        }

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

        if ($postValue === null && !$required) {

            //empty non-required fields don't have to be checked
            return true;
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

            return FileUploads::validateFileInput( $this, $field, $postValue );
        }

        return true;
    }

    /**
     * Returns the id from a GET or POST request as an integer
     * Returns false if no valid id is found
     * An id must be an integer string value larger than 0
     */
    public static function getID(): int|bool
    {

        if ($_SERVER['REQUEST_METHOD'] === "GET" && self::isValidID($_GET['id'] ?? false)) {

            return (int)$_GET['id'];
        } else if ($_SERVER['REQUEST_METHOD'] === "POST" && self::isValidID($_POST['id'] ?? false)) {

            return (int)$_POST['id'];
        } else {

            return false;
        }
    }

    /**
     * Helper function for getID
     */
    private static function isValidID(string|bool $value): bool
    {

        //mind that id from $_GET is a string, not an int.
        if ((ctype_digit($value)) && (int)$value > 0) {

            return true;
        }

        return false;
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

    public static function getDelCheck(): bool|array
    {

        $serial = "";

        //navbar links
        if ($_SERVER['REQUEST_METHOD'] === "GET" && $_GET['d'] ?? false) {

            $serial = $_GET['d'];
        }

        return Core::delDecode($serial);
    }
}
