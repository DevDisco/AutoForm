<?php

class Logger
{

    public function __construct(private string $projectDir="", private string $logfile="")
    {
    }
    
    //prints a preformatted var_dump of $var, 
    //prefixed with $description
    static function printVariable(string $description, mixed $var): void{
    
        print "<pre>";
        print $description . ": ";
        var_dump($var);
        print "</pre>";
    }

    static function toLog(mixed $var, string $description="Logger"): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);

        $GLOBALS['logger'][] = [$description=>$var, 'trace'=> $backtrace];
    }

    static function printLog(bool $debug = true): void
    {
        
        if(!isset($GLOBALS['logger']) || !$debug){
            return;
        }
        
        $logged = $GLOBALS['logger'];

        print "<pre>";
        foreach ($logged as $entry ) {
            $key = key($entry);
            print $key ."\n". $entry['trace'][0]['file'].":". $entry['trace'][0]['line']."\n";
            print_r($entry[$key]);
            print "\n";
        }
        print "</pre>";
        
        unset($GLOBALS['logger']);
    }

    public function getLogfilePath():string|false
    {
        return realpath($this->projectDir . $this->logfile);
    }

    public function toLogFile(string $message = "test", string $method = "")
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
        $message = date("y-m-d H:i:s") . "\n\tText:\t" . $message . "\n";
        $message .= "\tFile:\t" . str_replace($this->projectDir, "~", $backtrace[0]['file']) . "\n";
        $message .= "\tLine:\t" . $backtrace[0]['line'] . "\n";
        if ($method !== "") {
            $message .= "\tMethod:\t" . $method . "\n";
        }
        file_put_contents($this->projectDir . $this->logfile, $message, FILE_APPEND);
    }

    public function emptyLogFile()
    {
        file_put_contents(
            $this->projectDir . $this->logfile,
            ""
        );
    }

    public function dividerToLogfile()
    {
        file_put_contents($this->projectDir . $this->logfile, "==========================\n", FILE_APPEND);
    }
}
