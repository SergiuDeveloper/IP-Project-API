<?php

    if(!defined('ROOT'))
    {
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

    $getInstitutionIDQuery = "SELECT ID FROM Institutions WHERE name = :institutionName";

    $getDocumentsQuery = "SELECT * FROM documents WHERE Receiver_Institution_ID = :institutionID";

    $getDocumentTypeQuery = "SELECT * FROM document_types WHERE ID = :id";

    try {
        if (InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::PREVIEW_RECEIVED_DOCUMENTS) == false) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
        }
    }
    catch(Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
    }

    try{

        DatabaseManager::Connect(); //DB_EXCEPT TODO : exceptii calumea, testare

        $getInstitutionID = DatabaseManager::PrepareStatement($getInstitutionIDQuery);
        $getInstitutionID->bindParam(":institutionName", $institutionName);
        $getInstitutionID->execute();

        $institutionID = $getInstitutionID->fetch(PDO::FETCH_ASSOC);

        if ($institutionID == null)
        {
           ResponseHandler::getInstance()
               ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
               ->send();
        }

        $getDocuments = DatabaseManager::PrepareStatement($getDocumentsQuery);
        $getDocuments->bindParam(":institutionID", $institutionID["ID"]);
        $getDocuments->execute();

        $documents = $getDocuments->fetch(PDO::FETCH_ASSOC);

        if ($documents == null)
        {
           ResponseHandler::getInstance()
               ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENTS_INVALID"))
               ->send();
        }

        $receiptsList = array();
        $invoicesList = array();

        foreach ($documents as $document)
        {
            $getDocumentType = DatabaseManager::PrepareStatement($getDocumentTypeQuery);
            $getDocumentType->bindParam(":id", $document["Document_Types_ID"]);
            $getDocumentType->execute();

            $documentType = $getDocumentType->fetch(PDO::FETCH_ASSOC);

            if ($documentType["Title"] == "Invoice")
            {
                $invoice = new Invoice();
                $invoice->setId($document["ID"])->fetchFromDatabaseDocumentByID();

                array_push($invoicesList, $invoice);
            }

            if ($documentType["Title"] == "Receipt")
            {
               $receipt = new Receipt();
               $receipt->setID($document["ID"])->fetchFromDatabase();

               array_push($receiptsList, $receipt);
            }
        }

        DatabaseManager::Disconnect();

    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

