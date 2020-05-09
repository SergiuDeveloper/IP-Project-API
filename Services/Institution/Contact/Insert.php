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

    $insertContact = "INSERT INTO institution_contact_information(Email,Phone_Number,Fax) VALUES(:email, :phone, :fax);";

    $getContactID = "SELECT id from institution_contact_information WHERE Email = :email, Phone_Number = :phone, Fax = :fax;";

    $updateContactID = "UPDATE institutions SET Institution_Contact_Information_ID = :contactId WHERE id = :institutionId;";

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
        
        $insert = DatabaseManager::PrepareStatement($insertContact);
        $insert->bindParam(":email", $contactEmail);
        $insert->bindParam(":phone", $contactPhone);
        $insert->bindParam(":fax", $contactFax);
        $insert->execute();

        $getId = DatabaseManager::PrepareStatement($getContactID);
        $getId->bindParam(":email", $contactEmail);
        $getId->bindParam(":phone", $contactPhone);
        $getId->bindParam(":fax", $contactFax);
        $getId->execute();

        $contactIdRow = $getId->fetch(PDO::FETCH_ASSOC);

        $getId = DatabaseManager::PrepareStatement($updateContactID);
        $getId->bindParam(":contactId", $contactIdRow['ID']);
        $getId->bindParam(":institutionId", $institutionRow['ID']);
        $getId->execute();

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