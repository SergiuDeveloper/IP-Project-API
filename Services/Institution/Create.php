<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");
    require_once(ROOT . "/Institution/Utility/InstitutionCreation.php");

    $debugHeader = 'In ' . basename(__FILE__) . ', ';

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email              = $_POST["email"];
    $hashedPassword     = $_POST["hashedPassword"];
    $institutionName    = $_POST["institutionName"];
    $institutionAddress = json_decode($_POST["institutionAddress"], true);
    $institutionCIF     = $_POST["institutionCIF"];

    $debugModeExists    = isset($_POST['debugMode']);
    if($debugModeExists)
        $debugMode      = $_POST['debugMode'];

    if(
        $email              == null ||
        $hashedPassword     == null ||
        $institutionName    == null ||
        $institutionAddress == null ||
        $institutionCIF     == null
    ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED VAR TEST')
            ->setLineNumber(__LINE__)
            ->debugEcho();

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED CREDENTIAL TEST')
            ->setLineNumber(__LINE__)
            ->debugEcho();

    if( InstitutionCreation::checkForInstitutionDuplicate($institutionName) == true ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_INSTITUTION"))
            ->send();
    }

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED INITIAL DUPLICATE TEST')
            ->setLineNumber(__LINE__)
            ->debugEcho();

    if( InstitutionCreation::checkAddressValidity($institutionAddress) == false ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ADDRESS_INVALID"))
            ->send();
    }

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED ADDRESS VALIDITY CHECKER TEST')
            ->setLineNumber(__LINE__)
            ->debugEcho();

    $addressID = InstitutionCreation::insertAddressIntoDatabase($institutionAddress);

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED ADDRESS INSERTION')
            ->setLineNumber(__LINE__)
            ->debugEcho();

    $institutionID = InstitutionCreation::insertInstitutionIntoDatabase($institutionName, $institutionCIF);

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED INSTITUTION INSERTION')
            ->setLineNumber(__LINE__)
            ->debugEcho();

    InstitutionCreation::linkInstitutionWithAddress($institutionID, $addressID);

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED LINKAGE')
            ->setLineNumber(__LINE__)
            ->debugEcho();

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

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED ROLE INSERTION')
            ->setLineNumber(__LINE__)
            ->debugEcho();

    InstitutionRoles::addAndAssignMemberToInstitution($email, $institutionName, 'Manager');

    if($debugMode)
        DebugHandler::getInstance()
            ->setSource(basename(__FILE__))
            ->setDebugMessage('PASSED MEMBER INSERTION AND ROLE MANAGER')
            ->setLineNumber(__LINE__)
            ->debugEcho();

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

?>