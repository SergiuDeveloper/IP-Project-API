<?php

    if(!defined('ROOT'))
        define('ROOT', dirname(__FILE__) . '/..');

    require_once (ROOT . '/Utility/Utilities.php');
    require_once (ROOT . '/Document/Utility/Document.php');

    require_once (ROOT . '/Institution/Role/Utility/InstitutionActions.php');
    require_once (ROOT . '/Institution/Role/Utility/InstitutionRoles.php');
    require_once(ROOT . '/Institution/Utility/InstitutionValidator.php');

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email = $_POST["email"];
    $hashedPassword = $_POST["hashedPassword"];
    $institutionName = $_POST["institutionName"];
    $documentID = $_POST["documentID"];

    $apiKey = $_POST["apiKey"];

    if($apiKey != null) {
        try {
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
        if($email == null || $hashedPassword == null) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIALS"))->
            send(StatusCodes::BAD_REQUEST);
        }

        CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
    }

    if($documentID == null || $institutionName == null) {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))->
        send(StatusCodes::BAD_REQUEST);
    }

    InstitutionValidator::validateInstitution($institutionName);
    $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

    try {
        if (false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::APPROVE_DOCUMENTS)) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORISED_ACTION"))->
            send();
        }
    } catch (InstitutionRolesInvalidAction $e) {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))->
        send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

    try {
        DatabaseManager::Connect();
        $statement = DatabaseManager::PrepareStatement("
            SELECT ID, Is_Approved, Is_Sent from documents where Receiver_Institution_ID = :rID and ID = :dID
        ");
        $statement->bindParam(":dID", $documentID);
        $statement->bindParam("rID", $institutionID);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_OBJ);

        if($row == null) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_NOT_FOUND"))->
            send();
        }

        if($row->Is_Approved == 1) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_ALREADY_APPROVED"))->
            send();
        }

        if($row->Is_Sent == 0) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_NOT_SENT"))->
            send();
        }

        $statement = DatabaseManager::PrepareStatement("
            UPDATE documents SET Is_Approved = true where ID = :dID
        ");

        $statement->bindParam(":dID", $documentID);
        $statement->execute();

        if($statement->rowCount() == 0){
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("EXECUTION_ERROR"))->
            send();
        }

        DatabaseManager::Disconnect();
    } catch (PDOException $e) {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))->
        send();
    }

    ResponseHandler::getInstance()->
    setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())->
    send();
