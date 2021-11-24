<?php

require_once "../src/init.php";

$debug = false;
$debug = true;

$config = new Config();
$error = new SimpleError();
$database = new Database($config, $error);
$form = new AutoForm($database);
$request = new Request($form, $error);

//I only accept input coming from this page
if ($_SERVER['REQUEST_METHOD'] === "POST") {

    // && (basename($_SERVER['HTTP_REFERER']) == $_SERVER['SCRIPT_NAME'])
    Logger::toLog($_SERVER);
    $request->validatePost();
    $succes = $database->insertAutoForm($request->getCleanPost());
    //Logger::toLog($request->getCleanPost(), "getCleanPost");
} else {
}



Logger::printLog($debug);
$error->printError($debug);

