<?php

    if(!defined('ROOT'))
    {
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once("Utility/InstitutionValidator.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $institutionName = $_GET["institutionName"];

    if ($institutionName == null)
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    InstitutionValidator::validateInstitution($institutionName);

    $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

    $queryAddressList = "SELECT Address_ID FROM institution_addresses_list WHERE Institution_ID = :institutionID";

    $queryAllAddresses = "SELECT * FROM addresses WHERE ID = :id";

    $addressArray = array();

    try
    {
        DatabaseManager::Connect();

        $sqlStatement = DatabaseManager::PrepareStatement($queryAddressList);
        $sqlStatement->bindParam("institutionID", $institutionID);
        $sqlStatement->execute();

        $addressIDs = $sqlStatement->fetch(PDO::FETCH_ASSOC);

        foreach ($addressIDs as $addressID)
        {
            $sqlStatement = DatabaseManager::PrepareStatement($queryAllAddresses);
            $sqlStatement->bindParam(":id", $addressID["ID"]);
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
    catch(ResponseHandlerDuplicateLabel $e)
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    } 

?>
