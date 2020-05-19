<?php

if(!defined('ROOT'))
    define('ROOT', dirname(__FILE__) . '/../..' );

require_once ( ROOT . '/Utility/Utilities.php' );

class InstitutionAutomation{

    public static function isInstitutionTrusted($institutionID, $institutionInCheckID, $connected = false){
        try{
            if(!$connected)
                DatabaseManager::Connect();

            $statement = DatabaseManager::PrepareStatement(self::$selectTrustedRowStatementString);
            $statement->bindParam(":instID", $institutionID);
            $statement->bindParam(":trustedInstID", $institutionInCheckID);
            $statement->execute();

            if(!$connected)
                DatabaseManager::Disconnect();

            return $statement->rowCount() != 0;
        } catch (PDOException $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            die();
        }
    }

    private static $selectTrustedRowStatementString = "
        SELECT ID FROM institution_whitelist WHERE Institution_ID = :instID AND Trusted_Institution_ID = :trustedInstID
    ";
}