<?php

$navbar = new Navigation($config);

//Logger::toLog($database->showTable(), "table");
//Logger::toLog($fieldList->get(), "fl");
//Logger::toLog($_SESSION, "session");
//Logger::toLog($config, "config");

$formHtml = $form->createForm();

require_once TEMPLATES_FOLDER."header.php";
require_once TEMPLATES_FOLDER."navbar.php";
require_once TEMPLATES_FOLDER."main.php"; 

//print "\n<img src='".$database->getImage()."'>\n";
Logger::printLog(DEBUG);
$error->printError(DEBUG);
require_once TEMPLATES_FOLDER."footer.php"; 