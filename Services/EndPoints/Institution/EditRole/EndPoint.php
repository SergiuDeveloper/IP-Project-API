<?php

    require_once ("../../../HelperClasses/DatabaseManager.php");
    require_once ("../../../HelperClasses/CommonEndPointLogic.php");
    require_once ("../../../HelperClasses/StatusCodes.php");
    require_once ("../../../HelperClasses/ValidationHelper.php");
    require_once ("../../../HelperClasses/Institution/InstitutionActions.php");
    require_once ("../../../HelperClasses/Institution/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $username = $_POST['username'];
    $hashedPassword = $_POST['hashedPassword'];
    $institutionName = $_POST['institutionName'];
    $roleName = $_POST['roleName'];
    $newRoleName = $_POST['newRoleName'];
    $newRoleRights = json_decode($_POST['newRoleRights'], true);

    echo $username, PHP_EOL,
    $hashedPassword, PHP_EOL,
    $institutionName, PHP_EOL,
    $roleName, PHP_EOL,
    $newRoleName, PHP_EOL;
    print_r($newRoleRights);

    if( $username == null
        || $hashedPassword == null
        || $institutionName == null
        || $roleName == null
        || $newRoleRights == null
    ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");
        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }
/*
    $newRoleRights = [
            "Can_Modify_Institution"                    => (int) $newRoleRightsUncasted["Can_Modify_Institution"],
            "Can_Delete_Institution"                    => (int) $newRoleRightsUncasted["Can_Delete_Institution"],
            "Can_Add_Members"                           => (int) $newRoleRightsUncasted["Can_Add_Members"],
            "Can_Remove_Members"                        => (int) $newRoleRightsUncasted["Can_Remove_Members"],
            "Can_Upload_Documents"                      => (int) $newRoleRightsUncasted["Can_Upload_Documents"],
            "Can_Preview_Uploaded_Documents"            => (int) $newRoleRightsUncasted["Can_Preview_Uploaded_Documents"],
            "Can_Remove_Uploaded_Documents"             => (int) $newRoleRightsUncasted["Can_Remove_Uploaded_Documents"],
            "Can_Send_Documents"                        => (int) $newRoleRightsUncasted["Can_Send_Documents"],
            "Can_Preview_Received_Documents"            => (int) $newRoleRightsUncasted["Can_Preview_Received_Documents"],
            "Can_Preview_Specific_Received_Document"    => (int) $newRoleRightsUncasted["Can_Preview_Specific_Received_Document"],
            "Can_Remove_Received_Documents"             => (int) $newRoleRightsUncasted["Can_Remove_Received_Documents"],
            "Can_Download_Documents"                    => (int) $newRoleRightsUncasted["Can_Download_Documents"],
            "Can_Add_Roles"                             => (int) $newRoleRightsUncasted["Can_Add_Roles"],
            "Can_Remove_Roles"                          => (int) $newRoleRightsUncasted["Can_Remove_Roles"],
            "Can_Modify_Roles"                          => (int) $newRoleRightsUncasted["Can_Modify_Roles"],
            "Can_Assign_Roles"                          => (int) $newRoleRightsUncasted["Can_Assign_Roles"],
            "Can_Deassign_Roles"                        => (int) $newRoleRightsUncasted["Can_Deassign_Roles"]
    ];
*/
    print_r($newRoleRights);

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    try{
        if (InstitutionRoles::isUserAuthorized($username, $institutionName, InstitutionActions::ADD_ROLE) == false) {
            $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION");

            echo json_encode($failureResponseStatus), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }
    }
    catch (InstitutionRolesInvalidAction $exception){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    InstitutionRoles::updateRole($roleName, $institutionName, $newRoleName, $newRoleRights);

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response);
    http_response_code(StatusCodes::OK);

?>