<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email          = $_POST["email"];
    $hashedPassword = $_POST["hashedPassword"];
        
    if ($email == null || $hashedPassword == null) {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }   

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);


    $responseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($responseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
