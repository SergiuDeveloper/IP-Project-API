<?php

require_once("DatabaseManager.php");
require_once("CommonEndPointLogic.php");
require_once("ValidationHelper.php");

CommonEndPointLogic::ValidateHTTPGETRequest();

$uniqueKey = $_GET["Unique_Key"];

if ($uniqueKey == null)
{
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_ACTIVATION_KEY");
    echo json_encode($failureResponseStatus), PHP_EOL; 
    die();
}

$successfullyConnectedToDB = DatabaseManager::Connect();
echo json_encode($successfullyConnectedToDB ? "Successfully connected to DB" : "Could not connect to DB"), PHP_EOL;

$usernameFindStatement = "SELECT Username FROM users a, user_activation_keys b 
                          WHERE a.ID = b.ID AND b.Unique_Key = :uniqueKey;";

$sqlStatementToExecute = DatabaseManager::PrepareStatement($usernameFindStatement);
$sqlStatementToExecute->bindParam(":uniqueKey", $uniqueKey);
$sqlStatementToExecute->execute();

$username = $sqlStatementToExecute->fetch();

$passwordFindStatement = "SELECT Hashed_Password FROM users a, user_activation_keys b 
                          WHERE a.ID = b.ID AND b.Unique_Key = :uniqueKey;";

$sqlStatementToExecute = DatabaseManager::PrepareStatement($passwordFindStatement);
$sqlStatementToExecute->bindParam(":uniqueKey", $uniqueKey);
$sqlStatementToExecute->execute();

$hashedPassword = $sqlStatementToExecute->fetch();

$accountExists = ValidationHelper::ValidateCredentials($username, $hashedPassword);

if ($accountExists != SuccessStates::USER_INACTIVE)
{
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("FAILED_TO_ACTIVATE_USER");
    echo json_encode($failureResponseStatus), PHP_EOL;
    die();
}


$userActiveStatus = "UPDATE users a, user_activation_keys b SET a.Is_Active = 1 
                     WHERE a.ID = b.ID AND b.Unique_Key = :uniqueKey";

$sqlStatementToExecute = DatabaseManager::PrepareStatement($userActiveStatus);
$sqlStatementToExecute->bindParam(":uniqueKey", $uniqueKey);
$sqlStatementToExecute->execute();

$successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus("USER_HAS_BEEN_ACTIVATED");
echo json_encode($successResponseStatus), PHP_EOL;

http_response_code(StatusCodes::OK);

?>