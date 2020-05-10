<?php

class DebugHandler{

    /**
     * @var DebugHandler $instance
     */
    private static $instance;

    private $source;
    private $errorName;
    private $lineNumber;
    private $debugBody;

    public function debugEcho(){
        $message = 'Debug Message in \'' . $this->source . '\' at line ' . $this->lineNumber . ', with message \'' .
            $this->errorName . '\'' . ((count($this->debugBody) == 0) ? '' : ', with watched vars : ');

        $message = $message . ' ' . json_encode($this->debugBody);

        echo $message;

        self::$instance = new DebugHandler();
    }

    /**
     * @return DebugHandler
     */
    public static function getInstance(){
        if(self::$instance == null)
            self::$instance = new DebugHandler();
        return self::$instance;
    }

    /**
     * DebugHandler constructor.
     */
    private function __construct(){
        $this->source = 'Unknown Source';
        $this->errorName = 'Unhandled Error';
        $this->lineNumber = '??';
        $this->debugBody = array();
    }

    /**
     * @param $var
     * @param string|int $label
     * @return DebugHandler
     */
    public function addDebugVars($var, $label = null){
        if($label == null){
            $label = count($this->debugBody);
        }

        $this->debugBody[$label] = $var;

        return $this;
    }

    /**
     * @param int $lineNumber
     * @return DebugHandler
     */
    public function setLineNumber($lineNumber){
        $this->lineNumber = $lineNumber;
        return $this;
    }

    /**
     * @param string $debugMessage
     * @return DebugHandler
     */
    public function setDebugMessage($debugMessage){
        $this->errorName = $debugMessage;
        return $this;
    }

    /**
     * @param string $sourceName
     * @return DebugHandler
     */
    public function setSource($sourceName){
        $this->source = $sourceName;
        return $this;
    }

}

?>