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

    $email              = $_POST["email"];
    $hashedPassword     = $_POST["hashedPassword"];
    $institutionName    = $_POST["institutionName"];
    $institutionAddress = json_decode($_POST["institutionAddress"], true);

    if(
        $email              == null ||
        $hashedPassword     == null ||
        $institutionName    == null ||
        $institutionAddress == null
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

    if( InstitutionCreation::checkForInstitutionDuplicate($institutionName) == false ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_INSTITUTION"))
            ->send();
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_INSTITUTION");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    if( InstitutionCreation::checkAddressValidity($institutionAddress) == false ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ADDRESS_INVALID"))
            ->send();
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("ADDRESS_INVALID");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    $addressID = InstitutionCreation::insertAddressIntoDatabase($institutionAddress);

    $institutionID = InstitutionCreation::insertInstitutionIntoDatabase($institutionName);

    InstitutionCreation::linkInstitutionWithAddress($institutionID, $addressID);

    $roleDictionary =  [
            "Can_Modify_Institution"                    => true,
            "Can_Delete_Institution"                    => true,
            "Can_Add_Members"                           => true,
            "Can_Remove_Members"                        => true,
            "Can_Upload_Documents"                      => true,
            "Can_Preview_Uploaded_Documents"            => true,
            "Can_Remove_Uploaded_Documents"             => true,
            "Can_Send_Documents"                        => true,
            "Can_Preview_Received_Documents"            => true,
            "Can_Preview_Specific_Received_Document"    => true,
            "Can_Remove_Received_Documents"             => true,
            "Can_Download_Documents"                    => true,
            "Can_Add_Roles"                             => true,
            "Can_Remove_Roles"                          => true,
            "Can_Modify_Roles"                          => true,
            "Can_Assign_Roles"                          => true,
            "Can_Deassign_Roles"                        => true
       ];

    InstitutionRoles::createRole('Manager', $institutionName, $roleDictionary);

    InstitutionRoles::addAndAssignMemberToInstitution($email, $institutionName, 'Manager');

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

    /*
    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */
