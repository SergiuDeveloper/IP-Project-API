<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/UserValidation.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/SuccessStates.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
    require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

    require_once("./Utility/Document.php");
    require_once("./Utility/Invoice.php");
    require_once("./Utility/Receipt.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email              = $_GET["email"];
    $hashedPassword     = $_GET["hashedPassword"];
    $institutionName    = $_GET["institutionName"];
    $documentId         = $_GET["documentId"];

    if ($email == null || $hashedPassword == null || $institutionName == null || $documentId == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    $queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

    $documentQuery = "SELECT * FROM documents WHERE ID = :documentId;";

    $receiptQuery = "SELECT * FROM Receipts WHERE Documents_ID = :documentId;";

    $invoiceQuery = "SELECT * FROM Invoices WHERE Documents_ID = :documentId;";

    $queryGetItems = "SELECT * FROM document_items WHERE Documents_ID = :documentId;";

    $queryGetItem = "SELECT * FROM Items WHERE ID = :itemId;";

    $institutionRoles = array();
    try {
        DatabaseManager::Connect();

        $getInstitution = DatabaseManager::PrepareStatement($queryIdInstitution);
        $getInstitution->bindParam(":institutionName", $institutionName);
        $getInstitution->execute();

        $institutionRow = $getInstitution->fetch(PDO::FETCH_ASSOC);

        if($institutionRow == null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
                ->send();
        }

        $getDocument = DatabaseManager::PrepareStatement($documentQuery);
        $getDocument->bindParam(":documentId", $documentId);
        $getDocument->execute();

        $documentRow = $getDocument->fetch(PDO::FETCH_ASSOC);

        if($documentRow == null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_INVALID"))
                ->send();
        } else {
            if($documentRow["Sender_Institution_ID"] == $institutionRow["ID"]){
                $type = "intern";
            } else if($documentRow["Receiver_Institution_ID"] == $institutionRow["ID"]){
                $type = "received";
            } else {
                ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_INVALID"))
                ->send();
            }
        }

        if($type == "intern"){
            if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::PREVIEW_UPLOADED_DOCUMENTS)) {
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                    ->send();
            }
        } else if($type == "received"){
            if( false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::PREVIEW_SPECIFIC_RECEIVED_DOCUMENT)) {
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                    ->send();
            }
        }

        DatabaseManager::Connect();

        $getReceipt = DatabaseManager::PrepareStatement($receiptQuery);
        $getReceipt->bindParam(":documentId", $documentId);
        $getReceipt->execute();

        $receiptRow = $getReceipt->fetch(PDO::FETCH_ASSOC);

        $getInvoice = DatabaseManager::PrepareStatement($invoiceQuery);
        $getInvoice->bindParam(":documentId", $documentId);
        $getInvoice->execute();

        $invoiceRow = $getInvoice->fetch(PDO::FETCH_ASSOC);

        if($receiptRow == null && $invoiceQuery == null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_NOT_FOUND"))
                ->send();
        }
        if($receiptRow == null){
            $document = new Invoice();
            $document->setId($documentRow["ID"])->fetchFromDatabaseDocumentByID();
        }

        if($invoiceRow == null){
            $document = new Receipt();
            $document->setId($documentRow["ID"])->fetchFromDatabase();
        }
       
        DatabaseManager::Disconnect();
    }
    catch (Exception $databaseException) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("document", $document)
            ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }
?>