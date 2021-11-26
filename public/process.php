<?php

require_once "../src/init.php";

$debug = false;
$debug = true;

$session = new Session();
$config = new Config(Request::getTable());
$error = new SimpleError();
$database = new Database($config, $error);
$form = new AutoForm($database);
$request = new Request($form, $error);



if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $isValidated = $request->validatePost();
    $message = "";
    $title = "1";
    
    if ($isValidated ){
         
        $isInserted = $database->insertAutoForm($request->getCleanPost());
        
        if ($isInserted){
            
            $title = "Success!";
            $message = "Your form has been submitted succesfully.";
            Session::unsetCleanForm();
        }
        
        //what if not?
        
        //return to index.php or link?
    }
    else{

        $title = "Error!";
        $message = ($error->getErrorArray()['message'] ?? "")." This is probably a programming error, not something caused by your input.";  
        //return cleanPost and prefill form       
    }
    
} else {

    $title = "Oops";
    $message = "You shouldn't be here. This page must be called from a form.";    
}
Logger::toLog($_SESSION, "session");

require_once "../src/templates/header.php";
require_once "../src/templates/result.php";
Logger::printLog($debug);
$error->printError($debug);
require_once "../src/templates/footer.php"; 
