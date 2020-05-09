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

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email              = $_GET["email"];
    $hashedPassword     = $_GET["hashedPassword"];
    $institutionName    = $_GET["institutionName"];

    if ($email == null || $hashedPassword == null || $institutionName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryIdInstitution = "SELECT ID, Institution_Contact_Information_ID  FROM Institutions WHERE name = :institutionName;";

    $queryContact = "SELECT * FROM institution_contact_information WHERE ID = :contactId;";

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

        $contact = new Contact($contactRow['Email'],$contactRow['Phone_Number'],$contactRow['Fax']);

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
        ->addResponseData("Contact", $contact)
        ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

    class Contact{
        public $contactEmail;
        public $contactPhone;
        public $contactFax;
    
        function __construct($contactEmail, $contactPhone, $contactFax)
        {
            $this->contactEmail = $contactEmail;
            $this->contactPhone = $contactPhone;
            $this->contactFax   = $contactFax;
        }
    }
?>