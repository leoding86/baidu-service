<?php
namespace leoding86\BaiduService;

function autoload($classname) {
    $parts = explode('\\', $classname);
    require_once __DIR__ . '/' . array_pop($parts) . '.php';
}

spl_autoload_register(__NAMESPACE__ . '\autoload');