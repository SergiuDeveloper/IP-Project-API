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
    $document = json_decode($_POST['document'], true);

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

    if($document == null){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("BAD_INPUT_DOCUMENT_JSON"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    if($document['documentType'] != 'Invoice'){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_DOCUMENT_TYPE"))
            ->send();
    }

    $institutionID = null;

    if($institutionName != null){
        InstitutionValidator::validateInstitution($institutionName);

        $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();
    }
    else{
        try{
            DatabaseManager::Connect();

            $institutionID = $document['senderInstitutionID'];

            $statement = DatabaseManager::PrepareStatement("SELECT * FROM institutions WHERE ID = :ID");
            $statement->bindParam(":ID", $institutionID);
            $statement->execute();

            if($statement->rowCount() == 0){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_INSTITUTION"))
                    ->send();
            }

            $row = $statement->fetch(PDO::FETCH_OBJ);

            $institutionName = $row->Name;

            DatabaseManager::Disconnect();
        }
        catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    try {
        if (false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::SEND_DOCUMENTS)) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
        }
    } catch (InstitutionRolesInvalidAction $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
    }

$documentObject = new Invoice();

    $documentObject->setID($document['ID'])->fetchFromDatabase();

    $documentObject->updateIntoDatabase($document);

    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();

?>
