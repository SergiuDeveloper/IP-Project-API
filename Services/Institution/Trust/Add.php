<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    require_once("/../Role/Utility/InstitutionActions.php");
    require_once("/../Role/Utility/InstitutionRoles.php");

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
        if (false == InstitutionRoles::isUserAuthorized($email, $institutionName, InstitutionActions::APPROVE_DOCUMENTS)){
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
    $queryInsetTrustedInst = "INSERT into institution_whitelist(Institution_ID,Trusted_Institution_ID) VALUES(:instID,:trustedInstID)";

    try {
        DatabaseManager::Connect();

        $getInst = DatabaseManager::PrepareStatement($queryGetInstID);
        $getInst->bindParam(":instName", $institutionName);
        $getInst->execute();

        $instRow = $getInst->fetch(PDO::FETCH_ASSOC);

        $getInst = DatabaseManager::PrepareStatement($queryGetInstID);
        $getInst->bindParam(":instName", $trustedInst);
        $getInst->execute();

        $trustedInstRow = $getInst->fetch(PDO::FETCH_ASSOC);

        if($instRow == null || $trustedInstRow == null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INSTITUTION_NOT_FOUND"))
            ->send();
        }

        $getTrust = DatabaseManager::PrepareStatement($getFromWhitelist);
        $getTrust->bindParam(":instID", $instRow['ID']);
        $getTrust->bindParam(":trustedInstID", $trustedInstRow['ID']);
        $getTrust->execute(); 
        
        $getTrustRow = $getTrust->fetch(PDO::FETCH_ASSOC);
        
        if($getTrustRow != null){
            ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INST_ALREADY_ON WHITELIST"))
            ->send();
        }

        $insertTrust = DatabaseManager::PrepareStatement($queryInsetTrustedInst);
        $insertTrust->bindParam(":instID", $instRow['ID']);
        $insertTrust->bindParam(":trustedInstID", $trustedInstRow['ID']);
        $insertTrust->execute();

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