<?php

$request = new Request($fields, $error);

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    
    $isValidated = $request->validatePost();
    $message = "";
    $title = "1";
    
    if ($isValidated ){
         
        $isInserted = $database->insertFormData($request);
        
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
        $message = ($error->getErrorArray()['message'] ?? "");  
        //return cleanPost and prefill form       
    }
    
} else {

    $title = "Oops";
    $message = "You shouldn't be here. This page must be called from a form.";    
}
Logger::toLog($_SESSION, "session");

require_once TEMPLATES_FOLDER."header.php";
require_once TEMPLATES_FOLDER . "result.php";
Logger::printLog(DEBUG);
$error->printError(DEBUG);
require_once TEMPLATES_FOLDER . "footer.php"; 
