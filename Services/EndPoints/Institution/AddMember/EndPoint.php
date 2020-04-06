<?php

require_once("./../../../HelperClasses/CommonEndPointLogic.php");
require_once("./../../../HelperClasses/DatabaseManager.php");
require_once("./../../../HelperClasses/StatusCodes.php");
require_once("./../../../HelperClasses/Institution/InstitutionRoles.php");
require_once("./../../../HelperClasses/Institution/InstitutionActions.php");

$username = $_POST["username"];
$hashedPassword = $_POST["hashedPassword"];
$institutionName = $_POST["institutionName"];
$userIdentifier = $_POST["userIdentifier"];
$roleName = $_POST["roleName"];

CommonEndPointLogic::ValidateHTTPPOSTRequest();

if ($username == null || $hashedPassword == null || $institutionName == null || $userIdentifier == null || $roleName == null) {
    $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

    echo json_encode($failureResponse), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}

CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

$userCanAddMembers = InstitutionRoles::isUserAuthorized($username, $institutionName, InstitutionActions::ADD_MEMBERS);
if (!$userCanAddMembers) {
    $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("NOT_ENOUGH_RIGHTS");

    echo json_encode($failureResponse), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

$isUserIdentifierEmail = (strpos($userIdentifier, "@") !== false);

if ($isUserIdentifierEmail) {
    DatabaseManager::Connect();

    $getUsernameStatement = DatabaseManager::PrepareStatement("SELECT Username FROM Users WHERE Email = :email");
    $getUsernameStatement->bindParam(":email", $userIdentifier);
    $getUsernameStatement->execute();
    $userRow = $getUsernameStatement->fetch(PDO::FETCH_OBJ);

    if ($userRow == null) {
        $failureResponse = CommonEndPointLogic::GetFailureResponseStatus("USER_NOT_FOUND");
    
        echo json_encode($failureResponse), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    $userIdentifier = $userRow->Username;

    DatabaseManager::Disconnect();
}

InstitutionRoles::addAndAssignMemberToInstitution($userIdentifier, $institutionName, $roleName);

$successResponse = CommonEndPointLogic::GetSuccessResponseStatus();
echo json_encode($successResponse);
http_response_code(StatusCodes::OK);

?>