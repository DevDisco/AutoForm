<?php

require_once "../src/init.php";

$session = new Session();
$config = new Config(Request::getTable());
$error = new SimpleError();
$database = new Database($config, $error);
$fields = new Fields($database);
$form = new AutoForm($fields);
$navbar = new Navigation($config);

//Logger::toLog($database->showTable(), "table");
//Logger::toLog($fieldList->get(), "fl");
//Logger::toLog($_SESSION, "session");

$formHtml = $form->createForm();

require_once "../src/templates/header.php";
require_once "../src/templates/navbar.php";
require_once "../src/templates/main.php"; 

//print "\n<img src='".$database->getImage()."'>\n";
Logger::printLog(DEBUG);
$error->printError(DEBUG);
require_once "../src/templates/footer.php"; 