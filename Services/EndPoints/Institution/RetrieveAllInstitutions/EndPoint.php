<?php

require_once("./../../../HelperClasses/CommonEndPointLogic.php");
require_once("./../../../HelperClasses/DatabaseManager.php");
require_once("./../../../HelperClasses/StatusCodes.php");
require_once("./../../../HelperClasses/Institution/InstitutionRoles.php");
require_once("./../../../HelperClasses/Institution/InstitutionActions.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $username           = $_GET['username'];
    $hashedPassword     = $_GET['hashedPassword'];
    $institutionCount   = $_GET['institutionsCount'];

    if(
        $username == null ||
        $hashedPassword == null ||
        $institutionCount == null
    ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    $fetchAllInstitutionsStatement = "SELECT Name FROM institutions LIMIT $institutionCount";

    $institutionsArray = array();

    try{
        DatabaseManager::Connect();

        $SQLStatement = DatabaseManager::PrepareStatement($fetchAllInstitutionsStatement);
        $SQLStatement->execute();

        while($row = $SQLStatement->fetch(PDO::FETCH_OBJ)){
            array_push($institutionsArray, $row->Name);
        }

        DatabaseManager::Disconnect();
    }
    catch(Exception $exception){
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    echo json_encode($institutionsArray), PHP_EOL;
    http_response_code(StatusCodes::OK);

?>