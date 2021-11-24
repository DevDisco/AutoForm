<?php

require_once "../src/init.php";

$debug = false;
//$debug = true;

$config = new Config();
$error = new SimpleError();
$request = new Request();
$database = new Database($config, $error);

//the request must include the name of the table you want to read from
//and the id of the record you want to retrieve, which must be named 'id'
$id = $request->getID();
$table = $request->getTable();

if ($table === FALSE) {

    $error->setError("No or not a valid table.", 406);
} else {

    //run query, will report on invalid parameters
    $result = $database->run($table, $id, FALSE);

    Logger::toLog($result, "result");

    if (is_array($result)) {

        if (!$debug) {

            header('Content-type: application/json');
            print json_encode($result);
            exit;
        }
    } 
}

//any errors are returned as a json object
if (!$debug) {

    header('Content-type: application/json');
    print json_encode($error->getErrorArray());
    exit;
}

//this is just for debugging
$error->printError($debug);
Logger::printLog($debug);
