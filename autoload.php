<?php

spl_autoload_register(function ($class) {
    $filePath = __DIR__ . '/classes/' . strtolower(str_replace(array("_", "\\"), "/", $class)) . '.php';
    if (file_exists ($filePath)) {
        include $filePath;
    }
});