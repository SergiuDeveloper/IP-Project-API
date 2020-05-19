<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email              = $_GET["email"];
    $hashedPassword     = $_GET["hashedPassword"];
    $institutionName    = $_GET["institutionName"];

    $apiKey = $_GET["apiKey"];

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

    $queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

    //$queryContactEmail = "SELECT id FROM contact_email_addresses WHERE Institution_ID = :institutionId;";
    //$queryContactPhone = "SELECT id FROM contact_phone_numbers WHERE Institution_ID = :institutionId;";
    //$queryContactFax = "SELECT id FROM contact_fax_numbers WHERE Institution_ID = :institutionId;";
    
    $queryContactEmail = "SELECT Value FROM contact_email_addresses WHERE Institution_ID = :institutionId;";
    $queryContactPhone = "SELECT Value FROM contact_phone_numbers WHERE Institution_ID = :institutionId;";
    $queryContactFax = "SELECT Value FROM contact_fax_numbers WHERE Institution_ID = :institutionId;";

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

        $emailArray = array();
        $phoneArray = array();
        $faxArray = array();

        DatabaseManager::Connect();

        $getEmail = DatabaseManager::PrepareStatement($queryContactEmail);
        $getEmail->bindParam(":institutionId", $institutionRow['ID']);
        $getEmail->execute();

        while($row = $getEmail->fetch(PDO::FETCH_OBJ)){
            array_push($emailArray, $row->Value);
        }
        //$emailRow = $getEmail->fetch(PDO::FETCH_ASSOC);

        $getPhone = DatabaseManager::PrepareStatement($queryContactPhone);
        $getPhone->bindParam(":institutionId", $institutionRow['ID']);
        $getPhone->execute();

        while($row = $getPhone->fetch(PDO::FETCH_OBJ)){
            array_push($phoneArray, $row->Value);
        }

        //$phoneRow = $getPhone->fetch(PDO::FETCH_ASSOC);

        $getFax = DatabaseManager::PrepareStatement($queryContactFax);
        $getFax->bindParam(":institutionId", $institutionRow['ID']);
        $getFax->execute();

        while($row = $getFax->fetch(PDO::FETCH_OBJ)){
            array_push($faxArray, $row->Value);
        }

        //$faxRow = $getFax->fetch(PDO::FETCH_ASSOC);

        /*if($emailRow == null && $phoneRow == null && $faxRow == null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("CONTACT_NOT_FOUND"))
            ->send();
            DatabaseManager::Disconnect();
        }*/

        //$contact = new Contact($emailRow['value'],$phoneRow['value'],$faxRow['value']);

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
        ->addResponseData("emailContacts", $emailArray)
        ->addResponseData("phoneContacts", $phoneArray)
        ->addResponseData("faxContacts", $faxArray)
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