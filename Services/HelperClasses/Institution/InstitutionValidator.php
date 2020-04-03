<?php


class InstitutionValidator{
    public static function validateInstitution($institutionName){

        if(self::getLastValidatedInstitution()->institutionName == $institutionName){
            return self::getLastValidatedInstitution()->institutionID;
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
                $response = CommonEndPointLogic::GetFailureResponseStatus("INST_NOT_FOUND");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
            }

            self::getLastValidatedInstitution()->institutionID = $row->ID;

            DatabaseManager::Disconnect();
        }
        catch(Exception $databaseException){
            $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }
    }

    public static function getLastValidatedInstitution(){
        if(self::$institution == null)
            self::$institution = new InstitutionValidator();

        return self::$institution;
    }

    public function getName(){
        return $this->institutionName;
    }

    public function getID(){
        return $this->institutionID;
    }

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

?>