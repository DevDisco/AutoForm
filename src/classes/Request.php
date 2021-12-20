<?php

class Request
{

    private array $cleanPost = [];
    private array $cleanGet = [];
    private array $fieldList = [];
    private Config $config;


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
        
        if( !$succes){
            Logger::toLog($_POST, "_POST");
            Logger::toLog($_FILES, "_FILES");
            
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
    
    private function sanitizeInt( $postValue ){

        $postValue = filter_var(
            $postValue,
            FILTER_SANITIZE_NUMBER_INT
        );
        
        if ($postValue == ""){

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
        
        if ($postValue === null && !$required ){
            
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
            
            //todo: use prefill checker
            
            if (Session::getCurrentId()){
                
                //we're updating, check if text input has been set
                $prefillValue = $_POST["prefill_".$name];

                if ( $prefillValue !== "" && $postValue === $prefillValue){
                    
                    return true;
                }
            }

            if (!isset($_FILES[$name])) {

                $this->error->setError("Upload: No file can be found for " . $name . ".");
                return false;
            } else if ($_FILES[$name]['name'] === "" && !$required ) {

                //empty upload on updating an optional file input
                return true;
            } else if ($_FILES[$name]['size'] > $maxfilesize) {

                $this->error->setError("Upload: The uploaded file for " . $name . " is too large.");
                return false;
            } else if ( $component === "input_image"){

                $imageArray = getimagesize($_FILES[$name]['tmp_name']);
                //Logger::toLog($imageArray);

                $width ?? false;
                $heigth ?? false;

                if ( !Core::checkAgainstDimension($width, $imageArray[0]) ||
                     !Core::checkAgainstDimension($height, $imageArray[1]) ) {

                    $error = "There is something wrong with the dimensions of the uploaded image. The image should meet these conditions: width must be ".htmlentities($width) ." pixels, height must be ". htmlentities($height)." pixels";

                    $this->error->setError(rtrim($error, ",").". ");
                    
                    return false;                    
                }
            }
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
    
    private function checkUploadState( string $inputName, string $postValue ){

        $prefillValue = $_POST["prefill_" . $inputName] ?? "";
        $name_FILES = $_FILES[$inputName]['name'] ?? "";
        
        if ( !Session::getCurrentId() || $prefillValue === "" ) {

            //not an update
            if ( $name_FILES === "" ){
                //empty optional file, do nothing
                return UPL_NONE;                
            }
            else{
                //upload file
                return UPL_UPLOAD;                
            }
        } 
        else if ( $postValue === $prefillValue ) {

            //update, nothing changed to the file
            return UPL_KEEP;
        } else if ( $postValue === "" && $name_FILES === "" ){

            //update, file was deleted, nothing new chosen
            return UPL_DELETE;
        } else if ($postValue === "" && $name_FILES !== "") {

            //update, file was deleted, new file chosen
            return UPL_DELETE_UPLOAD;
        }
        
        return UPL_NONE;
    }

    //todo: make separate class
    /**
     * Checks cleanPost to see if there are any file inputs,
     * and deals with either base64 conversion for database inserts,
     * or file uploads to a directory.
     */
    public function processFiles(array $cleanPost, array $fieldList): array
    {
        
        foreach ($fieldList as $field) {

            if ($field['type'] === "file") {
            
                $path = $this->config->UPLOAD_ROOT ?? false;
                $key  = $field['name'];
                $prefill = $this->checkUploadState($key, $cleanPost[$key]);
                $prefillValue = $_POST["prefill_" . $key] ?? "";
                
                if ( $prefill === UPL_KEEP ){

                    Logger::toLog("Keep current file", "processFiles");
                }
                else if ( $prefill === UPL_NONE ){

                    Logger::toLog("Empty optional file input", "processFiles");
                } 
                else if ($prefill === UPL_DELETE) {

                    Logger::toLog("Delete orphaned file ". $prefillValue, "processFiles");
                    $this->deleteFile($path . $prefillValue);
                }
                else if ($path) {
                    
                    $path .= ($field['path'] ?? "");

                    if (is_dir($path)) {

                        $filename = $this->createSafeFilename($path, $_FILES[$key]['name']);

                        //update cleanPost
                        $cleanPost[$key] = $filename;

                        // Location
                        $target_file = $path . $filename;
                        

                        // Upload file
                        if (!move_uploaded_file(
                            $_FILES[$key]['tmp_name'],
                            $target_file)
                            ){
    
                            //error
                            $this->error->setError("Can't move file to folder $path", 500);
                            $this->error->showAndAbort();
                        }

                        Logger::toLog("Uploaded " . $target_file, "processFiles");

                        if ($prefill === UPL_DELETE_UPLOAD) {
                            
                            Logger::toLog("Delete original file " . $prefillValue, "processFiles");
                            $this->deleteFile($path . $prefillValue);
                        }
                        

                        
                        Logger::toLog($cleanPost, "cleanPost");
                        Logger::toLog($_POST, "_POST");
                        Logger::toLog($_FILES, "_FILES");
                        Logger::toLog($fieldList, "fieldList");
                        //process upload
                        
                    } else {

                        //error
                        $this->error->setError("Can't find upload folder $path", 500);
                        $this->error->showAndAbort();
                    }
                } else {

                    //images and other files will be stored inside db
                    $image_base64 = base64_encode(file_get_contents($_FILES[$key]['tmp_name']));
                    $image = "data:" . $_FILES[$key]['type'] . ";base64," . $image_base64;
                    $cleanPost[$key] = $image;
                    Logger::toLog("Insert image as base64", "processFiles");
                }
            }
        }

        return $cleanPost;
    }
    
    private function deleteFile( $path ){
    
        if (is_file($path)){
            
            unlink($path);
        }
    }
    
    //todo: check length sql field
    private function createSafeFilename( string $path, string $filename, int $rand=0 ):string{

        $filename = str_replace(' ', '_', $filename);
        $filename = preg_replace('/[^a-zA-Z0-9\-\._]/', '', $filename);  
        
        if ( $rand > 0 ){

            $pathinfo = pathinfo($path . $filename);
            
            $filename = $pathinfo['filename']."_".$rand.".". $pathinfo['extension'];
        }
        
        if ( is_file( $path.$filename )){
            
            return $this->createSafeFilename($path, $filename, $rand=rand(1000,9999) );
        }
        
        return $filename;
    }
    
}
