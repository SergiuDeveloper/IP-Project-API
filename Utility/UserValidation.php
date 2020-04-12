<?php

if( !defined('ROOT') ){
    define ('ROOT', dirname(__FILE__) . '/..');
}

require_once(ROOT . "/Utility/DatabaseManager.php");
require_once(ROOT . "/Utility/SuccessStates.php");
require_once(ROOT . "/Utility/ResponseHandler.php");

/**
    Validation class for user-related actions
*/
class UserValidation {
    /**
     *  @param $inputEmail              string              = User's associated email
     *  @param $inputHashedPassword     string              = User's associated hashed password
     *  @return                         int (SuccessState)  = User credentials validation state
     */
    public static function ValidateCredentials($inputEmail, $inputHashedPassword) {
        try {
            DatabaseManager::Connect();

            $getHashedPasswordAndActiveStateStatement = DatabaseManager::PrepareStatement(UserValidation::$getHashedPasswordAndActiveStateQuery);
            $getHashedPasswordAndActiveStateStatement->bindParam(":inputEmail", $inputEmail);
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
        
        $state = password_verify($inputHashedPassword, $usersTableRow->Hashed_Password);
    
        if (!$state)
            return SuccessStates::WRONG_PASSWORD; 
        
        if (!$usersTableRow->Is_Active)
            return SuccessStates::USER_INACTIVE;

        return SuccessStates::SUCCESS;
    }

    /**
     *  @deprecated
     *  Usage example:
     *  <
     *      $inputInstitutionActionRequiredRightsDictionary = [
     *          "Can_Modify_Institution"                    => true,
     *          "Can_Delete_Institution"                    => true,
     *          "Can_Add_Members"                           => true,
     *          "Can_Remove_Members"                        => false,
     *          "Can_Upload_Documents"                      => false,
     *          "Can_Preview_Uploaded_Documents"            => true,
     *          "Can_Remove_Uploaded_Documents"             => false,
     *          "Can_Send_Documents"                        => true,
     *          "Can_Preview_Received_Documents"            => false,
     *          "Can_Preview_Specific_Received_Document"    => true,
     *          "Can_Remove_Received_Documents"             => false,
     *          "Can_Download_Documents"                    => true,
     *          "Can_Add_Roles"                             => false,
     *          "Can_Remove_Roles"                          => false,
     *          "Can_Modify_Roles"                          => true,
     *          "Can_Assign_Roles"                          => true,
     *          "Can_Deassign_Roles"                        => true
     *       ];
     *
     *       echo (UserValidation::ValidateInstitutionActionRequiredRights("testuser", "testname", $inputInstitutionActionRequiredRightsDictionary) == true ? "true" : "false"), PHP_EOL;
     *   >
     *  @param $inputUsername                                   string                  = User's associated username
     *  @param $inputInstitutionName                            string                  = The institution's associated name
     *  @param $inputInstitutionActionRequiredRightsDictionary  array   (key => value)  = The required institution rights to perform a certain action
     *  @return                                                 int     (SuccessState)  = User's possession of the required rights, for the given institution
     *
     *  Old rights validation
     *  Mark as safe for delete : SergiuDeveloper
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
        SELECT Hashed_Password, Is_Active FROM Users WHERE Email = :inputEmail;
    ";

    /**
     * @var string
     * @deprecated
     */
     private static $getInstitutionMemberRightsQuery = "
        SELECT * FROM Institution_Rights WHERE ID = (
            SELECT Institution_Roles_ID FROM Institution_Members WHERE
                User_ID = (SELECT ID FROM Users WHERE Username = :inputUsername) AND
                Institution_ID = (SELECT ID From Institutions WHERE Name = :inputInstitutionName)
        );
    ";
}

?>