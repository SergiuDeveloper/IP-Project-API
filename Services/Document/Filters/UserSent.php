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

    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    require_once(ROOT . "/Document/Utility/Document.php");
    require_once(ROOT . "/Document/Utility/Invoice.php");
    require_once(ROOT . "/Document/Utility/Receipt.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email = $_GET["email"];
    $hashedPassword = $_GET["hashedPassword"];

    $institutionName = $_GET['institutionName'];

    if(
       $email == null ||
       $hashedPassword == null
    ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIALS"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $institutionID = null;

    if($institutionName != null){
        InstitutionValidator::validateInstitution($institutionName);
        $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();
    }

    $getUserRowStatementString = "SELECT ID FROM users WHERE Email = :email";
    $getDocumentRowsStatementString = "
        SELECT 
            documents.ID,
            Sender_User_ID,
            Sender_Institution_ID,
            Sender_Address_ID,
            Receiver_User_ID,
            Receiver_Institution_ID,
            Receiver_Address_ID,
            Creator_User_ID,
            Is_Sent,
            Date_Sent,
            Date_Created,
            Document_Types.Title
        FROM documents JOIN document_types on documents.Document_Types_ID = document_types.ID
        WHERE Sender_User_ID = :userID
    " . ($institutionID != null) ? " AND Sender_Institution_ID = :institutionID" : "";

    $responseArray = array();

    try {
        DatabaseManager::Connect();

        $userRows = DatabaseManager::PrepareStatement($getUserRowStatementString);
        $userRows->bindParam(":email", $email);
        $userRows->execute();
        $userRow = $userRows->fetch(PDO::FETCH_OBJ);

        $getDocumentRowsStatement = DatabaseManager::PrepareStatement($getDocumentRowsStatementString);
        $getDocumentRowsStatement->bindParam(":userID", $userRow->ID);
        if($institutionID != null)
            $getDocumentRowsStatement->bindParam(":institutionID", $institutionID);
        $getDocumentRowsStatement->execute();

        while($row = $getDocumentRowsStatement->fetch(PDO::FETCH_OBJ)){
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

            $document->documentType = $row->Title;

            array_push($responseArray, $document);
        }

        DatabaseManager::Disconnect();
    } catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("documents", $responseArray)
            ->send();
    } catch (Exception $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

?>