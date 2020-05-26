<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/DatabaseManager.php");
    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    class ModifyAccountManager {
        static function fetchIDAndHashedPassword($email)
        {
            $getIDAndHashedPasswordQuery = "
                SELECT ID, Hashed_Password FROM USERS
                    WHERE Email = :email
            ";

            try {
                DatabaseManager::Connect();

                $userRowStatement = DatabaseManager::PrepareStatement($getIDAndHashedPasswordQuery);
                $userRowStatement->bindParam(":email", $email);
                $userRowStatement->execute();
                $userRow = $userRowStatement->fetch();

                DatabaseManager::Disconnect();

                return $userRow;
            }
            catch (Exception $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
            */
                die();
            }
        }
        
        static function PrepareStatementUpdateUserInDatabase($ID, $newHashedPassword, $newFirstName, $newLastName){
            $SQLPreparedStatement = "UPDATE Users SET ";
            if($newHashedPassword != null)
                $SQLPreparedStatement = $SQLPreparedStatement . "Hashed_Password = :hashedPassword, ";
            if($newFirstName != null)
                $SQLPreparedStatement = $SQLPreparedStatement . "First_Name = :firstName, ";
            if($newLastName != null)
                $SQLPreparedStatement = $SQLPreparedStatement . "Last_Name = :lastName, ";

            $SQLPreparedStatement = $SQLPreparedStatement . "DateTime_Modified = CURRENT_TIMESTAMP";

            $SQLPreparedStatement = $SQLPreparedStatement . " WHERE :ID = Users.ID";

            $SQLUpdateStatement = DatabaseManager::PrepareStatement($SQLPreparedStatement);
            
            if($newHashedPassword != null)
                $SQLUpdateStatement->bindParam(":hashedPassword", $newHashedPassword);
            if($newFirstName != null)
                $SQLUpdateStatement->bindParam(":firstName", $newFirstName);
            if($newLastName != null)
                $SQLUpdateStatement->bindParam(":lastName", $newLastName);
            $SQLUpdateStatement->bindParam(":ID", $ID);

            $SQLUpdateStatement->execute();

            $SQLUpdateStatement->debugDumpParams();
        }

        static function updateFieldsInDatabase($ID, $newHashedPassword, $newFirstName, $newLastName) {
            try {
                DatabaseManager::Connect();

                ModifyAccountManager::PrepareStatementUpdateUserInDatabase($ID, $newHashedPassword, $newFirstName, $newLastName);

                DatabaseManager::Disconnect();
            }
            catch(Exception $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
            }
        }
    }
?>