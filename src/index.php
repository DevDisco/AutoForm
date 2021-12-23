<?php

$navbar = new Navigation($config);
$editor = new Editor($database, $fields );

$editor->setPrefill();

if (empty(Session::getPrefill()) ){
    
    $formHtml = $form->createForm();
}
else{

    $formHtml = $form->createForm( "update" );
}


$editTable = $editor->showAll();

require_once TEMPLATES_FOLDER."header.php";
require_once TEMPLATES_FOLDER."navbar.php";
require_once TEMPLATES_FOLDER."main.php"; 

Logger::printLog(DEBUG);
$error->printError(DEBUG);
require_once TEMPLATES_FOLDER."footer.php"; 