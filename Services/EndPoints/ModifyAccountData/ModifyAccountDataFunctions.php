<?php
    require_once ("../../HelperClasses/DatabaseManager.php");
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

        static function prepareResponse($userRow, $inputCurrentHashedPassword) {
            if ($userRow["ID"] == null)
                $response = CommonEndPointLogic::GetFailureResponseStatus("USER_NOT_FOUND");
            else if ($userRow["Hashed_Password"] != $inputCurrentHashedPassword)
                $response = CommonEndPointLogic::GetFailureResponseStatus("WRONG_PASSWORD");
            else 
                $response = CommonEndPointLogic::GetSuccessResponseStatus();

            return $response;
        }

        static function updateHashedPasswordInDatabase($ID, $newHashedPassword) {
            $SQLUpdateStatement = "UPDATE Users SET Hashed_Password = :hashedPassword
                WHERE :ID = Users.ID;
            ";
            $SQLStatement = DatabaseManager::PrepareStatement($SQLUpdateStatement);
            $SQLStatement->bindParam(":hashedPassword", $newHashedPassword);
            $SQLStatement->bindParam(":ID", $ID);
            $SQLStatement->execute();
        }

        static function updateFirstNameInDatabase($ID, $newFirstName) {
            $SQLUpdateStatement = "UPDATE Users SET First_Name = :firstName
                WHERE :ID = Users.ID;
            ";
            $SQLStatement = DatabaseManager::PrepareStatement($SQLUpdateStatement);
            $SQLStatement->bindParam(":firstName", $newFirstName);
            $SQLStatement->bindParam(":ID", $ID);
            $SQLStatement->execute();
        }

        static function updateLastNameInDatabase($ID, $newLastName) {
            $SQLUpdateStatement = "UPDATE Users SET Last_Name = :lastName
                WHERE :ID = Users.ID;
            ";
            $SQLStatement = DatabaseManager::PrepareStatement($SQLUpdateStatement);
            $SQLStatement->bindParam(":lastName", $newLastName);
            $SQLStatement->bindParam(":ID", $ID);
            $SQLStatement->execute();

        }

        static function updateFieldsInDatabase($ID, $newHashedPassword, $newFirstName, $newLastName) {
            DatabaseManager::Connect();

            if($newHashedPassword != null)
                ModifyAccountManager::updateHashedPasswordInDatabase($ID, $newHashedPassword);
            if($newFirstName != null)
                ModifyAccountManager::updateFirstNameInDatabase($ID, $newFirstName);
            if($newLastName != null)
                ModifyAccountManager::updateLastNameInDatabase($ID, $newLastName);

            DatabaseManager::Disconnect();
        }
    }
?>