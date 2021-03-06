<?php

if(!defined('BUILDING_NOT_SPECIFIED')){
    define ('BUILDING_NOT_SPECIFIED', "NOT_SPECIFIED");
}

if(!defined('FLOOR_NOT_SPECIFIED')) {
    define('FLOOR_NOT_SPECIFIED', -1);
}

if(!defined('APARTMENT_NOT_SPECIFIED')) {
    define('APARTMENT_NOT_SPECIFIED', -1);
}

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . "/../..");
}

if(!defined('DISABLE_CIF_VALIDITY')) {
    define('DISABLE_CIF_VALIDITY', true);
}

require_once(ROOT . "/Utility/StatusCodes.php");
require_once(ROOT . "/Utility/CommonEndPointLogic.php");
require_once(ROOT . "/Utility/DatabaseManager.php");
require_once(ROOT . "/Utility/UserValidation.php");
require_once(ROOT . "/Utility/ResponseHandler.php");

/**
 * Class InstitutionCreation
 *
 * Uses Address (mixed array). Example :
 *
 * AddressArray = [
 *      'Country'   => 'Romania',               NOT_NULL
 *      'Region'    => 'Iasi',                  NOT_NULL
 *      'City'      => 'Iasi',                  NOT_NULL
 *      'Street'    => 'Strapungere Silvestru', NOT_NULL
 *      'Number'    =>  33,                     NOT_NULL
 *      'Building'  => 'T6',
 *      'Floor'     =>  4,
 *      'Apartment' =>  20
 * ]
 */
class InstitutionCreation
{

    public static function validateCIF($cif){
        if(DISABLE_CIF_VALIDITY)
            return true;

        if(!is_integer($cif)){
            $cif = strtoupper($cif);
            if(strpos($cif, 'RO') === 0){
                $cif = substr($cif, 2);
            }
            $cif = (int)trim($cif);
        }

        if(strlen($cif) > 10 || strlen($cif) < 2)
            return false;

        $controlValue = 753217532;

        $controlDigit = $cif%10;
        $cif = (int)($cif/10);

        $aux = 0;
        while($cif > 0){
            $aux += ($cif%10) * ($controlValue%10);
            $cif = (int) ($cif / 10);
            $controlValue = (int) ($controlValue/10);
        }

        $controlDigitMultiply = ($aux * 10) / 11;

        if($controlDigitMultiply == 10)
            $controlDigitMultiply = 0;

        return $controlDigit == $controlDigitMultiply;
    }

    /**
     * Function links an institution with an address
     *
     * @param $institutionID    int     ID of the institution
     * @param $addressID        int     ID of the address
     * @param $isMainAddress    bool    true if address is main, false otherwise. True by default
     */
    public static function linkInstitutionWithAddress($institutionID, $addressID, $isMainAddress = true){

        try{
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$insertInstitutionAddressStatement);
            $SQLStatement->bindParam(":institutionID", $institutionID);
            $SQLStatement->bindParam(":addressID", $addressID);
            $SQLStatement->bindParam(":isMainAddress", $isMainAddress);

            $SQLStatement->execute();

            if($SQLStatement->rowCount() == 0){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("MAIN_ADDRESS_DUPLICATE"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("MAIN_ADDRESS_DUPLICATE");

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
     * Inserts an institution into the database and returns its ID
     *
     * @param $institutionName  string  Name of the institution to be added
     * @param $CIF              string  Institution CIF
     * @return                  int     ID of the institution
     */
    public static function insertInstitutionIntoDatabase($institutionName, $CIF){

        if(!self::validateCIF($CIF)){
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_CIF"))
                ->send();
        }

        try{
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$insertInstitutionStatement);
            $SQLStatement->bindParam(":institutionName", $institutionName);
            $SQLStatement->bindParam(":cif", $CIF);

            $SQLStatement->execute();

            if($SQLStatement->rowCount() == 0){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_INST"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_INST");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
            }

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getInstitutionStatement);
            $SQLStatement->bindParam(":institutionName", $institutionName);

            $SQLStatement->execute();

            $row = $SQLStatement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            return $row->ID;
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
    }

    /**
     * Function inserts an address into the database
     *
     * @param $address  array (mixed)   address (check class declaration)
     * @return          int             ID of the address in the database. If it exists, returns the ID of the existing address
     */
    public static function insertAddressIntoDatabase($address){

        try{
            DatabaseManager::Connect();

            /**
             * Incomplete statement due to possible parameters
             */
            $SQLStatement = DatabaseManager::PrepareStatement(self::$insertAddressStatement);

            self::bindParametersForAddressStatement($SQLStatement, $address);

            $SQLStatement->execute();

            $SQLPreparedStatement = self::$getAddressID;

            $SQLStatement = DatabaseManager::PrepareStatement($SQLPreparedStatement);

            self::bindParametersForAddressStatement($SQLStatement, $address);

            $SQLStatement->execute();

            $row = $SQLStatement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

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
     * @param $SQLStatement PDOStatement    Statement for which the parameters will be bound
     * @param $address      array [mixed]   Address Array (refer class declaration)
     */
    private static function bindParametersForAddressStatement(& $SQLStatement, $address){
        $building   = $address['Building']  == null ? BUILDING_NOT_SPECIFIED    : $address['Building'];
        $floor      = $address['Floor']     == null ? FLOOR_NOT_SPECIFIED       : $address['Floor'];
        $apartment  = $address['Apartment'] == null ? APARTMENT_NOT_SPECIFIED   : $address['Apartment'];

        $SQLStatement->bindParam(":country",    $address['Country']);
        $SQLStatement->bindParam(":region",     $address['Region']);
        $SQLStatement->bindParam(":city",       $address['City']);
        $SQLStatement->bindParam(":street",     $address['Street']);
        $SQLStatement->bindParam(":number",     $address['Number']);
        $SQLStatement->bindParam(":building", $building);
        $SQLStatement->bindParam(":floor",      $floor);
        $SQLStatement->bindParam(":apartment",  $apartment);
    }

    /**
     * Function checks whether an address is valid (refer to class declaration)
     *
     * @param $address  array (mixed)   Address array
     * @return          bool            True if address is valid, false otherwise
     */
    public static function checkAddressValidity($address){
        if(
            $address['Country'] == null ||
            $address['Region']  == null ||
            $address['City']    == null ||
            $address['Street']  == null ||
            $address['Number']  == null
        )
            return false;

        return true;
    }

    /**
     * Function checks for institution duplicate. Might be deprecated soon (Inst. Validator works properly now)
     *
     * @param $institutionName  string  name of the institution
     * @return                  bool    true if institution exists, false otherwise
     */
    public static function checkForInstitutionDuplicate($institutionName){

        try{
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getInstitutionStatement);
            $SQLStatement->bindParam(":institutionName", $institutionName);

            $SQLStatement->execute();

            $row = $SQLStatement->fetch();

            DatabaseManager::Disconnect();

            echo $row, PHP_EOL;

            return $row != null;
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

    private static $insertInstitutionAddressStatement = "
        INSERT INTO institution_addresses_list (
            Institution_ID, Address_ID, Is_Main_Address
        ) 
        VALUE 
        (
            :institutionID, :addressID, :isMainAddress
        )
    ";

    private static $getInstitutionStatement = "
        SELECT ID FROM institutions WHERE Name = :institutionName
    ";

    private static $insertInstitutionStatement = "
        INSERT INTO institutions (Name, DateTime_Created, DateTime_Modified, CIF) VALUE 
            (:institutionName, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :cif)
    ";

    private static $getAddressID = "
        SELECT ID FROM addresses WHERE 
            Country     = :country      AND
            Region      = :region       AND
            City        = :city         AND
            Street      = :street       AND
            Number      = :number       AND
            Building    = :building     AND
            Floor       = :floor        AND
            Apartment   = :apartment
    ";

    private static $insertAddressStatement = "
        INSERT INTO addresses (Country, Region, City, Street, Number, Building, Floor, Apartment) 
        VALUE (:country, :region, :city, :street, :number, :building, :floor, :apartment)
    ";

}
?>
