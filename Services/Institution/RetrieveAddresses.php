<?php

if(!defined('ROOT'))
{
    define('ROOT', dirname(__FILE__) . '/..');
}

require_once(ROOT . "/Utility/CommonEndPointLogic.php");
require_once(ROOT . "/Utility/UserValidation.php");
require_once(ROOT . "/Utility/StatusCodes.php");
require_once(ROOT . "/Utility/SuccessStates.php");
require_once(ROOT . "/Utility/ResponseHandler.php");

require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");

CommonEndPointLogic::ValidateHTTPGETRequest();

$email              = $_GET['email'];
$hashedPassword     = $_GET['hashedPassword'];
$institutionName    = $_GET["institutionName"];

if (
    $institutionName    == null ||
    $email              == null ||
    $hashedPassword     == null
)
{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
        ->send(StatusCodes::BAD_REQUEST);
}

CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

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
        ->addResponseData("Addresses", $addressArray)
        ->send();
}
catch(Exception $e)
{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
}

?>
