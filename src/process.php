<?php

$request = new Request($fields, $error);
$message = "";
$title = "1";

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $isValidated = $request->validatePost();

    if ($isValidated) {

        if (Session::getCurrentId()) {

            $success = $database->updateFormData($request);
        } else {

            $success = $database->insertFormData($request);
        }

        if ($success) {

            $title = "Success!";
            $message = "Your form has been submitted succesfully.";
            Session::unsetCleanPost();
            Session::unsetPrefill();
        } else {

            Logger::toLog($_POST, "_POST");
            Logger::toLog($_FILES, "_FILES");
            Logger::toLog($database->showTable(), "table");
            Logger::toLog($fieldList->get(), "fl");
            Logger::toLog($config, "config");
        }

        //what if not?

        //return to index.php or link?
    } else {

        $title = "Error!";
        $message = ($error->getErrorArray()['message'] ?? "");
        Session::setPrefill();
        Session::unsetCleanPost();
    }
} 
else if ($_SERVER['REQUEST_METHOD'] === "GET"){

    $isValidated = $request->validateGet();

    if ($isValidated) {
        
        if ( $success = $database->deleteRecord($request) ){

            $title = "Success!";
            $message = "The record has been deleted.";   
        }
        else{
            $title = "Error!";
            $message = ($error->getErrorArray()['message'] ?? "");
        }
    }
    else{

        $title = "Error!";
        $message = "Request: Missing or invalid input."; 
    }
}
else {

    $title = "Oops";
    $message = "You shouldn't be here. This page must be called from a form.";
}


require_once TEMPLATES_FOLDER . "header.php";
require_once TEMPLATES_FOLDER . "result.php";
Logger::printLog(DEBUG);
$error->printError(DEBUG);
require_once TEMPLATES_FOLDER . "footer.php";
