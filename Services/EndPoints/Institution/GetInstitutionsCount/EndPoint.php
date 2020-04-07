<?php

require_once("./../../../HelperClasses/CommonEndPointLogic.php");
require_once("./../../../HelperClasses/DatabaseManager.php");
require_once("./../../../HelperClasses/StatusCodes.php");

$username = $_GET["username"];
$hashedPassword = $_GET["hashedPassword"];

CommonEndPointLogic::ValidateHTTPGETRequest();

if ($username == null || $hashedPassword == null) {
    $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL");

    echo json_encode($failureResponse), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}

CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

DatabaseManager::Connect();

$getInstitutionsCountStatement = DatabaseManager::PrepareStatement("SELECT COUNT(*) AS InstitutionsCount FROM Institutions");
$getInstitutionsCountStatement->execute();

$institutionsRow = $getInstitutionsCountStatement->fetch(PDO::FETCH_OBJ);
$institutionsCount = $institutionsRow->InstitutionsCount;
$institutionsCountObj = array(
    "institutionsCount" => $institutionsCount
);

$successResponse = CommonEndPointLogic::GetSuccessResponseStatus();

echo json_encode($successResponse), PHP_EOL;
echo json_encode($institutionsCountObj), PHP_EOL;
http_response_code(StatusCodes::OK);

DatabaseManager::Disconnect();

?>