<?php

/**
 * I really should take time to learn to use the standard Error class...
 * This class does the trick, though. 
 * Use setError to load and error on the object,
 * printError at the end of the page to show the error during debugging,
 * and getErrorArray in combination with json_encode for ajax requests
 */
class SimpleError
{

    public function __construct(private int $httpError = 0, private string $message = "", private array $trace=[])
    {
    }

    /**
     * Loads the error message and httpError number on the SimpleError object.
     */
    public function setError(string $message, int $httpError = 0): void
    {

        $this->trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
        $this->message = $message;
        $this->httpError  = $httpError;
    }

    /**
     * Prints the contents of the SimpleError object
     * Doesn't print anything if no error has been set.
     */
    public function printError(bool $debug=FALSE): void
    {

        if ($this->httpError > 0) {

            print $this->httpError . " - ";
        }

        if ($this->message !== "" ) {

            print $this->message;
        }

        if (!empty($this->trace) && $debug) {

            print " [".$this->trace[0]['file']." (". $this->trace[0]['line'].")]";
        }
    }

    /**
     * Converts the SimpleError object to an array.
     * If no errors are set you will get an 500 error 
     * with the message that no error info has been found.
     */
    public function getErrorArray(){
        
        $error = array("httpError"=>500, "message"=>"Something went wrong but I don't know what.");

        if ($this->httpError > 0) {

            $error['httpError'] = $this->httpError;
        }

        if ($this->message !== "") {

            $error['message'] = $this->message;
        }   
        
        return $error;
    }
}
