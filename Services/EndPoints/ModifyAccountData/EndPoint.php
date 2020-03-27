<?php

    /**
     * TODO : Document
     */
    
    require_once("ModifyAccountDataFunctions.php");
    require_once("../../HelperClasses/CommonEndPointLogic.php");
    require_once("../../HelperClasses/StatusCodes.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $inputUsername              = $_GET["username"];
    $inputCurrentHashedPassword = $_GET["currentHashedPassword"];
    $inputNewHashedPassword     = $_GET["newHashedPassword"];
    $inputNewFirstName          = $_GET["newFirstName"];
    $inputNewLastName           = $_GET["newLastName"];

    if ($inputUsername == null || $inputCurrentHashedPassword == null) {
        CommonEndPointLogic::GetFailureResponseStatus("BAD_USERNAME_PASSWORD");
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    $userRow = ModifyAccountManager::fetchIDandHashedPassword($inputUsername);

    $response = ModifyAccountManager::prepareResponse($userRow, $inputCurrentHashedPassword);

    if ($response["status"] == "SUCCESS")
        ModifyAccountManager::updateFieldsInDatabase(
            $userRow["ID"],
            $inputNewHashedPassword,
            $inputNewFirstName,
            $inputNewLastName
        );

    echo json_encode($response), PHP_EOL;
    http_response_code(StatusCodes::OK);
?>