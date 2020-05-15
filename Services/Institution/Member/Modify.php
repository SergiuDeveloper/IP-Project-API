<?php

if(!defined('ROOT'))
    define('ROOT', dirname(__FILE__) . '/../..');

require_once (ROOT . '/Utility/Utilities.php');
require_once (ROOT . '/Institution/Role/Utility/InstitutionRoles.php');
require_once (ROOT . '/Institution/Role/Utility/InstitutionActions.php');

CommonEndPointLogic::ValidateHTTPPOSTRequest();

$email = $_POST["email"];
$hashedPassword = $_POST["hashedPassword"];
$institutionName = $_POST["institutionName"];
$memberEmail = $_POST["memberEmail"];
$newRole = $_POST["newRole"];

$apiKey = $_POST["apiKey"];

if($apiKey != null){
    try {
        $credentials = APIKeyHandler::getInstance()->setAPIKey($apiKey)->getCredentials();
    } catch (APIKeyHandlerKeyUnbound $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNBOUND_KEY"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    } catch (APIKeyHandlerAPIKeyInvalid $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_KEY"))
            ->send();
    }

    $email = $credentials->getEmail();
    //$hashedPassword = $credentials->getHashedPassword();
} else {
    if ($email == null || $hashedPassword == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
            ->send(StatusCodes::BAD_REQUEST);
    }
    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
}

if ($institutionName == null || $memberEmail == null || $newRole == null) {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
        ->send();
}

//CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

try {
    $userHasRights = InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::ASSIGN_ROLE);
} catch (InstitutionRolesInvalidAction $e) {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
        ->send();
}

if (!$userHasRights) {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
        ->send();
}

DatabaseManager::Connect();

$getRoleIDStatement = DatabaseManager::PrepareStatement("SELECT ID FROM Institution_Roles WHERE Title = :title");
$getRoleIDStatement->bindParam(":title", $newRole);
$getRoleIDStatement->execute();
$roleRow = $getRoleIDStatement->fetch(PDO::FETCH_OBJ);

if ($roleRow == null) {
    DatabaseManager::Disconnect();

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_NOT_FOUND"))
        ->send();
}

$roleID = $roleRow->ID;

$getUserIDStatement = DatabaseManager::PrepareStatement("SELECT ID FROM users WHERE Email = :email");
$getUserIDStatement->bindParam(":email", $memberEmail);
$getUserIDStatement->execute();

$userID = $getUserIDStatement->fetch(PDO::FETCH_OBJ);

$changeRoleStatement = DatabaseManager::PrepareStatement("UPDATE Institution_Members SET Institution_Roles_ID = :roleID WHERE User_ID = :userID");
$changeRoleStatement->bindParam(":roleID", $roleID);
$changeRoleStatement->bindParam(":userID", $userID->ID);
$changeRoleStatement->execute();

DatabaseManager::Disconnect();

ResponseHandler::getInstance()
    ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
    ->send();

?>