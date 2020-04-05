<?php

require_once ("../../../HelperClasses/DatabaseManager.php");
require_once ("../../../HelperClasses/StatusCodes.php");
require_once ("../../../HelperClasses/ValidationHelper.php");
require_once ("../../../HelperClasses/CommonEndPointLogic.php");
require_once ("../../../HelperClasses/Institution/InstitutionValidator.php");

    CommonEndPointLogic::ValidateHTTPGetRequest();

    $username = $_GET["username"];
    $hashedPassword = $_GET["hashedPassword"];
    $institutionName = $_GET["institutionName"];

    if(
        $username       == null ||
        $hashedPassword == null
    ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

    InstitutionValidator::validateInstitution($institutionName);

    $getInstitutionAddresses = "
        SELECT Country, Region, City, Street, Number, Building, Floor, Apartment, Is_Main_Address FROM addresses 
            JOIN institution_addresses_list ON addresses.ID = institution_addresses_list.Address_ID 
            WHERE Institution_ID = :ID
    ";

    $addresses = array();

    try{
        DatabaseManager::Connect();

        $institutionID = InstitutionValidator::getLastValidatedInstitution()->getID();
        $institutionName = InstitutionValidator::getLastValidatedInstitution()->getName();

        $SQLStatement = DatabaseManager::PrepareStatement($getInstitutionAddresses);
        $SQLStatement->bindParam(":ID", $institutionID);

        $SQLStatement->execute();

        while($row = $SQLStatement->fetch(PDO::FETCH_OBJ)){
            array_push($addresses, new InstitutionAddress(
                $row->Country,
                $row->Region,
                $row->City,
                $row->Street,
                $row->Number,
                $row->Building,
                $row->Floor,
                $row->Apartment,
                $row->Is_Main_Address
            ));
        }

        DatabaseManager::Disconnect();
    }
    catch(Exception $exception){
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

        echo json_encode($response), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    echo json_encode($response), PHP_EOL;
    echo json_encode($institutionName), PHP_EOL;
    echo json_encode($addresses), PHP_EOL;

    class InstitutionAddress{
        public $country;
        public $region;
        public $city;
        public $street;
        public $number;
        public $building;
        public $floor;
        public $apartment;
        public $isMainAddress;

        function __construct($country, $region, $city, $street, $number, $building, $floor, $apartment, $isMainAddress)
        {
            $this->country          = $country;
            $this->region           = $region;
            $this->city             = $city;
            $this->street           = $street;
            $this->number           = $number;
            $this->building         = $building;
            $this->floor            = $floor;
            $this->apartment        = $apartment;
            $this->isMainAddress    = $isMainAddress;
        }
    }

?>