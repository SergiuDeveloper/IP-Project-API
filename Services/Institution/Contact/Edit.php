<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email              = $_POST["email"];
    $hashedPassword     = $_POST["hashedPassword"];
    $institutionName    = $_POST["institutionName"];
    $contactEmail       = json_decode($_POST["contactEmail"], true);
    $contactPhone       = json_decode($_POST["contactPhone"], true);
    $contactFax         = json_decode($_POST["contactFax"], true);

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

    if ($institutionName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    if($contactEmail == null && $contactPhone == null && $contactFax == null){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    try {
        if (false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::MODIFY_INSTITUTION)){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
        }
    } catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
    }

    $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

    try {
        DatabaseManager::Connect();

        $clearEmailTableStatement = DatabaseManager::PrepareStatement("DELETE FROM contact_email_addresses WHERE Institution_ID = :institutionID");
        $clearPhoneTableStatement = DatabaseManager::PrepareStatement("DELETE FROM contact_phone_numbers WHERE Institution_ID = :institutionID");
        $clearFaxTableStatement = DatabaseManager::PrepareStatement("DELETE FROM contact_fax_numbers WHERE Institution_ID = :institutionID");

        $clearEmailTableStatement->bindParam(":institutionID", $institutionID);
        $clearPhoneTableStatement->bindParam(":institutionID", $institutionID);
        $clearFaxTableStatement->bindParam(":institutionID", $institutionID);

        $clearEmailTableStatement->execute();
        $clearPhoneTableStatement->execute();
        $clearFaxTableStatement->execute();

        foreach ($contactEmail as $emailItem){
            $insertEmailStatement = DatabaseManager::PrepareStatement("INSERT INTO contact_email_addresses (Value, Institution_ID) VALUES (:email, :institutionID)");
            $insertEmailStatement->bindParam(":email", $emailItem);
            $insertEmailStatement->bindParam(":institutionID", $institutionID);
            $insertEmailStatement->execute();
        }

        foreach ($contactPhone as $phoneItem){
            $insertEmailStatement = DatabaseManager::PrepareStatement("INSERT INTO contact_phone_numbers (Value, Institution_ID) VALUES (:phone, :institutionID)");
            $insertEmailStatement->bindParam(":phone", $phoneItem);
            $insertEmailStatement->bindParam(":institutionID", $institutionID);
            $insertEmailStatement->execute();
        }

        foreach ($contactFax as $faxItem){
            $insertEmailStatement = DatabaseManager::PrepareStatement("INSERT INTO contact_fax_numbers (Value, Institution_ID) VALUES (:fax, :institutionID)");
            $insertEmailStatement->bindParam(":fax", $faxItem);
            $insertEmailStatement->bindParam(":institutionID", $institutionID);
            $insertEmailStatement->execute();
        }

        DatabaseManager::Disconnect();
    } catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }
?>