<?php
    require_once("../../HelperClasses/DatabaseManager.php");
    require_once("../../HelperClasses/CommonEndPointLogic.php");

    class ModifyAccountManager {
        static function fetchIDandHashedPassword($username) {
            $getIDandHashedPasswordQuery = "
                SELECT ID, Hashed_Password FROM USERS
                    WHERE Username = :username
            ";

            DatabaseManager::Connect();

            $userRowStatement = DatabaseManager::PrepareStatement($getIDandHashedPasswordQuery);
            $userRowStatement->bindParam(":username", $username);
            $userRowStatement->execute();
            $userRow = $userRowStatement->fetch();

            DatabaseManager::Disconnect();

            return $userRow;
        }
        
        static function PrepareStatementUpdateUserInDatabase($ID, $newHashedPassword, $newFirstName, $newLastName){
            $SQLPreparedStatement = "UPDATE Users SET ";
            if($newHashedPassword != null)
                $SQLPreparedStatement = $SQLPreparedStatement . "Hashed_Password = :hashedPassword, ";
            if($newFirstName != null)
                $SQLPreparedStatement = $SQLPreparedStatement . "First_Name = :firstName, ";
            if($newLastName != null)
                $SQLPreparedStatement = $SQLPreparedStatement . "Last_Name = :lastName, ";

            $SQLPreparedStatement = rtrim($SQLPreparedStatement, ", ");

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
        }

        static function updateFieldsInDatabase($ID, $newHashedPassword, $newFirstName, $newLastName) {
            DatabaseManager::Connect();
           
            ModifyAccountManager::PrepareStatementUpdateUserInDatabase($ID, $newHashedPassword, $newFirstName, $newLastName);

            DatabaseManager::Disconnect();
        }
    }
?>