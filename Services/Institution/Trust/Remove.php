<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once("../Role/Utility/InstitutionActions.php");
    require_once("../Role/Utility/InstitutionRoles.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $email              = $_POST["email"];
    $hashedPassword     = $_POST["hashedPassword"];
    $institutionName    = $_POST["institutionName"];
    $trustedInst        = $_POST["trustedInstitutionName"];

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

    if ($institutionName == null || $trustedInst == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    try {
        if (false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::MODIFY_INSTITUTION)){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNAUTHORIZED_ACTION"))
                ->send();
        }
    } catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION"))
            ->send();
    }

    $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

    $queryGetInstID = "SELECT id from institutions WHERE Name = :instName";
    $getFromWhitelist = "SELECT ID FROM institution_whitelist WHERE Institution_ID = :instID AND Trusted_Institution_ID = :trustedInstID";
    $queryDeleteTrustedInst = "DELETE FROM institution_whitelist WHERE ID = :trustID";

    try {
        DatabaseManager::Connect();

        $getInst = DatabaseManager::PrepareStatement($queryGetInstID);
        $getInst->bindParam(":instName", $institutionName);
        $getInst->execute();

        $instRow = $getInst->fetch(PDO::FETCH_ASSOC);
        if($instRow == null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
            ->send();
        }
        $idInst = $instRow["ID"];

        $getInst = DatabaseManager::PrepareStatement($queryGetInstID);
        $getInst->bindParam(":instName", $trustedInst);
        $getInst->execute();

        $trustedInstRow = $getInst->fetch(PDO::FETCH_ASSOC);

        if($trustedInstRow == null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
            ->send();
        }
        $trustedInstID = $trustedInstRow["ID"];

        $getTrust = DatabaseManager::PrepareStatement($getFromWhitelist);
        $getTrust->bindParam(":instID", $idInst);
        $getTrust->bindParam(":trustedInstID", $trustedInstRow);
        $getTrust->execute(); 
        
        $getTrustRow = $getTrust->fetch(PDO::FETCH_ASSOC);

        if($getTrustRow == null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("RELATIONSHIP_NOT_FOUND"))
            ->send();
        }

        $deleteTrust = DatabaseManager::PrepareStatement($queryDeleteTrustedInst);
        $deleteTrust->bindParam(":trustID", $getTrustRow['ID']);
        $deleteTrust->execute();

        DatabaseManager::Disconnect();
        
    } catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
    }

    try {
        ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }
?>