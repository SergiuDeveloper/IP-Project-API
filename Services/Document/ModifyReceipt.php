<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . '/..');
}

    require_once (ROOT . '/Document/Utility/Document.php');
    require_once (ROOT . '/DataAccessObject/DataObjects.php');
    require_once (ROOT . '/Utility/Utilities.php');

    CommonEndPointLogic::ValidateHTTPPOSTRequest();


?>
