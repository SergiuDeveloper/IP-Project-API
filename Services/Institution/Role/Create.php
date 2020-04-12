<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once("./Utility/InstitutionActions.php");
    require_once("./Utility/InstitutionRoles.php");

CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email              = $_POST["email"];
    $hashedPassword     = $_POST["hashedPassword"];
    $institutionName    = $_POST["institutionName"];
    $roleName           = $_POST["roleName"];

    if(
        $email              == null ||
        $hashedPassword     == null ||
        $institutionName    == null ||
        $roleName           == null
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

    try {
        if (InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::ADD_ROLE) == false) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
            /*
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

            echo json_encode($failureResponseStatus), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
            */
        }
    }
    catch(InstitutionRolesInvalidAction $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
    /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    */
    }

    $newRoleRightsDictionary =  [
        "Can_Modify_Institution"                    => false,
        "Can_Delete_Institution"                    => false,
        "Can_Add_Members"                           => false,
        "Can_Remove_Members"                        => false,
        "Can_Upload_Documents"                      => false,
        "Can_Preview_Uploaded_Documents"            => false,
        "Can_Remove_Uploaded_Documents"             => false,
        "Can_Send_Documents"                        => false,
        "Can_Preview_Received_Documents"            => false,
        "Can_Preview_Specific_Received_Document"    => false,
        "Can_Remove_Received_Documents"             => false,
        "Can_Download_Documents"                    => false,
        "Can_Add_Roles"                             => false,
        "Can_Remove_Roles"                          => false,
        "Can_Modify_Roles"                          => false,
        "Can_Assign_Roles"                          => false,
        "Can_Deassign_Roles"                        => false
    ];

    InstitutionRoles::createRole($roleName, $institutionName, $newRoleRightsDictionary);

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
    /*
    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response);
    http_response_code(StatusCodes::OK);
    */

