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

    $queryIdInstitution = "SELECT ID, Institution_Contact_Information_ID  FROM Institutions WHERE name = :institutionName;";

    $queryContact = "SELECT ID FROM institution_contact_information WHERE ID = :contactId;";

    $updateEmail = "UPDATE institution_contact_information SET Email = :email WHERE ID = :contactId;";

    $updatePhone = "UPDATE institution_contact_information SET Phone_Number = :phone WHERE ID = :contactId;";

    $updateFax = "UPDATE institution_contact_information SET Fax = :fax WHERE ID = :contactId;";

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

        if($institutionRow['Institution_Contact_Information_ID'] == null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("CONTACT_NOT_FOUND"))
            ->send();
        }

        DatabaseManager::Connect();

        $getContact = DatabaseManager::PrepareStatement($queryContact);
        $getContact->bindParam(":contactId", $institutionRow['Institution_Contact_Information_ID']);
        $getContact->execute();

        $contactRow = $getContact->fetch(PDO::FETCH_ASSOC);

        if($contactRow == null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("CONTACT_NOT_FOUND"))
            ->send();
            DatabaseManager::Disconnect();
        }

        if($contactEmail != null){
            $update = DatabaseManager::PrepareStatement($updateEmail);
            $update->bindParam(":email", $contactEmail);
            $update->bindParam(":contactId", $contactRow['ID']);
            $update->execute();
        }

        if($contactPhone != null){
            $update = DatabaseManager::PrepareStatement($updatePhone);
            $update->bindParam(":phone", $contactPhone);
            $update->bindParam(":contactId", $contactRow['ID']);
            $update->execute();
        }

        if($contactFax != null){
            $update = DatabaseManager::PrepareStatement($updateFax);
            $update->bindParam(":fax", $contactPhone);
            $update->bindParam(":contactId", $contactRow['ID']);
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