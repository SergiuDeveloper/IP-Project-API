<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . '/..');
}

require_once(ROOT . "/Utility/CommonEndPointLogic.php");
require_once(ROOT . "/Utility/UserValidation.php");
require_once(ROOT . "/Utility/StatusCodes.php");
require_once(ROOT . "/Utility/SuccessStates.php");
require_once(ROOT . "/Utility/ResponseHandler.php");

require_once(ROOT . "/Institution/Role/Utility/InstitutionRoles.php");
require_once(ROOT . "/Institution/Utility/InstitutionCreation.php");
require_once(ROOT . "/Institution/Utility/InstitutionValidator.php");


CommonEndPointLogic::ValidateHTTPGetRequest();

$email              = $_GET["email"];
$hashedPassword     = $_GET["hashedPassword"];
$institutionName    = $_GET["institutionName"];

if(
    $email       == null ||
    $hashedPassword == null
){
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
        ->send(StatusCodes::BAD_REQUEST);
}

CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

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
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
        ->send();
}

try{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->addResponseData("institutionID", $institutionID)
        ->addResponseData("institutionName", $institutionName)
        ->addResponseData("addresses", $addresses)
        ->send();
}
catch (Exception $exception){
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(500);
}

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
