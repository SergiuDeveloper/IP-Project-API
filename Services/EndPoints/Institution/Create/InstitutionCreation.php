<?php


class InstitutionCreation
{

    public static function linkInstitutionWithAddress($institutionID, $addressID){

        try{
            DatabaseManager::Connect();

            $isMainAddress = 1;

            $SQLStatement = DatabaseManager::PrepareStatement(self::$insertInstitutionAddressStatement);
            $SQLStatement->bindParam(":institutionID", $institutionID);
            $SQLStatement->bindParam(":addressID", $addressID);
            $SQLStatement->bindParam(":isMainAddress", $isMainAddress);

            $SQLStatement->execute();

            if($SQLStatement->rowCount() == 0){
                $response = CommonEndPointLogic::GetFailureResponseStatus("MAIN_ADDRESS_DUPLICATE");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
            }

            DatabaseManager::Disconnect();
        }
        catch(Exception $exception){
            $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }

    }

    public static function insertInstitutionIntoDatabase($institutionName){

        try{
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$insertInstitutionStatement);
            $SQLStatement->bindParam(":institutionName", $institutionName);

            $SQLStatement->execute();

            if($SQLStatement->rowCount() == 0){
                $response = CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_ADDRESS");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
            }

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getInstitutionStatement);
            $SQLStatement->bindParam(":institutionName", $institutionName);

            $SQLStatement->execute();

            $row = $SQLStatement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            return $row->ID;
        }
        catch(Exception $exception) {
            $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }

    }

    public static function insertAddressIntoDatabase($address){

        try{
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$insertAddressStatement);
            self::bindParametersForAddressStatement($SQLStatement, $address);

            $SQLStatement->execute();

            ///ORICUM VA RETURNA ID-ul, daca o adresa e neasignata, de ce sa o stergem?
            /*
            if($SQLStatement->rowCount() == 0){
                $response = CommonEndPointLogic::GetFailureResponseStatus("DUPLICATE_ADDRESS");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
            }*/

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getAddressID);
            self::bindParametersForAddressStatement($SQLStatement, $address);

            $SQLStatement->execute();

            $row = $SQLStatement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            return $row->ID;
        }
        catch(Exception $exception){
            $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
            die();
        }

    }

    private static function bindParametersForAddressStatement(& $SQLStatement, $address){
        $SQLStatement->bindParam(":country",    $address['Country']);
        $SQLStatement->bindParam(":region",     $address['Region']);
        $SQLStatement->bindParam(":city",       $address['City']);
        $SQLStatement->bindParam(":street",     $address['Street']);
        $SQLStatement->bindParam(":number",     $address['Number']);
        $SQLStatement->bindParam(":building",   $address['Building']);
        $SQLStatement->bindParam(":floor",      $address['Floor']);
        $SQLStatement->bindParam(":apartment",  $address['Apartment']);
    }

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

    public static function checkForInstitutionDuplicate($institutionName){

        try{
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(self::$getInstitutionStatement);
            $SQLStatement->bindParam(":institutionName", $institutionName);

            $SQLStatement->execute();

            $row = $SQLStatement->fetch();

            DatabaseManager::Disconnect();

            return $row == null;
        }
        catch(Exception $exception){
            $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

            echo json_encode($response), PHP_EOL;
            http_response_code(StatusCodes::OK);
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

    private static $insertAddressStatement = "
        INSERT INTO addresses (
           Country, Region, City, Street, Number, Building, Floor, Apartment
        ) 
        VALUE (:country, :region, :city, :street, :number, :building, :floor, :apartment)
    ";

    private static $insertInstitutionStatement = "
        INSERT INTO institutions (Name, DateTime_Created, DateTime_Modified) VALUE 
            (:institutionName, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
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
}

?>