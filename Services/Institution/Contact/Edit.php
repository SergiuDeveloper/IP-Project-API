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
    $contactEmail       = $_POST["contactEmail"];
    $contactPhone       = $_POST["contactPhone"];
    $contactFax         = $_POST["contactFax"];

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

    $queryContactEmail = "SELECT id FROM contact_email_adresses WHERE Institution_ID = :institutionId;";
    $queryContactPhone = "SELECT id FROM contact_phone_numbers WHERE Institution_ID = :institutionId;";
    $queryContactFax = "SELECT id FROM contact_fax_numbers WHERE Institution_ID = :institutionId;";

    $updateEmail = "UPDATE contact_email_adresses SET value = :email WHERE id = :contactId;";

    $updatePhone = "UPDATE contact_phone_numbers SET value = :phone WHERE id = :contactId;";

    $updateFax = "UPDATE contact_fax_numbers SET value = :fax WHERE id = :contactId;";

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

        if($contactEmail != null){
            $getEmail = DatabaseManager::PrepareStatement($queryContactEmail);
            $getEmail->bindParam(":institutionId", $institutionRow['ID']);
            $getEmail->execute();
    
            $emailRow = $getEmail->fetch(PDO::FETCH_ASSOC);
        }
       
        if($contactPhone != null){
            $getPhone = DatabaseManager::PrepareStatement($queryContactPhone);
            $getPhone->bindParam(":institutionId", $institutionRow['ID']);
            $getPhone->execute();
    
            $phoneRow = $getPhone->fetch(PDO::FETCH_ASSOC);    
        }

        if($contactFax != null){
            $getFax = DatabaseManager::PrepareStatement($queryContactFax);
            $getFax->bindParam(":institutionId", $institutionRow['ID']);
            $getFax->execute();
    
            $faxRow = $getFax->fetch(PDO::FETCH_ASSOC);
        }
       
        if($emailRow == null && $phoneRow == null && $faxRow == null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("CONTACT_NOT_FOUND"))
            ->send();
            DatabaseManager::Disconnect();
        }

        if($emailRow != null){
            $update = DatabaseManager::PrepareStatement($updateEmail);
            $update->bindParam(":email", $contactEmail);
            $update->bindParam(":contactId", $emailRow['ID']);
            $update->execute();
        }

        if($phoneRow != null){
            $update = DatabaseManager::PrepareStatement($updatePhone);
            $update->bindParam(":phone", $contactPhone);
            $update->bindParam(":contactId", $phoneRow['ID']);
            $update->execute();
        }

        if($faxRow != null){
            $update = DatabaseManager::PrepareStatement($updateFax);
            $update->bindParam(":fax", $contactPhone);
            $update->bindParam(":contactId", $faxRow['ID']);
            $update->execute();
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
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }
?>