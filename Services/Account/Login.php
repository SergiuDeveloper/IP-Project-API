<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email          = $_POST["email"];
    $hashedPassword = $_POST["hashedPassword"];
        
    if ($email == null || $hashedPassword == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }   

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

    /*
    $responseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($responseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */
