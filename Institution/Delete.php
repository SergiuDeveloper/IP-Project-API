<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once("Role/Utility/InstitutionRoles.php");
    require_once("Utility/InstitutionCreation.php ");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email = $_POST["email"];
    $hashedPassword = $_POST["hashedPassword"];
    $institutionName = $_POST["institutionName"];

    if ($email == null || $hashedPassword == null || $institutionName == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
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

    $queryDeleteRoles = "DELETE FROM Institution_Roles Where Institution_ID = :institutionID;";
    $queryDeleteMembers = "DELETE FROM Institution_Members Where Institution_ID = :institutionID;";
    $queryDeleteAdresses = "DELETE FROM institution_addresses_list Where Institution_ID = :institutionID;";
    $queryDeleteInstitution = "DELETE FROM Institutions Where ID = :institutionID;";


    try {
        if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::DELETE_INSTITUTION) ){
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
    }
    catch (InstitutionRolesInvalidAction $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        http_response_code(StatusCodes::OK);
        echo json_encode($response), PHP_EOL;
        die();
        */
    }

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

        DatabaseManager::Connect();

        $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteRoles);
        $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
        $deleteInstitution->execute();

        $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteMembers);
        $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
        $deleteInstitution->execute();

        $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteAdresses);
        $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
        $deleteInstitution->execute();

        $deleteInstitution = DatabaseManager::PrepareStatement($queryDeleteInstitution);
        $deleteInstitution->bindParam(":institutionID", $institutionRow["ID"]);
        $deleteInstitution->execute();

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
