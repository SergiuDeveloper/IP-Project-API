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

    //CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

    $queryGetRoles = "SELECT Title, Institution_Rights_ID FROM Institution_Roles
    WHERE Institution_ID = :institutionID;";

    $queryGetRoleRights = "
        SELECT * FROM institution_rights WHERE ID = :ID
    ";

    $institutionRoles = array();
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
        }

        if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::ASSIGN_ROLE)) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
        }

        DatabaseManager::Connect();
        $getRoles = DatabaseManager::PrepareStatement($queryGetRoles);
        $getRoles->bindParam(":institutionID",$institutionRow["ID"]);
        $getRoles->execute();

        while($getRolesRow = $getRoles->fetch(PDO::FETCH_ASSOC)){
            array_push($institutionRoles, new RoleDAO($getRolesRow['Title'], $getRolesRow['Institution_Rights_ID']));
        }

        foreach($institutionRoles as $role){
            $rightsID = $role->getRightsID();

            $getRightsStatement = DatabaseManager::PrepareStatement($queryGetRoleRights);
            $getRightsStatement->bindParam(":ID", $rightsID);
            $getRightsStatement->execute();

            $role->setRights($getRightsStatement->fetch(PDO::FETCH_ASSOC));
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
            ->addResponseData("roles", $institutionRoles)
            ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

    class RoleDAO{
        public $name;
        private $rightsID;
        public $rights;

        public function __construct($name, $ID){
            $this->rightsID = $ID;
            $this->name = $name;
            $this->rights = null;
        }

        public function setRights($rights){
            $this->rights = $rights;
        }

        /**
         * @return int
         */
        public function getRightsID(){
            return $this->rightsID;
        }
    }
?>