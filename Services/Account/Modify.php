<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once("./Utility/ModifyAccountManager.php");
    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $inputEmail                 = $_POST["email"];
    $inputCurrentHashedPassword = $_POST["currentHashedPassword"];
    $inputNewHashedPassword     = $_POST["newHashedPassword"];
    $inputNewFirstName          = $_POST["newFirstName"];
    $inputNewLastName           = $_POST["newLastName"];

    if ($inputEmail == null || $inputCurrentHashedPassword == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("BAD_USERNAME_PASSWORD");
        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }

    $hashedPassword = password_hash($inputNewHashedPassword, PASSWORD_BCRYPT);

    CommonEndPointLogic::ValidateUserCredentials($inputEmail, $inputCurrentHashedPassword);

    $userRow = ModifyAccountManager::fetchIDAndHashedPassword($inputEmail);

    ModifyAccountManager::updateFieldsInDatabase(
        $userRow["ID"],
        $hashedPassword,
        $inputNewFirstName,
        $inputNewLastName
    );


    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

    /*
    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */
?>