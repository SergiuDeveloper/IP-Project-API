<?php

    if(!defined('ROOT'))
    {
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

    $email = $_GET["email"];
    $hashedPassword = $_GET["hashedPassword"];
    $institutionName = $_GET["institutionName"];

    if ($email == null || $hashedPassword == null || $institutionName == null)
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryIDInstitution = "SELECT ID FROM institutions WHERE name = :institutionName";

    $queryRoleID = "SELECT Institution_Rights_ID FROM institution_roles WHERE Institution_ID = :institutionID";

