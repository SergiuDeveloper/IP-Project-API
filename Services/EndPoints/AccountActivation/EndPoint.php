<?php

    require_once("../../HelperClasses/DatabaseManager.php");
    require_once("../../HelperClasses/CommonEndPointLogic.php");
    require_once("../../HelperClasses/ValidationHelper.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $uniqueKey = $_GET["Unique_Key"];

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

    /*$usernameFindStatement = "SELECT Username FROM users a, user_activation_keys b 
                            WHERE a.ID = b.ID AND b.Unique_Key = :uniqueKey;";

    $sqlStatementToExecute = DatabaseManager::PrepareStatement($usernameFindStatement);
    $sqlStatementToExecute->bindParam(":uniqueKey", $uniqueKey);
    $sqlStatementToExecute->execute();

    $username = $sqlStatementToExecute->fetch();

    $passwordFindStatement = "SELECT Hashed_Password FROM users a, user_activation_keys b 
                            WHERE a.ID = b.ID AND b.Unique_Key = :uniqueKey;";*/

    $IDAndPreparedFindStatement = "SELECT Users.ID, Is_Active FROM Users JOIN User_Activation_Keys ON Users.ID = User_Activation_Keys.User_ID WHERE Unique_Key = :uniqueKey;";

    

    $sqlStatementToExecute = DatabaseManager::PrepareStatement($IDAndPreparedFindStatement);
    

    $sqlStatementToExecute->bindParam(":uniqueKey", $uniqueKey);
    $sqlStatementToExecute->execute();

    $inactiveUserTableRow = $sqlStatementToExecute->fetch();

    /*
    $accountExists = UserValidation::ValidateCredentials($username, $hashedPassword);

    if ($accountExists != SuccessStates::USER_INACTIVE)
    {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("FAILED_TO_ACTIVATE_USER");
        echo json_encode($failureResponseStatus), PHP_EOL;
        die();
    }

    */

    if($inactiveUserTableRow == null){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NO_USER_FOR_KEY");
        echo json_encode($failureResponseStatus), PHP_EOL;
        die();
    }

    if($inactiveUserTableRow["Is_Active"]){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("FAILED_TO_ACTIVATE_USER");
        echo json_encode($failureResponseStatus), PHP_EOL;
        die();
    }

    /*$userActiveStatus = "UPDATE users a, user_activation_keys b SET a.Is_Active = 1 
                        WHERE a.ID = b.ID AND b.Unique_Key = :uniqueKey";*/

    $updateUserActiveStatusPreparedStatement = "UPDATE Users SET Is_Active = 1 WHERE ID = :id";

    $sqlStatementToExecute = DatabaseManager::PrepareStatement($updateUserActiveStatusPreparedStatement);
    $sqlStatementToExecute->bindParam(":id", $inactiveUserTableRow['ID']);
    $sqlStatementToExecute->execute();
    DatabaseManager::Disconnect();

    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus("USER_HAS_BEEN_ACTIVATED");
    echo json_encode($successResponseStatus), PHP_EOL;

    http_response_code(StatusCodes::OK);

?>