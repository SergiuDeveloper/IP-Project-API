<?php

if(!defined('ROOT'))
{
    define('ROOT', dirname(__FILE__) . '/..');
}

require_once(ROOT . "/Utility/Utilities.php");

require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");

CommonEndPointLogic::ValidateHTTPGETRequest();

$email              = $_GET['email'];
$hashedPassword     = $_GET['hashedPassword'];
$institutionName    = $_GET["institutionName"];

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

if ($institutionName    == null) {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
        ->send(StatusCodes::BAD_REQUEST);
}

InstitutionValidator::validateInstitution($institutionName);

$institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

$queryAddressList = "SELECT Address_ID FROM institution_addresses_list WHERE Institution_ID = :institutionID";

$queryAllAddresses = "SELECT * FROM addresses WHERE ID = :ID";

$addressArray = array();

try
{
    DatabaseManager::Connect();

    $sqlStatement = DatabaseManager::PrepareStatement($queryAddressList);
    $sqlStatement->bindParam("institutionID", $institutionID);
    $sqlStatement->execute();

    while($addressRow = $sqlStatement->fetch(PDO::FETCH_ASSOC)){
        $sqlStatement = DatabaseManager::PrepareStatement($queryAllAddresses);
        $sqlStatement->bindParam(":ID", $addressRow["Address_ID"]);
        $sqlStatement->execute();

        $addressRow = $sqlStatement->fetch(PDO::FETCH_ASSOC);

        array_push($addressArray, $addressRow);
    }
}
catch (Exception $databaseException)
{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
        ->send();
}

try
{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->addResponseData("addresses", $addressArray)
        ->send();
}
catch(Exception $e)
{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
}

?>
