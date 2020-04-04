<?php

    require_once ("../../../HelperClasses/DatabaseManager.php");
    require_once ("../../../HelperClasses/CommonEndPointLogic.php");
    require_once ("../../../HelperClasses/SuccessStates.php");
    require_once ("../../../HelperClasses/StatusCodes.php");
    require_once("../../../HelperClasses/Institution/InstitutionRoles.php");
    
    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $username = $_POST["username"];
    $hashedPassword = $_POST["hashedPassword"];
    $institutionName = $_POST["institutionName"];
    $roleName = $_POST["roleName"];

    if( $username == null || $hashedPassword == null || $institutionName == null || $roleName == null ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    try {
        if (InstitutionRoles::isUserAuthorized($username, $institutionName, InstitutionActions::ADD_ROLE) == false) {
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

            echo json_encode($failureResponseStatus), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }
    }
    catch(InstitutionRolesInvalidAction $exception){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
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

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response);
    http_response_code(StatusCodes::OK);

?>

