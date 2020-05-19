<?php

    if(!defined('ROOT'))
        define('ROOT', dirname(__FILE__) . '/..');

    require_once (ROOT . '/Utility/Utilities.php');
    require_once (ROOT . '/Document/Utility/Document.php');

    require_once (ROOT . '/Institution/Role/Utility/InstitutionActions.php');
    require_once (ROOT . '/Institution/Role/Utility/InstitutionRoles.php');

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email = $_POST['email'];
    $hashedPassword = $_POST['hashedPassword'];
    $senderInstitutionName = $_POST['senderInstitutionName'];
    $documentID = $_POST['documentID'];
    $receiverInstitutionID = $_POST['receiverInstitutionID'];
    $receiverAddressID = $_POST['receiverAddressID'];
    $receiverUserID = $_POST['receiverUserID'];

    if(isset($_POST['debugMode']))
        define('DEBUG_ENABLED', true);

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
        $senderInstitutionName == null ||
        $documentID == null ||
        $receiverInstitutionID == null
    ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send();
    }

    //CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $senderUserID = null;

    try{
        DatabaseManager::Connect();

        $statement = DatabaseManager::PrepareStatement("SELECT ID FROM users WHERE Email = :email");
        $statement->bindParam(":email", $email);
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_OBJ);

        $senderUserID = $row->ID;

        DatabaseManager::Disconnect();
    } catch (PDOException $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        if (false == InstitutionRoles::isUserAuthorized($email, $senderInstitutionName, InstitutionActions::SEND_DOCUMENTS)) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
        }
    } catch (InstitutionRolesInvalidAction $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
    }

    try {
        $document = Document::fetchDocument($documentID);
    } catch (DocumentNotFound $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_NOT_FOUND"))
            ->send();
    } catch (DocumentTypeNotFound $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_TYPE_NOT_CONFIGURED"))
            ->send();
    }

    try {
        $document
            ->setSenderID($senderUserID)
            ->setReceiverInstitutionID($receiverInstitutionID)
            ->setReceiverAddressID($receiverAddressID)
            ->setReceiverID($receiverUserID)
            ->send();
    } catch (DocumentSendAlreadySent $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_ALREADY_SENT"))
            ->send();
    } catch (DocumentSendInvalidReceiverInstitution $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_RECEIVER_INSTITUTION"))
            ->send();
    } catch (DocumentSendInvalidReceiverUser $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_RECEIVER_USER"))
            ->send();
    } catch (DocumentSendNoReceiverInstitution $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("RECEIVER_INSTITUTION_ABSENT"))
            ->send();
    } catch (DocumentSendUpdateStatementFailed $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("SENDING_FAILED"))
            ->send();
    }

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
?>