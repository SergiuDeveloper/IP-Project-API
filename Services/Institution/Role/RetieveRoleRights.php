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

    CommonEndPointLogic::ValidateHTTPGETRequest();

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

    InstitutionValidator::validateInstitution($institutionName);

    $fullRoleRights = InstitutionRoles::fetchUserRightsDictionary($email, $institutionName);

    try
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("Rights", $fullRoleRights)
            ->send();
    }
    catch(ResponseHandlerDuplicateLabel $e)
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }
?>

