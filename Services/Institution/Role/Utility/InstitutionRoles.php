<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . "/../../..");
}

require_once(ROOT . "/Utility/StatusCodes.php");
require_once(ROOT . "/Utility/CommonEndPointLogic.php");
require_once(ROOT . "/Utility/DatabaseManager.php");
require_once(ROOT . "/Utility/UserValidation.php");
require_once(ROOT . "/Utility/ResponseHandler.php");

require_once("Exceptions/InstitutionRolesInvalidStatement.php");
require_once("Exceptions/InstitutionRolesInvalidAction.php");
require_once("InstitutionActions.php");
require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");

/**
 * Class InstitutionRoles
 *
 * Utility class for Institution Operations within the Database
 *
 * Update 09/04/2020 : fixed
 * MUST INCLUDE DATABASEMANAGER, STATUSCODES, COMMONENDPOINTS before this one!!!!!!
 * (includes not working, relative to includer???! why?)
 */
class InstitutionRoles{

    /**
     * Function isUserAuthorized
     *
     * Checks whether a user is allowed to do an action in a given institution
     *
     * Possible Errors :
     *      INST_NOT_FOUND : if given an institution that does not exist
     *
     * @param $email            String              Email of the caller
     * @param $institutionName  String              Name of the institution
     * @param $action           int                 Action which is to be taken   (Taken from InstitutionActions)
     * @return                  bool                true if user is authorized, false otherwise
     * @throws                  InstitutionRolesInvalidAction
     */
    public static function isUserAuthorized($email, $institutionName, $action){

        InstitutionValidator::validateInstitution($institutionName);

        $rightsDictionary = self::fetchUserRightsDictionary($email, $institutionName);

        if($rightsDictionary == null){
            return false;
        }

        switch($action){
            case InstitutionActions::ADD_ROLE :                             return $rightsDictionary['Can_Add_Roles'];
            case InstitutionActions::DELETE_ROLE :                          return $rightsDictionary['Can_Remove_Roles'];
            case InstitutionActions::MODIFY_ROLE :                          return $rightsDictionary['Can_Modify_Roles'];
            case InstitutionActions::ASSIGN_ROLE :                          return $rightsDictionary['Can_Assign_Roles'];
            case InstitutionActions::DEASSIGN_ROLE :                        return $rightsDictionary['Can_Deassign_Roles'];
            case InstitutionActions::MODIFY_INSTITUTION :                   return $rightsDictionary['Can_Modify_Institution'];
            case InstitutionActions::DELETE_INSTITUTION :                   return $rightsDictionary['Can_Delete_Institution'];
            case InstitutionActions::ADD_MEMBERS :                          return $rightsDictionary['Can_Add_Members'];
            case InstitutionActions::REMOVE_MEMBERS :                       return $rightsDictionary['Can_Remove_Members'];
            case InstitutionActions::UPLOAD_DOCUMENTS :                     return $rightsDictionary['Can_Upload_Documents'];
            case InstitutionActions::PREVIEW_UPLOADED_DOCUMENTS :           return $rightsDictionary['Can_Preview_Uploaded_Documents'];
            case InstitutionActions::REMOVE_UPLOADED_DOCUMENTS :            return $rightsDictionary['Can_Remove_Uploaded_Documents'];
            case InstitutionActions::SEND_DOCUMENTS :                       return $rightsDictionary['Can_Send_Documents'];
            case InstitutionActions::PREVIEW_RECEIVED_DOCUMENTS :           return $rightsDictionary['Can_Preview_Received_Documents'];
            case InstitutionActions::PREVIEW_SPECIFIC_RECEIVED_DOCUMENT :   return $rightsDictionary['Can_Preview_Specific_Received_Document'];
            case InstitutionActions::REMOVE_RECEIVED_DOCUMENTS :            return $rightsDictionary['Can_Remove_Received_Documents'];
            case InstitutionActions::DOWNLOAD_DOCUMENTS :                   return $rightsDictionary['Can_Download_Documents'];
            case InstitutionActions::APPROVE_DOCUMENTS :                    return $rightsDictionary['Can_Approve_Documents'];

            default : throw new InstitutionRolesInvalidAction("Invalid Action");
        }
    }

    /**
     * Function adds a member by email to a certain institution with a specified role
     *
     * @param $email            string  Email of the user
     * @param $institutionName  string  Name of the institution
     * @param $roleName         string  Name of the role
     */
    public static function addAndAssignMemberToInstitution($email, $institutionName, $roleName){

        InstitutionValidator::validateInstitution($institutionName);

        try{
            DatabaseManager::Connect();

            $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getRoleIDStatement);
            $SQLStatement->bindParam(":title", $roleName);
            $SQLStatement->bindParam(":institutionID", $institutionID);

            $SQLStatement->execute();

            $role = $SQLStatement->fetch(PDO::FETCH_OBJ);

            if($role == null){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_NOT_FOUND"))
                    ->send();

                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("ROLE_NOT_FOUND");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
            }

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getUserIDStatement);
            $SQLStatement->bindParam(":email", $email);

            $SQLStatement->execute();

            $user = $SQLStatement->fetch(PDO::FETCH_OBJ);

            $SQLStatement = DatabaseManager::PrepareStatement(self::$insertIntoInstitutionMembersStatement);
            $SQLStatement->bindParam(":institutionID", $institutionID);
            $SQLStatement->bindParam(":userID", $user->ID);
            $SQLStatement->bindParam(":roleID", $role->ID);

            $SQLStatement->execute();

            if($SQLStatement->rowCount() == 0){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("MEMBER_DUPLICATE"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("MEMBER_DUPLICATE");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
            }

            DatabaseManager::Disconnect();
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
        }

    }

    /** Function that checks whether a role can be deleted/modified or not
     * 
     * Possible Errors : 
     *      DB_EXCEPT : caused by an exception triggered by the database
     * 
     * @param $roleID   int     ID of the role to be modified/deleted
     * @return          bool    True if it can be modified/deleted, false otherwise
     */
    public static function canModifyOrDeleteRole($roleID){
        $fullRoleRights = [
            "Can_Modify_Institution"                    => true,
            "Can_Delete_Institution"                    => true,
            "Can_Add_Members"                           => true,
            "Can_Remove_Members"                        => true,
            "Can_Upload_Documents"                      => true,
            "Can_Preview_Uploaded_Documents"            => true,
            "Can_Remove_Uploaded_Documents"             => true,
            "Can_Send_Documents"                        => true,
            "Can_Preview_Received_Documents"            => true,
            "Can_Preview_Specific_Received_Document"    => true,
            "Can_Remove_Received_Documents"             => true,
            "Can_Download_Documents"                    => true,
            "Can_Add_Roles"                             => true,
            "Can_Remove_Roles"                          => true,
            "Can_Modify_Roles"                          => true,
            "Can_Assign_Roles"                          => true,
            "Can_Deassign_Roles"                        => true,
            "Can_Approve_Documents"                     => true
        ];

        $rightsID = self::fetchRightsID($fullRoleRights);

        try {
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getRoleRightsStatement);
            $SQLStatement->bindParam(":ID", $roleID);
            $SQLStatement->execute();

            $rightsIDRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();
        }
        catch(Exception $exception) {
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

        return !($rightsID == $rightsIDRow->Institution_Rights_ID);
    }

    /**
     * Function which deletes a given role name in an institution
     * 
     * Possible Errors : 
     *      ROLE_NOT_FOUND : if a role does not exist in the database
     *      ROLE_CANNOT_BE_DELETED : caused by attempting to delete an institute's crucial role (i.e. Manager)
     *
     * @param $roleName             String the name of the role
     * @param $institutionName      String the name of the institution
     */
    public static function deleteRole($roleName, $institutionName){

        $roleID = self::getRoleID($roleName, $institutionName);
        if ($roleID == null) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_NOT_FOUND"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("ROLE_NOT_FOUND");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
            */
        }

        if(self::canModifyOrDeleteRole($roleID) == false){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_CANNOT_BE_DELETED"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("ROLE_CANNOT_BE_DELETED");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
            */
        }

        if(self::roleIsAssignedToMembers($roleID) == true){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_ASSIGNED_TO_MEMBER"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("ROLE_ASSIGNED_TO_MEMBER");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
            */
        }

        try {
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$deleteRoleStatement);
            $SQLStatement->bindParam(":ID", $roleID);

            $SQLStatement->execute();

            DatabaseManager::Disconnect();
        }
        catch(Exception $databaseException){
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

    /**
     * Function called to update a given role in a given institution
     *
     * Possible Errors :
     *      ROLE_NOT_FOUND : given role does not exist
     *
     * @param $roleName         String      name of the role to be changed
     * @param $institutionName  String      institution's name
     * @param $newRoleName      String      new role's name. Can be null
     * @param $newRoleRights    array<bool> new role rights dictionary
     */
    public static function updateRole($roleName, $institutionName, $newRoleName, $newRoleRights){

        $roleID = self::getRoleID($roleName, $institutionName);
        if($roleID == null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_NOT_FOUND"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("ROLE_NOT_FOUND");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
            */
        }

        self::updateRoleName($roleID, $newRoleName);

        if($newRoleRights != null){
            if(self::canModifyOrDeleteRole($roleID) == true){
               if(self::updateRoleRights($roleID, $newRoleRights) == false){

                   $rightsID = self::fetchRightsID($newRoleRights);

                   try{
                        DatabaseManager::Connect();

                        $SQLStatement = DatabaseManager::PrepareStatement(self::$updateRoleRightsStatement);

                        $SQLStatement->bindParam(":ID", $roleID);
                        $SQLStatement->bindParam(":rightsID", $rightsID);

                        $SQLStatement->execute();

//                        $SQLStatement->debugDumpParams();

                        if($SQLStatement->rowCount() == 0){
                            ResponseHandler::getInstance()
                                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_DUPLICATE_SAME_RIGHTS"))
                                ->send();
                            /*
                            $response = CommonEndPointLogic::GetFailureResponseStatus("ROLE_DUPLICATE_SAME_RIGHTS");

                            echo json_encode($response), PHP_EOL;
                            http_response_code(StatusCodes::OK);
                            die();
                            */
                        }

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
            else{
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_RIGHTS_UNMODIFIABLE"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("ROLE_RIGHTS_UNMODIFIABLE");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
            }
        }
    }

    private static function linkRoleWithRights($institutionID, $institutionRightsID, $roleTitle){
        try {
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$insertNewRoleStatement);
            $SQLStatement->bindParam(":institutionID", $institutionID);
            $SQLStatement->bindParam(":institutionRightsID", $institutionRightsID);
            $SQLStatement->bindParam(":title", $roleTitle);

            $SQLStatement->execute();

            if ($SQLStatement->rowCount() == 0) {
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("ROLE_DUPLICATE_SAME_RIGHTS"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("ROLE_DUPLICATE_SAME_RIGHTS");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
            }

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

    /**
     * Function called to update a given role's rights
     *
     * Possible Errors :
     *      DUPLICATE_RIGHTS : if, by updating the rights, you get the rights another role has
     *      DB_EXCEPT        : exception triggered by the database
     *
     * @param $roleID                   int         id of the role
     * @param $newRoleRightsDictionary  array<bool> new dictionary of the role's right
     * @return                          bool        true if successful, false otherwise ( for bug treatment )
     */
    private static function updateRoleRights($roleID, $newRoleRightsDictionary){

        try{
            $status = true;

            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getRoleRightsStatement);
            $SQLStatement->bindParam(":ID", $roleID);

            $SQLStatement->execute();

//            $SQLStatement->debugDumpParams();

            $rightsID = $SQLStatement->fetch(PDO::FETCH_OBJ);

            $SQLStatement = self::generateRightsStatement(self::GENERATE_RIGHTS_UPDATE_ROW_STATEMENT, $newRoleRightsDictionary);
            $SQLStatement->bindParam(":ID", $rightsID->Institution_Rights_ID);

            $SQLStatement->execute();

//            $SQLStatement->debugDumpParams();

            if($SQLStatement->rowCount() == 0){
                /**
                 * FIXED
                 * BUG : Two roles from different institutions cannot have same rights
                 * Temporary fix : Allow roles with same rights to exists within same institution (Unique in table fixes this)
                 */
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_RIGHTS");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
                $status = false;
            }

            DatabaseManager::Disconnect();

            return $status;
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
            die();
        }

    }

    /**
     * Function called to update a role's name (by id)
     * 
     * Possible Errors : 
     *      DB_EXCEPT : caused by an exception triggered by the database
     *
     * @param $roleID       int     id of the role
     * @param $newRoleName  String  new role's name
     */
    private static function updateRoleName($roleID, $newRoleName){

        try {
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$updateRoleNameStatement);
            $SQLStatement->bindParam(":newName", $newRoleName);
            $SQLStatement->bindParam(":ID", $roleID);

            $SQLStatement->execute();

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

    /**
     * Function which returns a given user's rights dictionary in a given institution
     *
     * Rights dictionary example : [
     *       "Can_Modify_Institution"                    => true,
     *       "Can_Delete_Institution"                    => true,
     *       "Can_Add_Members"                           => true,
     *       "Can_Remove_Members"                        => false,
     *       "Can_Upload_Documents"                      => false,
     *       "Can_Preview_Uploaded_Documents"            => true,
     *       "Can_Remove_Uploaded_Documents"             => false,
     *       "Can_Send_Documents"                        => true,
     *       "Can_Preview_Received_Documents"            => false,
     *       "Can_Preview_Specific_Received_Document"    => true,
     *       "Can_Remove_Received_Documents"             => false,
     *       "Can_Download_Documents"                    => true,
     *       "Can_Add_Roles"                             => false,
     *       "Can_Remove_Roles"                          => false,
     *       "Can_Modify_Roles"                          => true,
     *       "Can_Assign_Roles"                          => true,
     *       "Can_Deassign_Roles"                        => true
     *       ]
     *
     * Possible Errors:
     *      INST_NOT_FOUND : if given an institution that does not exist
     *      DB_EXCEPT      : database exception
     *
     * @param $email                String      email whose rights are being returned
     * @param $institutionName      String      name of the institution
     * @return                      array<bool> dictionary of the user's rights in the given institution
     */
    public static function fetchUserRightsDictionary($email, $institutionName){

        InstitutionValidator::validateInstitution($institutionName);

        try {
            DatabaseManager::Connect();

            $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$fetchRightsForUserInInstitution);
            $SQLStatement->bindParam(":email", $email);
            $SQLStatement->bindParam(":institutionID", $institutionID);
            $SQLStatement->execute();

            $rightsDictionary = $SQLStatement->fetch(PDO::FETCH_ASSOC);

//            print_r($rightsDictionary);

            DatabaseManager::Disconnect();

            return $rightsDictionary;
        }
        catch(Exception $databaseException){
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

    /**
     * Function that returns a given role's ID in a given institution in the Database
     *
     * Possible Errors:
     *      INST_NOT_FOUND : if given an institution that does not exist
     *      DB_EXCEPT :      if database triggers an exception
     *
     * @param $roleName         string  Name of the role
     * @param $institutionName  string  Name of the institution in which the role exists
     * @return                  int     ID of the role
     */
    public static function getRoleID($roleName, $institutionName){

        InstitutionValidator::validateInstitution($institutionName);

        try{
            DatabaseManager::Connect();

            $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getRoleIDStatement);
            $SQLStatement->bindParam(":institutionID", $institutionID);
            $SQLStatement->bindParam(":title", $roleName);

            $SQLStatement->execute();

            $row = $SQLStatement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            if($row == null)
                return null;

            return $row->ID;
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
            die();
        }
    }

    /**
     * Function that creates a role with a given name for a given institution with given rights
     *
     * Possible Errors :
     *      INST_NOT_FOUND              : if given an institution that does not exist
     *      DUPLICATE_ROLE              : if given a role that already exists
     *      DUPLICATE_ROLE_SAME_RIGHTS  : if given a role with set of rights S, there is already
     *          a role in given institution with the same S set of rights
     *      DB_EXCEPT                   :
     *
     * @param $roleName                 String      name of the role to be added
     * @param $institutionName          String      name of the given institution
     * @param $newRoleRightsDictionary  array<bool> dictionary of rights to be administered to the role
     */
    public static function createRole($roleName, $institutionName, $newRoleRightsDictionary){

        InstitutionValidator::validateInstitution($institutionName);

        if(self::getRoleID($roleName,$institutionName) != null){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_ROLE"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_ROLE");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);

            die();
            */
        }

        $rightsID = self::fetchRightsID($newRoleRightsDictionary);
        $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();

        self::linkRoleWithRights($institutionID, $rightsID, $roleName);
    }

    /**
     * Function that returns the ID of the given rights dictionary in the Database
     *
     * Possible Errors :
     *      DB_EXCEPT : if database triggers an exception
     *
     * @param $rightsDictionary array<bool> rights which are being looked up
     * @return                  int         id of the rights in the database
     */
    public static function fetchRightsID($rightsDictionary){

        DatabaseManager::Connect();

        try{
            $SQLStatement = InstitutionRoles::generateRightsStatement(InstitutionRoles::GENERATE_RIGHTS_FETCH_ID_STATEMENT, $rightsDictionary);
            $SQLStatement->execute();
            $row = $SQLStatement->fetch(PDO::FETCH_OBJ);

            if($row == null){
                $maxIDStatement = DatabaseManager::PrepareStatement(self::$fetchMaxIDStatement);
                $maxIDStatement->execute();

                $maxIDRow = $maxIDStatement->fetch();
                $ID = $maxIDRow[0] + 1;

                $SQLStatement = InstitutionRoles::generateRightsStatement(self::GENERATE_RIGHTS_INSERT_NEW_ROW_STATEMENT, $rightsDictionary);
                $SQLStatement->bindParam(":ID", $ID);
                $SQLStatement->execute();
            }
            else{
                $ID = $row->ID;
            }
        }
        catch(InstitutionRolesInvalidStatement $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

            echo json_encode($response), PHP_EOL, $exception->getMessage(), PHP_EOL;
            http_response_code(StatusCodes::OK);

            die();
            */
            die();
        }

        DatabaseManager::Disconnect();

        return $ID;
    }

    /**
     * Internal function used to minimize code
     * Returns an SQL Prepared Statement for a certain Rights action with parameters attached
     *
     * Possible Errors :
     *      None
     *
     * @param $statementType    int                 constant defining the Statement to be given
     * @param $rightsDictionary array<bool>         dictionary of rights assigned to params
     * @return                  Object              SQL Prepared Statement that was requested
     * @throws                  InstitutionRolesInvalidStatement
     */
    private static function generateRightsStatement($statementType, $rightsDictionary){
        switch($statementType) {
            case self::GENERATE_RIGHTS_FETCH_ID_STATEMENT :
                $SQLStatement = DatabaseManager::PrepareStatement(self::$fetchRightsIDStatement);
                break;

            case self::GENERATE_RIGHTS_INSERT_NEW_ROW_STATEMENT :
                $SQLStatement = DatabaseManager::PrepareStatement(self::$insertRightsStatement);
                break;

            case self::GENERATE_RIGHTS_UPDATE_ROW_STATEMENT :
                $SQLStatement = DatabaseManager::PrepareStatement(self::$updateRightsStatement);
                break;

            default: throw new InstitutionRolesInvalidStatement("Incorrect Statement");
        }

        $SQLStatement->bindParam(':canModifyInstitution',               $rightsDictionary['Can_Modify_Institution'],                    PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canDeleteInstitution',               $rightsDictionary['Can_Delete_Institution'],                    PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canAddMembers',                      $rightsDictionary['Can_Add_Members'],                           PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canRemoveMembers',                   $rightsDictionary['Can_Remove_Members'],                        PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canUploadDocuments',                 $rightsDictionary['Can_Upload_Documents'],                      PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canPreviewUploadedDocuments',        $rightsDictionary['Can_Preview_Uploaded_Documents'],            PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canRemoveUploadedDocuments',         $rightsDictionary['Can_Remove_Uploaded_Documents'],             PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canSendDocuments',                   $rightsDictionary['Can_Send_Documents'],                        PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canPreviewReceivedDocuments',        $rightsDictionary['Can_Preview_Received_Documents'],            PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canPreviewSpecificReceivedDocument', $rightsDictionary['Can_Preview_Specific_Received_Document'],    PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canRemoveReceivedDocuments',         $rightsDictionary['Can_Remove_Received_Documents'],             PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canDownloadDocuments',               $rightsDictionary['Can_Download_Documents'],                    PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canAddRoles',                        $rightsDictionary['Can_Add_Roles'],                             PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canRemoveRoles',                     $rightsDictionary['Can_Remove_Roles'],                          PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canModifyRoles',                     $rightsDictionary['Can_Modify_Roles'],                          PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canAssignRoles',                     $rightsDictionary['Can_Assign_Roles'],                          PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canDeassignRoles',                   $rightsDictionary['Can_Deassign_Roles'],                        PDO::PARAM_BOOL);
        $SQLStatement->bindParam(':canApproveDocuments',                $rightsDictionary['Can_Approve_Documents'],                     PDO::PARAM_BOOL);
        return $SQLStatement;
    }

    /**
     * Function that checks whether there are any members with a certain role ID
     *
     * @param $roleID   int     ID of the role
     * @return          bool    True if any members with role exist, false otherwise
     */
    private static function roleIsAssignedToMembers($roleID){

        try{
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$fetchMembersWithRoleID);
            $SQLStatement->bindParam(":ID", $roleID);

            $SQLStatement->execute();

            DatabaseManager::Disconnect();

            return $SQLStatement->rowCount() > 0;
        }
        catch (Exception $exception){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                ->send();
            /*
            $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

            echo json_encode($response), PHP_EOL, $exception->getMessage(), PHP_EOL;
            http_response_code(StatusCodes::OK);

            die();
            */
            die();
        }
    }

    const GENERATE_RIGHTS_FETCH_ID_STATEMENT = 0;
    const GENERATE_RIGHTS_INSERT_NEW_ROW_STATEMENT = 1;
    const GENERATE_RIGHTS_UPDATE_ROW_STATEMENT = 2;

    private static $fetchMembersWithRoleID = "
        SELECT ID FROM institution_members WHERE Institution_Roles_ID = :ID
    ";

    private static $insertIntoInstitutionMembersStatement = "
        INSERT INTO institution_members (Institution_ID, User_ID, Institution_Roles_ID, DateTime_Added, DateTime_Modified_Rights)
            VALUE
        (:institutionID, :userID, :roleID, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ";

    private static $getUserIDStatement = "
        SELECT ID FROM users WHERE Email = :email
    ";

    private static $deleteRoleStatement = "
        DELETE FROM institution_roles WHERE ID = :ID
    ";

    private static $getRoleRightsStatement = "
        SELECT Institution_Rights_ID FROM institution_roles WHERE ID = :ID
    ";

    private static $updateRoleNameStatement = "
        UPDATE institution_roles SET Title = :newName WHERE ID = :ID
    ";

    private static $updateRoleRightsStatement = "
        UPDATE institution_roles SET Institution_Rights_ID = :rightsID WHERE ID = :ID
    ";

    private static $getRoleIDStatement = "
        SELECT ID FROM institution_roles WHERE Institution_ID = :institutionID AND Title = :title
    ";

    private static $insertNewRoleStatement = "
        INSERT INTO institution_roles (Institution_ID, Institution_Rights_ID, Title)
            value
            (:institutionID, :institutionRightsID, :title)
    ";

    private static $fetchMaxIDStatement = "
        SELECT MAX(ID) FROM institution_rights;    
    ";

    private static $fetchRightsForUserInInstitution = "
        SELECT
            Can_Modify_Institution,
            Can_Delete_Institution,
            Can_Add_Members,
            Can_Remove_Members,
            Can_Upload_Documents,
            Can_Preview_Uploaded_Documents,
            Can_Remove_Uploaded_Documents,
            Can_Send_Documents,
            Can_Preview_Received_Documents,
            Can_Preview_Specific_Received_Document,
            Can_Remove_Received_Documents,
            Can_Download_Documents,
            Can_Add_Roles,
            Can_Remove_Roles,
            Can_Modify_Roles,
            Can_Assign_Roles,
            Can_Deassign_Roles,
            Can_Approve_Documents
        FROM institution_rights WHERE ID = (
            SELECT Institution_Rights_ID FROM institution_roles WHERE ID = (
                SELECT Institution_Roles_ID FROM institution_members WHERE User_ID = (
                    SELECT ID FROM users WHERE Email = :email
                )
                AND Institution_ID = :institutionID
            )
        )
    ";

    private static $fetchRightsIDStatement = "
        SELECT ID FROM institution_rights where
            Can_Modify_Institution =                    :canModifyInstitution               AND
            Can_Delete_Institution =                    :canDeleteInstitution               AND
            Can_Add_Members =                           :canAddMembers                      AND
            Can_Remove_Members =                        :canRemoveMembers                   AND 
            Can_Upload_Documents =                      :canUploadDocuments                 AND
            Can_Preview_Uploaded_Documents =            :canPreviewUploadedDocuments        AND
            Can_Remove_Uploaded_Documents =             :canRemoveUploadedDocuments         AND
            Can_Send_Documents =                        :canSendDocuments                   AND
            Can_Preview_Received_Documents =            :canPreviewReceivedDocuments        AND
            Can_Preview_Specific_Received_Document =    :canPreviewSpecificReceivedDocument AND
            Can_Remove_Received_Documents =             :canRemoveReceivedDocuments         AND
            Can_Download_Documents =                    :canDownloadDocuments               AND
            Can_Add_Roles =                             :canAddRoles                        AND
            Can_Remove_Roles =                          :canRemoveRoles                     AND
            Can_Modify_Roles =                          :canModifyRoles                     AND
            Can_Assign_Roles =                          :canAssignRoles                     AND
            Can_Deassign_Roles =                        :canDeassignRoles                   AND
            Can_Approve_Documents =                     :canApproveDocuments                                 
        ";

    private static $updateRightsStatement = "
        UPDATE institution_rights SET 
            Can_Modify_Institution                  = :canModifyInstitution,
            Can_Delete_Institution                  = :canDeleteInstitution,
            Can_Add_Members                         = :canAddMembers,
            Can_Remove_Members                      = :canRemoveMembers,
            Can_Upload_Documents                    = :canUploadDocuments,
            Can_Preview_Uploaded_Documents          = :canPreviewUploadedDocuments,
            Can_Remove_Uploaded_Documents           = :canRemoveUploadedDocuments,
            Can_Send_Documents                      = :canSendDocuments,
            Can_Preview_Received_Documents          = :canPreviewReceivedDocuments,
            Can_Preview_Specific_Received_Document  = :canPreviewSpecificReceivedDocument,
            Can_Remove_Received_Documents           = :canRemoveReceivedDocuments,
            Can_Download_Documents                  = :canDownloadDocuments,
            Can_Add_Roles                           = :canAddRoles,
            Can_Remove_Roles                        = :canRemoveRoles,
            Can_Modify_Roles                        = :canModifyRoles,
            Can_Assign_Roles                        = :canAssignRoles,
            Can_Deassign_Roles                      = :canDeassignRoles,
            Can_Approve_Documents                   = :canApproveDocuments
        WHERE
            ID = :ID
    ";

    private static $insertRightsStatement = "
        INSERT INTO institution_rights (
            ID,
            Can_Modify_Institution,
            Can_Delete_Institution,
            Can_Add_Members,
            Can_Remove_Members,
            Can_Upload_Documents,
            Can_Preview_Uploaded_Documents,
            Can_Remove_Uploaded_Documents,
            Can_Send_Documents,
            Can_Preview_Received_Documents,
            Can_Preview_Specific_Received_Document,
            Can_Remove_Received_Documents,
            Can_Download_Documents,
            Can_Add_Roles,
            Can_Remove_roles,
            Can_Modify_Roles,
            Can_Assign_Roles,
            Can_Deassign_Roles,
            Can_Approve_Documents         
        ) value (
            :ID,
            :canModifyInstitution, 
            :canDeleteInstitution,
            :canAddMembers,
            :canRemoveMembers,
            :canUploadDocuments,
            :canPreviewUploadedDocuments,
            :canRemoveUploadedDocuments,
            :canSendDocuments,
            :canPreviewReceivedDocuments,
            :canPreviewSpecificReceivedDocument,
            :canRemoveReceivedDocuments,
            :canDownloadDocuments,
            :canAddRoles,
            :canRemoveRoles,
            :canModifyRoles,
            :canAssignRoles,
            :canDeassignRoles,  
            :canApproveDocuments
        )
    ";

}
?>
