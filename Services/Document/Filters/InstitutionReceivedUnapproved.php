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
        if($email == null || $hashedPassword== null) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))->
            send(StatusCodes::BAD_REQUEST);
        }
        CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
    }

    if($institutionName == null) {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))->send();
    }

    try {
        if(false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::APPROVE_DOCUMENTS)) {
            ResponseHandler::getInstance()->
            setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORISED_ACTION"))->
            send();
        }
    } catch (InstitutionRolesInvalidAction $e) {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))->
        send();
    }

    $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

    $statementString = "
            SELECT 
                documents.ID,
                Sender_User_ID,
                Sender_Address_ID,
                Sender_Institution_ID,
                Creator_User_ID,
                Receiver_Institution_ID,
                Receiver_Address_ID,
                Receiver_User_ID,
                Is_Sent,
                Is_Approved,
                Date_Sent,
                Date_Created,
                document_types.Title
            FROM documents JOIN document_types on documents.Document_Types_ID = document_types.ID 
            WHERE Receiver_Institution_ID = :institutionID and Is_Approved = 0
        ";

    $responseArray = array();

    try {
        DatabaseManager::Connect();

        $statement = DatabaseManager::PrepareStatement($statementString);
        $statement->bindParam(":institutionID", $institutionID);
        $statement->execute();

        while($row = $statement->fetch(PDO::FETCH_OBJ)) {
            $document = new \DAO\Document();

            $document->ID = $row->ID;
            $document->dateCreated = $row->Date_Created;
            $document->dateSent = $row->Date_Sent;
            $document->creatorID = $row->Creator_User_ID;
            $document->senderAddressID = $row->Sender_Address_ID;
            $document->senderInstitutionID = $row->Sender_Institution_ID;
            $document->senderID = $row->Sender_User_ID;
            $document->receiverID = $row->Receiver_User_ID;
            $document->receiverAddressID = $row->Receiver_Address_ID;
            $document->receiverInstitutionID = $row->Receiver_Institution_ID;
            $document->isSent = $row->Is_Sent;
            $document->isApproved = $row->Is_Approved;

            $document->documentType = $row->Title;

            //echo json_encode($document), PHP_EOL;

            array_push($responseArray, $document);
        }
        DatabaseManager::Disconnect();
    } catch (PDOException $e) {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))->
        send();
    }

    try {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())->
        addResponseData("documents", $responseArray)->
        send();
    } catch (Exception $e) {
        ResponseHandler::getInstance()->
        setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))->
        send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

?>
