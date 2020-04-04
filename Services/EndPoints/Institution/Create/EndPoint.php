<?php

    require_once ("../../../HelperClasses/DatabaseManager.php");
    require_once ("../../../HelperClasses/CommonEndPointLogic.php");
    require_once ("../../../HelperClasses/SuccessStates.php");
    require_once ("../../../HelperClasses/StatusCodes.php");
    require_once("../../../HelperClasses/Institution/InstitutionRoles.php");
    require_once("InstitutionCreation.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $username = $_POST["username"];
    $hashedPassword = $_POST["hashedPassword"];
    $institutionName = $_POST["institutionName"];
    $institutionAddress = json_decode($_POST["institutionAddress"], true);

    if($username == null
        || $hashedPassword == null
        || $institutionName == null
        || $institutionAddress == null
    ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    if( InstitutionCreation::checkForInstitutionDuplicate($institutionName) == false ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_INSTITUTION");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    if( InstitutionCreation::checkAddressValidity($institutionAddress) == false ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("ADDRESS_INVALID");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
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

    InstitutionRoles::addAndAssignMemberToInstitution($username, $institutionName, 'Manager');

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    http_response_code(StatusCodes::OK);

?>