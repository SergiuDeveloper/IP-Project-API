<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . '/..');
}

require_once(ROOT . "/Utility/Utilities.php");

require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");
require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
require_once(ROOT . "/Institution/Utility/InstitutionCreation.php");
require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");

CommonEndPointLogic::ValidateHTTPPOSTRequest();

$email = $_POST["email"];
$hashedPassword = $_POST["hashedPassword"];
$institutionName = $_POST["institutionName"];

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

if($institutionName == null)
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
        ->send(StatusCodes::BAD_REQUEST);

$queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

$queryDeleteRoles = "DELETE FROM Institution_Roles Where Institution_ID = :institutionID;";
$queryDeleteMembers = "DELETE FROM Institution_Members Where Institution_ID = :institutionID;";
$queryDeleteAdresses = "DELETE FROM institution_addresses_list Where Institution_ID = :institutionID;";
$queryDeleteInstitution = "DELETE FROM Institutions Where ID = :institutionID;";

try {
    if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::DELETE_INSTITUTION) ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
            ->send();
    }
}
catch (InstitutionRolesInvalidAction $e) {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
        ->send();
}

try {
    DatabaseManager::Connect();

    $getInstitution = DatabaseManager::PrepareStatement($queryIdInstitution);
    $getInstitution->bindParam(":institutionName", $institutionName);
    $getInstitution->execute();

    $institutionRow = $getInstitution->fetch(PDO::FETCH_ASSOC);

    if($institutionRow == null){
        DatabaseManager::Disconnect();

        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
            ->send();
    }

    DatabaseManager::Connect();

    $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteRoles);
    $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteInstitution->execute();

    $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteMembers);
    $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteInstitution->execute();

    $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteAdresses);
    $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteInstitution->execute();

    $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteInstitution);
    $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
    $deleteInstitution->execute();

    DatabaseManager::Disconnect();
}
catch (Exception $databaseException) {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
        ->send();
}

ResponseHandler::getInstance()
    ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
    ->send();

?>