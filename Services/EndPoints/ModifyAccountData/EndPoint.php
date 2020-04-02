<?php

    /**
     * TODO : Document
     */
    
    require_once("ModifyAccountDataFunctions.php");
    require_once("../../HelperClasses/CommonEndPointLogic.php");
    require_once("../../HelperClasses/StatusCodes.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $inputUsername              = $_POST["username"];
    $inputCurrentHashedPassword = $_POST["currentHashedPassword"];
    $inputNewHashedPassword     = $_POST["newHashedPassword"];
    $inputNewFirstName          = $_POST["newFirstName"];
    $inputNewLastName           = $_POST["newLastName"];

    if ($inputUsername == null || $inputCurrentHashedPassword == null) {
        $response = CommonEndPointLogic::GetFailureResponseStatus("BAD_USERNAME_PASSWORD");
        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($inputUsername, $inputCurrentHashedPassword);

    $userRow = ModifyAccountManager::fetchIDandHashedPassword($inputUsername);

    ModifyAccountManager::updateFieldsInDatabase(
        $userRow["ID"],
        $inputNewHashedPassword,
        $inputNewFirstName,
        $inputNewLastName
    );

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    http_response_code(StatusCodes::OK);
?>
