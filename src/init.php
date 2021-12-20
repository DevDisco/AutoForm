<?php

//folders
const SOURCE_FOLDER = "../../../src/";
const CLASS_FOLDER = SOURCE_FOLDER."classes/";
const APP_FOLDER = "../";
const TEMPLATES_FOLDER = SOURCE_FOLDER."templates/";

//config files
const CONFIG_FILE_DEV = "../config.json";
const CONFIG_FILE_PROD = "../config.prod.json";

//file uploads
const UPL_NONE = 0;
const UPL_KEEP = 1;
const UPL_DELETE = 2;
const UPL_DELETE_UPLOAD  = 3;
const UPL_UPLOAD = 4;

//A simple autoloader that grabs everything in /classes/, no need for composer
if (is_dir(CLASS_FOLDER)) {

    $dir = new DirectoryIterator(CLASS_FOLDER);

    foreach ($dir as $fileinfo) {
        
        if (!$fileinfo->isDot() && $fileinfo->getExtension() === "php") {
            
            require_once($fileinfo->getPathname());
        }
    }
}

//call classes used in both index.php and process.php
$session = new Session();
$config = new Config();
$error = new SimpleError();
$database = new Database($config, $error);
$fields = new Fields($database);
$form = new AutoForm($fields);


//bit hacky but it will do for now
$allowedIps = $config->ALLOWED_IP ?? false;
if ( $allowedIps && !in_array($_SERVER['REMOTE_HOST'], $allowedIps)){
    
    print "You're not allowed to use these pages.";
    exit;
}