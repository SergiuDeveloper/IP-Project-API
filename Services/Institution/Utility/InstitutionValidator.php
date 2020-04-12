<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . "/../..");
}

require_once(ROOT . "/Utility/StatusCodes.php");
require_once(ROOT . "/Utility/CommonEndPointLogic.php");
require_once(ROOT . "/Utility/DatabaseManager.php");
require_once(ROOT . "/Utility/UserValidation.php");
require_once(ROOT . "/Utility/ResponseHandler.php");

/**
 * Class InstitutionValidator
 *
 * Utility Class used for validating an institution's existence.
 * Also Used to get ID of last validated institution (Singleton)
 */
class InstitutionValidator{

    /**
     * Function called to validate a given institution
     *
     * Possible Errors :
     *      INST_NOT_FOUND : if given an institution that does not exist
     *      DB_EXCEPT      : if database triggers an exception
     *
     * @param $institutionName  String institution's name
     * @return                  void
     */
    public static function validateInstitution($institutionName){

        if(self::getLastValidatedInstitution()->institutionName == $institutionName){
            return;
        }
        else{
            self::getLastValidatedInstitution()->institutionName = $institutionName;
        }

        try{
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$fetchInstitutionID);
            $SQLStatement->bindParam(":name", $institutionName);
            $SQLStatement->execute();

            $row = $SQLStatement->fetch(PDO::FETCH_OBJ);

            if($row == null){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INST_NOT_FOUND"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("INST_NOT_FOUND");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
            }

            self::getLastValidatedInstitution()->institutionID = $row->ID;

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
     * Getter function for the singleton object (last validated class)
     *
     * @return InstitutionValidator Pointer to the object containing last checked institution's id and name
     */
    public static function getLastValidatedInstitution(){
        if(self::$institution == null)
            self::$institution = new InstitutionValidator();

        return self::$institution;
    }

    /**
     * @return String name of the institution
     */
    public function getName(){
        return $this->institutionName;
    }

    /**
     * @return int ID of the institution
     */
    public function getID(){
        return $this->institutionID;
    }

    /**
     * InstitutionValidator constructor.
     */
    private function __construct(){
        $this->institutionName = null;
        $this->institutionID = null;
    }

    private static $institution = null;
    private $institutionName;
    private $institutionID;

    private static $fetchInstitutionID = "
        SELECT ID FROM institutions WHERE Name = :name
    ";

}
