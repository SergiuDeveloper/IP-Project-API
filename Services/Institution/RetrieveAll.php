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
    require_once("Utility/InstitutionCreation.php");
    require_once("Utility/InstitutionValidator.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email                  = $_GET['email'];
    $hashedPassword         = $_GET['hashedPassword'];
    $institutionsPerPage    = $_GET['institutionsPerPage'];
    $pageNumber             = $_GET['pageNumber'] - 1;
    $orderByAsc             = $_GET['orderByAsc'];

    if(
        $email                  == null ||
        $hashedPassword         == null ||
        $institutionsPerPage    == null
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

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $offset = $pageNumber * $institutionsPerPage;

    if($offset < 0)
        $offset = 0;

    $fetchAllInstitutionsStatement = "SELECT Name FROM institutions ORDER BY Name $ascendant LIMIT $institutionsPerPage OFFSET $offset" ;

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
            ->addResponseData("institutions", $institutionsArray)
            ->send();
    }
    catch (Exception $exception) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(500);
    }

/*
    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    echo json_encode($institutionsArray), PHP_EOL;
    http_response_code(StatusCodes::OK);
*/
