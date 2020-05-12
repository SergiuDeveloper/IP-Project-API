<?php

if(!defined('ROOT'))
    define('ROOT',  dirname(__FILE__) . '/../..');

require_once (ROOT . '/Document/Utility/Document.php');
require_once (ROOT . '/Utility/Utilities.php');

CommonEndPointLogic::ValidateHTTPGETRequest();

$currenciesArray = array();

try{
    DatabaseManager::Connect();

    $statement = DatabaseManager::PrepareStatement("SELECT ID, Title FROM currencies");
    $statement->execute();

    while($row = $statement->fetch(PDO::FETCH_OBJ)){
        array_push($currenciesArray, new CurrencyDAO($row->ID, $row->Title));
    }

    DatabaseManager::Disconnect();
} catch (PDOException $exception){
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
        ->send();
}

try{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->addResponseData("currencies",$currenciesArray)
        ->send();
} catch (Exception $exception){
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
}

class CurrencyDAO{
    public $ID;
    public $title;

    public function __construct($ID, $title){
        $this->title = $title;
        $this->ID = $ID;
    }
}

?>