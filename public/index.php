<?php

require_once "../src/init.php";

$debug = false;
$debug = true;

$config = new Config();
$error = new SimpleError();
$database = new Database($config, $error);
$form = new AutoForm($database);
//$request = new Request($form, $error);
$formHtml = $form->createForm();



Logger::toLog($form->getFieldList());

require_once "../src/templates/header.php";
require_once "../src/templates/main.php"; 
Logger::printLog($debug);
$error->printError($debug);
require_once "../src/templates/footer.php"; 