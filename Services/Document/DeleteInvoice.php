<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once (ROOT . '/Document/Utility/Document.php');
    require_once (ROOT . '/Utility/Utilities.php');

    require_once (ROOT . '/Institution/Utility/InstitutionValidator.php');

    require_once (ROOT . '/Institution/Role/Utility/InstitutionRoles.php');
    require_once (ROOT . '/Institution/Role/Utility/InstitutionActions.php');

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email = $_POST['email'];
    $hashedPassword = $_POST['hashedPassword'];
    $institutionName = $_POST['institutionName'];
    $documentID = $_POST['documentID'];

    $apiKey = $_POST["apiKey"];

    if($apiKey != null){
        try {
            $credentials = APIKeyHandler::getInstance()->setAPIKey($apiKey)->getCredentials();
        } catch (APIKeyHandlerKeyUnbound $e) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNBOUND_KEY"))
                ->send(StatusCodes::INTERNAL_SERVER_ERROR);
        } catch (APIKeyHandlerAPIKeyInvalid $e) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_KEY"))
                ->send();
        }

        $email = $credentials->getEmail();
        //$hashedPassword = $credentials->getHashedPassword();
    } else {
        if ($email == null || $hashedPassword == null) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
                ->send(StatusCodes::BAD_REQUEST);
        }
        CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
    }

    if(
        $institutionName == null ||
        $documentID == null
    ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send();
    }

    //CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    InstitutionValidator::validateInstitution($institutionName);

    $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

    $document = new Invoice();
    $document->setID($documentID)->fetchFromDatabase();

    $type = "Unknown";

    //echo $institutionID, PHP_EOL, json_encode($document->getDAO()), PHP_EOL;

    if($document->getSenderInstitutionID() == $institutionID){
        $type = "External";
    }
    if($document->getReceiverInstitutionID() == $institutionID){
        $type = "Internal";
    }

    if($type == "Unknown"){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_NOT_IN_INSTITUTION"))
            ->send();
    }

    try{
        if(
            ($type == "External" && false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::REMOVE_UPLOADED_DOCUMENTS)) ||
            ($type == "Internal" && false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::REMOVE_RECEIVED_DOCUMENTS))
        ){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
        }
    } catch (InstitutionRolesInvalidAction $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
    }

    $document->deleteFromDatabase($type);

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

?>