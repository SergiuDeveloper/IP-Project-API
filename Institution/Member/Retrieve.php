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

    $email = $_GET["email"];
    $hashedPassword = $_GET["hashedPassword"];
    $institutionName = $_GET["institutionName"];

    if ($email == null || $hashedPassword == null || $institutionName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

    $queryInstitutionMember = "SELECT * FROM Institution_Members i JOIN Users u ON i.User_ID = u.ID
     WHERE u.Email = :callerEmail AND i.Institution_ID = :institutionID;";

    $queryGetMembers = "SELECT u.Email, r.title FROM Institution_Members i JOIN Users u ON i.User_ID = u.ID
    JOIN Institution_Roles r ON i.Institution_Roles_ID = r.ID
    WHERE i.Institution_ID = :institutionID;";

    $institutionMembers = array();
    try {
        DatabaseManager::Connect();

        $getInstitution = DatabaseManager::PrepareStatement($queryIdInstitution);
        $getInstitution->bindParam(":institutionName", $institutionName);
        $getInstitution->execute();

        $institutionRow = $getInstitution->fetch(PDO::FETCH_ASSOC);

        if($institutionRow == null){
            DatabaseManager::Disconnect();

            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND");

            http_response_code(StatusCodes::OK);
            echo json_encode($response), PHP_EOL;
            die();
            */
        }

        $getCallerUser = DatabaseManager::PrepareStatement($queryInstitutionMember);
        $getCallerUser->bindParam(":callerEmail", $email);
        $getCallerUser->bindParam(":institutionID", $institutionRow["ID"]);
        $getCallerUser->execute();

        $callerUserRow = $getCallerUser->fetch(PDO::FETCH_OBJ);

        if($callerUserRow == null){
            DatabaseManager::Disconnect();

            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

            http_response_code(StatusCodes::OK);
            echo json_encode($response), PHP_EOL;
            die();
            */
        }


        $getMembers = DatabaseManager::PrepareStatement($queryGetMembers);
        $getMembers->bindParam(":institutionID", $institutionRow["ID"]);
        $getMembers->execute();

        while($getMembersRow = $getMembers->fetch(PDO::FETCH_ASSOC)){
            $member=new Member($getMembersRow["Email"],$getMembersRow["title"]);

            array_push($institutionMembers,$member);
        }
        DatabaseManager::Disconnect();
    }
    catch (Exception $databaseException) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");
        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("members", $institutionMembers)
            ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }
    /*
    $responseSuccess = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($responseSuccess), PHP_EOL;
    echo json_encode($institutionMembers), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */

    class Member
    {
        public $email;
        public $role;

        function __construct($email, $role)
        {
            $this->email = $email;
            $this->role = $role;
        }
    }
