<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/DatabaseManager.php");
    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $uniqueKey = $_GET["uniqueKey"];

    if ($uniqueKey == null)
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_ACTIVATION_KEY"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_ACTIVATION_KEY");
        echo json_encode($failureResponseStatus), PHP_EOL; 
        die();
        */
    }

    $successfullyConnectedToDB = DatabaseManager::Connect();
    if(!$successfullyConnectedToDB){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");
        echo json_encode($failureResponseStatus), PHP_EOL; 
        die();
        */
    }

    $IDAndPreparedFindStatement = "SELECT Users.ID, Is_Active FROM Users JOIN User_Activation_Keys ON Users.ID = User_Activation_Keys.User_ID WHERE Unique_Key = :uniqueKey;";

    $sqlStatementToExecute = DatabaseManager::PrepareStatement($IDAndPreparedFindStatement);

    $sqlStatementToExecute->bindParam(":uniqueKey", $uniqueKey);
    $sqlStatementToExecute->execute();

    $inactiveUserTableRow = $sqlStatementToExecute->fetch();

    if($inactiveUserTableRow == null){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NO_USER_FOR_KEY"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NO_USER_FOR_KEY");
        echo json_encode($failureResponseStatus), PHP_EOL;
        die();
        */
    }

    if($inactiveUserTableRow["Is_Active"]){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("USER_ALREADY_ACTIVE"))
            ->send();
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("USER_ALREADY_ACTIVE");
        echo json_encode($failureResponseStatus), PHP_EOL;
        die();
        */
    }

    $updateUserActiveStatusPreparedStatement = "UPDATE Users SET Is_Active = 1 WHERE ID = :id";

    $sqlStatementToExecute = DatabaseManager::PrepareStatement($updateUserActiveStatusPreparedStatement);
    $sqlStatementToExecute->bindParam(":id", $inactiveUserTableRow['ID']);
    $sqlStatementToExecute->execute();
    DatabaseManager::Disconnect();

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

    /*
    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($successResponseStatus), PHP_EOL;

    http_response_code(StatusCodes::OK);
    */
