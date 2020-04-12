<?php

if(!defined('ROOT')){
    define('ROOT', dirname(__FILE__) . "/..");
}

require_once (ROOT . "/Utility/Exceptions/ResponseHandlerDuplicateLabel.php");
require_once (ROOT . "/Utility/Exceptions/ResponseHandlerNoHeader.php");

/**
 * Class ResponseHandler. Use this to handle all outgoing responses
 */
class ResponseHandler
{
    private static $instance;
    private $responseArray;
    private $hasHeader;

    /**
     * Singleton instance getter function
     * @return ResponseHandler current response
     */
    public static function getInstance(){
        if(self::$instance == null)
            self::$instance = new ResponseHandler();
        return self::$instance;
    }

    /**
     * ResponseHandler constructor. Private. Singleton. Clean.
     */
    private function __construct(){
        $this->responseArray = array();
        $this->responseArray["responseStatus"] = array();
        $this->responseArray["returnedObject"] = array();

        $this->hasHeader = false;
    }

    /**
     * Function used to add data to the response handler.
     *
     * @param $label    String                          Label for the data (JSON label)
     * @param $object   mixed                           Object to be added
     * @return          ResponseHandler                 Reference to same object for linked calls
     * @throws          ResponseHandlerDuplicateLabel   Thrown if an object is added with a label that already exists
     * @throws          ResponseHandlerNoHeader         Thrown if response does not have header (status + error)
     */
    public function addResponseData($label, $object){
        if(!$this->hasHeader)
            throw new ResponseHandlerNoHeader();

        if(array_key_exists($label, $this->responseArray["returnedObject"]))
            throw new ResponseHandlerDuplicateLabel();

        $this->responseArray["returnedObject"][$label] = $object;

        return $this;
    }

    /**
     * Function adds the response header
     *
     * @param $response array           Response header
     * @return          ResponseHandler Reference to current object for linked calls
     */
    public function setResponseHeader($response){
        $this->responseArray["responseStatus"]["status"] = $response["status"];
        $this->responseArray["responseStatus"]["error"]  = $response["error"];

        $this->hasHeader = true;

        return $this;
    }

    /**
     * Getter for the response array
     *
     * @return array    Response array. Not encoded
     * @throws ResponseHandlerNoHeader
     */
    public function getResponseArray(){
        if(!$this->hasHeader)
            throw new ResponseHandlerNoHeader();

        return $this->responseArray;
    }

    /**
     * Getter for encoded response
     *
     * @return string JSON encoded response
     * @throws ResponseHandlerNoHeader
     */
    public function getEncodedResponse(){
        if(!$this->hasHeader)
            throw new ResponseHandlerNoHeader();

        return json_encode($this->responseArray);
    }

    /**
     * Send handle for response. Sends the response with given status code and exists the process
     *
     * @param $statusCode   int     Status code sent in the response. Default is 200
     */
    public function send($statusCode = 200){
        try {
            echo $this->getEncodedResponse(), PHP_EOL;
        }
        catch (ResponseHandlerNoHeader $exception){
            $this->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("SERVICE_ERROR_INTERNAL_SERVER_ERROR"))
                ->send(StatusCodes::INTERNAL_SERVER_ERROR);
        }
        http_response_code($statusCode);
        die();
    }
}
