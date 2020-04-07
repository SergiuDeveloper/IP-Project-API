<?php

require_once("./../../../HelperClasses/CommonEndPointLogic.php");
require_once("./../../../HelperClasses/DatabaseManager.php");
require_once("./../../../HelperClasses/StatusCodes.php");
require_once("./../../../HelperClasses/Institution/InstitutionRoles.php");
require_once("./../../../HelperClasses/Institution/InstitutionActions.php");

$username = $_POST["username"];
$hashedPassword = $_POST["hashedPassword"];
$institutionName = $_POST["institutionName"];
$memberUsername = $_POST["memberUsername"];
$newRole = $_POST["newRole"];

CommonEndPointLogic::ValidateHTTPPOSTRequest();

if ($username == null || $hashedPassword == null || $institutionName == null || $memberUsername == null || $newRole == null) {
    $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

    echo json_encode($failureResponse), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}

CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

$userHasRights = InstitutionRoles::isUserAuthorized($username, $institutionName, InstitutionActions::ASSIGN_ROLE);
if (!$userHasRights) {
    $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("NOT_ENOUGH_RIGHTS");

    echo json_encode($failureResponse), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

DatabaseManager::Connect();

$getRoleIDStatement = DatabaseManager::PrepareStatement("SELECT ID FROM Institution_Roles WHERE Title = :title");
$getRoleIDStatement->bindParam(":title", $newRole);
$getRoleIDStatement->execute();
$roleRow = $getRoleIDStatement->fetch(PDO::FTECH_OBJ);
if ($roleRow == null) {
    DatabaseManager::Disconnect();

    $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("ROLE_NOT_FOUND");

    echo json_encode($failureResponse), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

$roleID = $roleRow->ID;

$changeRoleStatement = DatabaseManager::PrepareStatement("UPDATE Institution_Members SET Institution_Roles_ID = :roleID");
$changeRoleStatement->bindParam(":roleID", $roleID);
$changeRoleStatement->execute();

DatabaseManager::Disconnect();

$successResponse = CommonEndPointLogic::GetSuccessResponseStatus();

echo json_encode($successResponse), PHP_EOL;
http_response_code(StatusCodes::OK);

?>