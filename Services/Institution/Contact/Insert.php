<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email              = $_POST["email"];
    $hashedPassword     = $_POST["hashedPassword"];
    $institutionName    = $_POST["institutionName"];
    $contactEmail       = json_decode($_POST["contactEmail"], true);
    $contactPhone       = json_decode($_POST["contactPhone"], true);
    $contactFax         = json_decode($_POST["contactFax"], true);

    if ($email == null || $hashedPassword == null || $institutionName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    if($contactEmail == null && $contactPhone == null && $contactFax == null){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

    $insertContactEmail = "INSERT INTO contact_email_addresses(Value,Institution_ID) VALUES(:value, :institutionId);";
    $insertContactPhone = "INSERT INTO contact_phone_numbers(Value,Institution_ID) VALUES(:value, :institutionId);";
    $insertContactFax = "INSERT INTO contact_fax_numbers(Value,Institution_ID) VALUES(:value, :institutionId);";

    try {
        DatabaseManager::Connect();

        $getInstitution = DatabaseManager::PrepareStatement($queryIdInstitution);
        $getInstitution->bindParam(":institutionName", $institutionName);
        $getInstitution->execute();

        $institutionRow = $getInstitution->fetch(PDO::FETCH_ASSOC);

        if($institutionRow == null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
                ->send();
            DatabaseManager::Disconnect();
        }

        if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::MODIFY_INSTITUTION)) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
        }

        DatabaseManager::Connect();

        if($contactEmail != null)
        {
            foreach ($contactEmail as $contactEmailItem) {
                $insert = DatabaseManager::PrepareStatement($insertContactEmail);
                $insert->bindParam(":value", $contactEmailItem);
                $insert->bindParam(":institutionId", $institutionRow['ID']);
                $insert->execute();
            }
        }
        if($contactPhone != null)
        {
            foreach ($contactPhone as $contactPhoneItem) {
                $insert = DatabaseManager::PrepareStatement($insertContactPhone);
                $insert->bindParam(":value", $contactPhoneItem);
                $insert->bindParam(":institutionId", $institutionRow['ID']);
                $insert->execute();
            }
        }
        if($contactFax != null)
        {
            foreach($contactFax as $contactFaxItem) {
                $insert = DatabaseManager::PrepareStatement($insertContactFax);
                $insert->bindParam(":value", $contactFaxItem);
                $insert->bindParam(":institutionId", $institutionRow['ID']);
                $insert->execute();
            }
        }

        DatabaseManager::Disconnect();
    }
    catch (Exception $databaseException) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->send();
    } catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }
    
?>