<?php

spl_autoload_register(function ($className) {
    $directories = array(
        dirname(dirname(__DIR__)) . '/app/core/',
        dirname(dirname(__DIR__)) . '/app/controllers/',
        dirname(dirname(__DIR__)) . '/app/models/'
    );

    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
