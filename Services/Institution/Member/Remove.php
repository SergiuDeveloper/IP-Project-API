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

    $email = $_POST["email"];
    $hashedPassword = $_POST["hashedPassword"];
    $institutionName = $_POST["institutionName"];
    $memberEmail= $_POST["memberEmail"];


    if ($email == null || $hashedPassword == null || $institutionName == null || $memberEmail == null) {
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

    $queryCallerRight = "SELECT ri.Can_Remove_Members FROM Institution_Members i JOIN Users u ON i.User_ID = u.ID
    JOIN Institution_Roles r ON i.Institution_Roles_ID = r.ID
    JOIN institution_rights ri ON r.institution_rights_id = ri.ID
    WHERE i.Institution_ID = :institutionID AND u.Email = :callerEmail;";


    $queryInstitutionMember = "SELECT u.ID FROM Institution_Members i JOIN Users u ON i.User_ID = u.ID
    WHERE u.Email = :email AND i.Institution_ID = :institutionID;";

    $queryDeleteMember = "DELETE FROM Institution_Members WHERE user_id = :userID AND Institution_ID = :institutionID;";

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

        $getCaller = DatabaseManager::PrepareStatement($queryCallerRight);
        $getCaller->bindParam(":callerEmail", $email);
        $getCaller->bindParam(":institutionID", $institutionRow["ID"]);
        $getCaller->execute();

       /// InstitutionRoles::isUserAuthorized($username, $institutionName, InstitutionActions::DEASSIGN_ROLE);

        $callerRow = $getCaller->fetch(PDO::FETCH_ASSOC);

        if($callerRow == null || $callerRow["Can_Remove_Members"] != 1){
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

        $getMember = DatabaseManager::PrepareStatement($queryInstitutionMember);
        $getMember->bindParam(":email", $memberEmail);
        $getMember->bindParam(":institutionID", $institutionRow["ID"]);
        $getMember->execute();

        $memberRow = $getMember->fetch(PDO::FETCH_ASSOC);

        if($memberRow == null){
            DatabaseManager::Disconnect();
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("MEMBER_NOT_FOUND"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("MEMBER_NOT_FOUND");

            http_response_code(StatusCodes::OK);
            echo json_encode($response), PHP_EOL;
            die();
            */
        }

        $deleteMembers = DatabaseManager::PrepareStatement($queryDeleteMember);
        $deleteMembers->bindParam(":userID", $memberRow["ID"]);
        $deleteMembers->bindParam(":institutionID", $institutionRow["ID"]);
        $deleteMembers->execute();

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


    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
    /*
    $responseSuccess = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($responseSuccess), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */
