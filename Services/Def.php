<?php

define ('ROOT', dirname(__FILE__));

require_once (ROOT . '/Utility/DebugHandler.php');

$var1 = 1;
$var2 = 'plm';
$var3 = true;

DebugHandler::getInstance()
    ->setSource(basename(__FILE__))
    ->setDebugMessage('TEST FOR SHIT')
    ->setLineNumber(__LINE__)
    ->addDebugVars($var1, 'var1')
    ->addDebugVars($var2)
    ->addDebugVars($var3, 'var3')
    ->debugEcho();

?>