<?php

    if(!defined('ROOT'))
        define('ROOT',  dirname(__FILE__) . '/../..');

    require_once (ROOT . '/Document/Utility/Document.php');
    require_once (ROOT . '/Utility/Utilities.php');

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $paymentMethodsArray = array();

    try{
        DatabaseManager::Connect();

        $statement = DatabaseManager::PrepareStatement("SELECT ID, Title FROM payment_methods");
        $statement->execute();

        while($row = $statement->fetch(PDO::FETCH_OBJ)){
            array_push($paymentMethodsArray, new PaymentMethodDAO($row->ID, $row->Title));
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
            ->addResponseData("paymentMethods",$paymentMethodsArray)
            ->send();
    } catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

    class PaymentMethodDAO{
        public $ID;
        public $title;

        public function __construct($ID, $title){
            $this->title = $title;
            $this->ID = $ID;
        }
    }

?>