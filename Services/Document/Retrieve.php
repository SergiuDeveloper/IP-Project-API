<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . '/..');
}

require_once(ROOT . "/Utility/Utilities.php");

require_once(ROOT . "/Institution/Role/Utility/InstitutionActions.php");
require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");

require_once(ROOT . "/Document/Utility/Document.php");
require_once(ROOT . "/Document/Utility/Invoice.php");
require_once(ROOT . "/Document/Utility/Receipt.php");

require_once(ROOT . "/Utility/DebugHandler.php");

CommonEndPointLogic::ValidateHTTPGETRequest();

$email              = $_GET["email"];
$hashedPassword     = $_GET["hashedPassword"];
$institutionName    = $_GET["institutionName"];
$documentId         = $_GET["documentID"];

$debugEnabled       = isset($_GET['debugMode']) ? $_GET['debugMode'] : false;

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

if ($institutionName == null || $documentId == null) {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
        ->send(StatusCodes::BAD_REQUEST);
}

//CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

$queryIdInstitution = "SELECT ID FROM Institutions WHERE name = :institutionName;";

$documentQuery = "SELECT * FROM documents WHERE ID = :documentId;";

$documentTypeQuery = "SELECT * from document_types WHERE ID = :id";

$receiptQuery = "SELECT * FROM Receipts WHERE Documents_ID = :documentId;";

$invoiceQuery = "SELECT * FROM Invoices WHERE Documents_ID = :documentId;";

//$queryGetItems = "SELECT * FROM document_items WHERE Documents_ID = :documentId;";

//$queryGetItem = "SELECT * FROM Items WHERE ID = :itemId;";

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
    $docTypeId = $documentRow['Document_Types_ID'];

    if($documentRow == null){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_INVALID"))
            ->send();
    } else {

        if($debugEnabled)
            DebugHandler::getInstance()
                ->setDebugMessage('Check Document ext/int')
                ->setLineNumber(__LINE__)
                ->setSource(basename(__FILE__))
                ->addDebugVars($documentRow['Sender_Institution_ID'], 'doc sender id')
                ->addDebugVars($institutionRow['ID'], 'institution id')
                ->debugEcho();

        if($documentRow["Sender_Institution_ID"] == $institutionRow["ID"]){
            $type = "intern";
        } else if($documentRow["Receiver_Institution_ID"] == $institutionRow["ID"]){
            $type = "received";
        } else {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DOCUMENT_STATUS_INVALID"))
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

    $getType = DatabaseManager::PrepareStatement($documentTypeQuery);
    $getType->bindParam(":id", $docTypeId);
    $getType->execute();

    $getTypeRow = $getType->fetch(PDO::FETCH_ASSOC);
    $type = $getTypeRow['Title'];

    if($type == "Invoice"){

        $document = new Invoice();
        $document->setId($documentId)->fetchFromDatabaseByDocumentID(true);

    } else if($type == "Receipt"){

        $document = new Receipt();
        $document->setId($documentId)->fetchFromDatabaseByDocumentID(true);

    }

    DatabaseManager::Disconnect();
}
catch (Exception $databaseException) {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
        ->send();
}

try{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->addResponseData("document", $document->getDAO()->setDocumentType($type))
        ->send();
}
catch (Exception $exception){
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
}


?>