<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");
    require_once(ROOT . "/Institution/Utility/InstitutionCreation.php");
    require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email                  = $_GET['email'];
    $hashedPassword         = $_GET['hashedPassword'];
    $institutionsPerPage    = $_GET['institutionsPerPage'];
    $pageNumber             = $_GET['pageNumber'] - 1;
    $orderByAsc             = $_GET['orderByAsc'];

    $apiKey                 = $_GET['apiKey'];

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
    }
    else{
        if(
            $email                  == null ||
            $hashedPassword         == null
        ){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIALS"))
                ->send(StatusCodes::BAD_REQUEST);
        }
        CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
    }

    if($institutionsPerPage == null)
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);

    if($orderByAsc == 0 || $orderByAsc == false || $orderByAsc == '0' || $orderByAsc == 'false'){
        $ascendant = 'desc';
    }
    else{
        $ascendant = 'asc';
    }

    if($orderByAsc == null)
        $ascendant = 'asc';

    if($pageNumber == null){
        $pageNumber = 0;
    }

    $offset = $pageNumber * $institutionsPerPage;

    if($offset < 0)
        $offset = 0;

    $fetchAllInstitutionsStatement = "SELECT ID, Name FROM institutions ORDER BY Name $ascendant LIMIT $institutionsPerPage OFFSET $offset" ;

    $institutionsArray = array();

    try{
        DatabaseManager::Connect();

        $SQLStatement = DatabaseManager::PrepareStatement($fetchAllInstitutionsStatement);
        $SQLStatement->execute();

        while($row = $SQLStatement->fetch(PDO::FETCH_OBJ)){
            array_push($institutionsArray, new InstitutionDAO($row->ID, $row->Name));
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
            ->addResponseData("institutions", $institutionsArray)
            ->send();
    }
    catch (Exception $exception) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(500);
    }

    class InstitutionDAO{
        public $name;
        public $ID;

        public function __construct($ID, $name){
            $this->name = $name;
            $this->ID = $ID;
        }
    }

?>
