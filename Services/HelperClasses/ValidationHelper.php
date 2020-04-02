<?php

require_once("DatabaseManager.php");
require_once("SuccessStates.php");

/*
    Validation class for user-related actions
*/
class UserValidation {
    /*
        Return:                 int(SuccessState) = User credentials validation state
        inputUsername:          string = User's associated username
        inputHashedPassword:    string = User's associated hashed password
    */
    public static function ValidateCredentials($inputUsername, $inputHashedPassword) {
        try {
            DatabaseManager::Connect();

            $getHashedPasswordAndActiveStateStatement = DatabaseManager::PrepareStatement(UserValidation::$getHashedPasswordAndActiveStateQuery);
            $getHashedPasswordAndActiveStateStatement->bindParam(":inputUsername", $inputUsername);
            $getHashedPasswordAndActiveStateStatement->execute();

            $usersTableRow = $getHashedPasswordAndActiveStateStatement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();
        }
        catch (Exception $databaseException) {
            echo $databaseException;
            return SuccessStates::DB_EXCEPT;
        }

        if ($usersTableRow == null)
            return SuccessStates::USER_NOT_FOUND;
        
        if (!password_verify($inputHashedPassword, $usersTableRow->Hashed_Password))
            return SuccessStates::WRONG_PASSWORD;
        
        if (!$usersTableRow->Is_Active)
            return SuccessStates::USER_INACTIVE;

        return SuccessStates::SUCCESS;
    }

    /*
        Return:                                         int(SuccessState) = User's possession of the required rights, for the given institution
        inputUsername:                                  string = User's associated username
        inputInstitutionName:                           string = The institution's associated name
        inputInstitutionActionRequiredRightsDictionary: array(key => value) = The required institution rights to perform a certain action

        Usage example:
        <
            $inputInstitutionActionRequiredRightsDictionary = [
                "Can_Modify_Institution"                    => true,
                "Can_Delete_Institution"                    => true,
                "Can_Add_Members"                           => true,
                "Can_Remove_Members"                        => false,
                "Can_Upload_Documents"                      => false,
                "Can_Preview_Uploaded_Documents"            => true,
                "Can_Remove_Uploaded_Documents"             => false,
                "Can_Send_Documents"                        => true,
                "Can_Preview_Received_Documents"            => false,
                "Can_Preview_Specific_Received_Document"    => true,
                "Can_Remove_Received_Documents"             => false,
                "Can_Download_Documents"                    => true,
                "Can_Add_Roles"                             => false,
                "Can_Remove_Roles"                          => false,
                "Can_Modify_Roles"                          => true,
                "Can_Assign_Roles"                          => true,
                "Can_Deassign_Roles"                        => true
            ];

            echo (UserValidation::ValidateInstitutionActionRequiredRights("testuser", "testname", $inputInstitutionActionRequiredRightsDictionary) == true ? "true" : "false"), PHP_EOL;
        >
    */
    public static function ValidateInstitutionActionRequiredRights($inputUsername, $inputInstitutionName, $inputInstitutionActionRequiredRightsDictionary) {
        try {
            DatabaseManager::Connect();

            $getInstitutionMemberRightsStatement = DatabaseManager::PrepareStatement(UserValidation::$getInstitutionMemberRightsQuery);
            $getInstitutionMemberRightsStatement->bindParam(":inputUsername", $inputUsername);
            $getInstitutionMemberRightsStatement->bindParam(":inputInstitutionName", $inputInstitutionName);
            $getInstitutionMemberRightsStatement->execute();

            $institutionRightsRow = $getInstitutionMemberRightsStatement->fetch(PDO::FETCH_ASSOC);

            DatabaseManager::Disconnect();
        }
        catch (Exception $databaseException) {
            echo $databaseException;
            return SuccessStates::DB_EXCEPT;
        }

        if ($institutionRightsRow == null)
            return SuccessStates::INSTITUTION_RIGHTS_ROW_NOT_FOUND;
        
        try {
            unset($institutionRightsRow["ID"]);
            foreach ($inputInstitutionActionRequiredRightsDictionary as $rightName => $rightValue)
                if ($rightValue == true)
                    if ($institutionRightsRow[$rightName] == false)
                        return SuccessStates::NOT_ENOUGH_RIGHTS;
        }
        catch (Exception $operationException) {
            echo $operationException;
            return SuccessStates::OPERATION_EXCEPT;
        }
        
        return SuccessStates::SUCCESS;
    }

    private static $getHashedPasswordAndActiveStateQuery = "
        SELECT Hashed_Password, Is_Active FROM Users WHERE Username = :inputUsername;
    ";
    private static $getInstitutionMemberRightsQuery = "
        SELECT * FROM Institution_Rights WHERE ID = (
            SELECT Institution_Right_ID FROM Institution_Members WHERE
                User_ID = (SELECT ID FROM Users WHERE Username = :inputUsername) AND
                Institution_ID = (SELECT ID From Institutions WHERE Name = :inputInstitutionName)
        );
    ";
}

?>