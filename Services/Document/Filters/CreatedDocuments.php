<?php

    if(!defined('ROOT'))
    {
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    require_once(ROOT . "/Document/Utility/Document.php");
    require_once(ROOT . "/Document/Utility/Invoice.php");
    require_once(ROOT . "/Document/Utility/Receipt.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email = $_GET['email'];
    $hashedPassword = $_GET['hashedPassword'];
    $institutionName = $_GET['institutionName'];

    $apiKey = $_GET["apiKey"];

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

    //CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    if($institutionName != null){
        InstitutionValidator::validateInstitution($institutionName);
    }

    try {
        DatabaseManager::Connect();

        $getUserIDStatement = DatabaseManager::PrepareStatement("
            SELECT ID FROM users WHERE Email = :email
        ");
        $getUserIDStatement->bindParam(":email", $email);
        $getUserIDStatement->execute();

        $userIDRow = $getUserIDStatement->fetch(PDO::FETCH_OBJ);

        $userID = $userIDRow->ID;

        $statementString = "SELECT * FROM documents WHERE Creator_User_ID = :creatorID";
        if($institutionName != null){
            $statementString = $statementString . " AND Sender_Institution_ID = :senderInstitutionID";
        }

        $getDocumentHeadersForCreatorStatement = DatabaseManager::PrepareStatement(
            $statementString
        );
        $getDocumentHeadersForCreatorStatement->bindParam(":creatorID", $userID);
        if($institutionName != null){
            $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();
            $getDocumentHeadersForCreatorStatement->bindParam(":senderInstitutionID", $institutionID);
        }

        $getDocumentHeadersForCreatorStatement->execute();

        $responseArray = array();

        while($row = $getDocumentHeadersForCreatorStatement->fetch(PDO::FETCH_ASSOC)){
            $document = new \DAO\Document();

            $document->ID = $row['ID'];
            $document->senderID = $row['Sender_User_ID'];
            $document->senderInstitutionID = $row['Sender_Institution_ID'];
            $document->senderAddressID = $row['Sender_Address_ID'];
            $document->receiverID = $row['Receiver_User_ID'];
            $document->receiverInstitutionID = $row['Receiver_Institution_ID'];
            $document->receiverAddressID = $row['Receiver_Address_ID'];
            $document->creatorID = $row['Creator_User_ID'];
            $document->dateCreated = $row['Date_Created'];
            $document->dateSent = $row['Date_Sent'];
            $document->isSent = $row['Is_Sent'];

            array_push($responseArray, $document);
        }
        DatabaseManager::Disconnect();
    }
    catch (PDOException $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("createdDocuments", $responseArray)
            ->send();
    } catch (Exception $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

?>