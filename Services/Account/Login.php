<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once (ROOT . '/Utility/Utilities.php');

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email          = $_POST["email"];
    $hashedPassword = $_POST["hashedPassword"];
        
    if ($email == null || $hashedPassword == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
            ->send(StatusCodes::BAD_REQUEST);
    }   

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    try {
        $apiKey = APIKeyHandler::getInstance()->setCredentials(new Credentials($email, $hashedPassword))->getAPIKey();
    } catch (APIKeyHandlerCredentialsUnbound $e) {
        ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNBOUND_CREDENTIALS_API_KEY"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("API_KEY", $apiKey)
            ->send();
    } catch (Exception $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

?>