<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/DatabaseManager.php");
    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $uniqueKey = $_GET["uniqueKey"];

    if ($uniqueKey == null)
    {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_ACTIVATION_KEY");
        echo json_encode($failureResponseStatus), PHP_EOL; 
        die();
    }

    $successfullyConnectedToDB = DatabaseManager::Connect();
    if(!$successfullyConnectedToDB){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");
        echo json_encode($failureResponseStatus), PHP_EOL; 
        die();
    }

    $IDAndPreparedFindStatement = "SELECT Users.ID, Is_Active FROM Users JOIN User_Activation_Keys ON Users.ID = User_Activation_Keys.User_ID WHERE Unique_Key = :uniqueKey;";

    $sqlStatementToExecute = DatabaseManager::PrepareStatement($IDAndPreparedFindStatement);

    $sqlStatementToExecute->bindParam(":uniqueKey", $uniqueKey);
    $sqlStatementToExecute->execute();

    $inactiveUserTableRow = $sqlStatementToExecute->fetch();

    if($inactiveUserTableRow == null){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NO_USER_FOR_KEY");
        echo json_encode($failureResponseStatus), PHP_EOL;
        die();
    }

    if($inactiveUserTableRow["Is_Active"]){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("USER_ALREADY_ACTIVE");
        echo json_encode($failureResponseStatus), PHP_EOL;
        die();
    }

    $updateUserActiveStatusPreparedStatement = "UPDATE Users SET Is_Active = 1 WHERE ID = :id";

    $sqlStatementToExecute = DatabaseManager::PrepareStatement($updateUserActiveStatusPreparedStatement);
    $sqlStatementToExecute->bindParam(":id", $inactiveUserTableRow['ID']);
    $sqlStatementToExecute->execute();
    DatabaseManager::Disconnect();

    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($successResponseStatus), PHP_EOL;

    http_response_code(StatusCodes::OK);
