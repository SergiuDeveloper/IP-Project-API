<?php

    if(!defined('ROOT'))
        define('ROOT', dirname(__FILE__) . '/../..');

    require_once ( ROOT . '/Utility/Utilities.php' );
    require_once ( ROOT . '/Document/Utility/Document.php' );
    require_once ( ROOT . '/DataAccessObject/DataObjects.php' );

    require_once ( ROOT . '/Institution/Utility/InstitutionValidator.php' );

    require_once ( ROOT . '/Institution/Role/Utility/InstitutionRoles.php' );
    require_once ( ROOT . '/Institution/Role/Utility/InstitutionActions.php' );

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email = $_GET['email'];
    $hashedPassword = $_GET['hashedPassword'];
    $institutionName = $_GET['institutionName'];

    $apiKey = $_GET["apiKey"];

    if($apiKey != null) {
        try{
            $credentials = APIKeyHandler::getInstance()->setAPIKey($apiKey)->getCredentials();
        } catch (APIKeyHandlerAPIKeyInvalid $e) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("API_KEY_INVALID"))->
            send(StatusCodes::BAD_REQUEST);
        } catch (APIKeyHandlerKeyUnbound $e) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("API_KEY_UNBOUND"))->
            send(StatusCodes::INTERNAL_SERVER_ERROR);
        }

        $email = $credentials->getEmail();
    } else {
        if($email != null || $hashedPassword!= null) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))->
            send(StatusCodes::BAD_REQUEST);
        }
        CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
    }

    if($institutionName == null) {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"));
    }

    try {
        if(false == InstitutionRoles::isUserAuthorized($email, $hashedPassword, InstitutionActions::APPROVE_DOCUMENTS)) {

        }
    }

