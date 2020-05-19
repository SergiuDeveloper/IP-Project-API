<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPGetRequest();

    $email          = $_GET["email"];
    $hashedPassword = $_GET["hashedPassword"];

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

    //CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $getInstitutionsAndRolesForEmailStatement = "
        SELECT institutions.ID, Name, Title FROM institution_members 
            JOIN users ON User_ID = users.ID 
            JOIN institution_roles ON institution_members.Institution_Roles_ID = institution_ROles.ID 
            JOIN institutions ON institutions.id = institution_members.Institution_ID 
            WHERE Email = :email
    ";

    $institutionRolesArray = array();

    try{
        DatabaseManager::Connect();

        $SQLStatement = DatabaseManager::PrepareStatement($getInstitutionsAndRolesForEmailStatement);
        $SQLStatement->bindParam(":email", $email);

        $SQLStatement->execute();

        while($row = $SQLStatement->fetch(PDO::FETCH_OBJ)){
            $institutionRole = new InstitutionRole($row->ID, $row->Name, $row->Title);

            array_push($institutionRolesArray, $institutionRole);
        }

        DatabaseManager::Disconnect();
    }
    catch(Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("institution", $institutionRolesArray)
            ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

    class InstitutionRole {
        public $institutionName;
        public $roleName;
        public $ID;

        function __construct($ID, $institutionName, $roleName)
        {
            $this->ID = $ID;
            $this->institutionName = $institutionName;
            $this->roleName = $roleName;
        }
    }

?>