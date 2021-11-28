<?php

const DEBUG = true;

//A simple autoloader that grabs everything in /classes/, no need for composer
$classFolder = "../src/classes/";

if (is_dir($classFolder)) {

    $dir = new DirectoryIterator($classFolder);

    foreach ($dir as $fileinfo) {
        
        if (!$fileinfo->isDot() && $fileinfo->getExtension() === "php") {
            
            require_once($fileinfo->getPathname());
        }
    }
}
