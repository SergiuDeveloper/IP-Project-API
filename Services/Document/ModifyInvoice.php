<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . '/..');
}

    require_once (ROOT . '/Document/Utility/Document.php');
    require_once (ROOT . '/Utility/Utilities.php');

    require_once (ROOT . '/Institution/Utility/InstitutionValidator.php');

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email = $_POST['email'];
    $hashedPassword = $_POST['hashedPassword'];
    $institutionName = $_POST['institutionName'];
    $document = json_decode($_POST['document']);

    if(
        $email == null ||
        $hashedPassword == null
    ){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIALS"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    if($document == null){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("BAD_INPUT_DOCUMENT_JSON"))
            ->send(StatusCodes::BAD_REQUEST);
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

            DatabaseManager::Disconnect();
        }
        catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
        }
    }

    $documentObject = new Invoice();

    $documentObject->setID($document['ID'])->fetchFromDatabase();

    $documentObject->updateIntoDatabase($document);

?>
