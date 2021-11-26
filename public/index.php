<?php

require_once "../src/init.php";

$debug = false;
$debug = true;

$session = new Session();
$config = new Config(Request::getTable());
$error = new SimpleError();
$database = new Database($config, $error);
$form = new AutoForm($database);
$navbar = new Navigation($config);

Logger::toLog($_SESSION, "session");
Logger::toLog($navbar, "navbar");

$formHtml = $form->createForm();

require_once "../src/templates/header.php";
require_once "../src/templates/navbar.php";
require_once "../src/templates/main.php"; 
Logger::printLog($debug);
$error->printError($debug);
require_once "../src/templates/footer.php"; 