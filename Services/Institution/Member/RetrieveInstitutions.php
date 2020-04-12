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

    CommonEndPointLogic::ValidateHTTPGetRequest();

    $email          = $_GET["email"];
    $hashedPassword = $_GET["hashedPassword"];

    if(
        $email       == null ||
        $hashedPassword == null
    ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $getInstitutionsAndRolesForEmailStatement = "
        SELECT Name, Title FROM institution_members 
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
            $institutionRole = new InstitutionRole($row->Name, $row->Title);

            array_push($institutionRolesArray, $institutionRole);
        }

        DatabaseManager::Disconnect();
    }
    catch(Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
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

    /*
    $response = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($response),PHP_EOL;
    echo json_encode($institutionRolesArray), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */

    class InstitutionRole {
        public $institutionName;
        public $roleName;

        function __construct($institutionName, $roleName)
        {
                $this->institutionName = $institutionName;
                $this->roleName = $roleName;
        }
    }

?>