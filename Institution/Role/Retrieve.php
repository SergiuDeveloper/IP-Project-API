<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");

    require_once("./Utility/InstitutionActions.php");
    require_once("./Utility/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email              = $_GET["email"];
    $hashedPassword     = $_GET["hashedPassword"];
    $institutionName    = $_GET["institutionName"];

    if ($email == null || $hashedPassword == null || $institutionName == null) {
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

    $queryGetRoles = "SELECT DISTINCT title FROM Institution_Roles
    WHERE Institution_ID = :institutionID;";

    $institutionRoles = array();
    try {
        DatabaseManager::Connect();

        $getInstitution = DatabaseManager::PrepareStatement($queryIdInstitution);
        $getInstitution->bindParam(":institutionName", $institutionName);
        $getInstitution->execute();

        $institutionRow = $getInstitution->fetch(PDO::FETCH_ASSOC);

        if($institutionRow == null){
            DatabaseManager::Disconnect();
            $response = CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND");

            http_response_code(StatusCodes::OK);
            echo json_encode($response), PHP_EOL;
            die();
        }

        if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::ASSIGN_ROLE)) {
            $response = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");
            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }

        DatabaseManager::Connect();
        $getRoles = DatabaseManager::PrepareStatement($queryGetRoles);
        $getRoles->bindParam(":institutionID",$institutionRow["ID"]);
        $getRoles->execute();

        while($getRolesRow = $getRoles->fetch(PDO::FETCH_ASSOC)){
            array_push($institutionRoles,$getRolesRow["title"]);
        }
        DatabaseManager::Disconnect();
    }
    catch (Exception $databaseException) {
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");
        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    $responseSuccess = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($responseSuccess), PHP_EOL;
    echo json_encode($institutionRoles), PHP_EOL;
    http_response_code(StatusCodes::OK);
