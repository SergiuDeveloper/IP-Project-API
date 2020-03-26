<?php

require_once("ValidationHelper.php");
require_once("StatusCodes.php");

/* Class containing common operations used in the API endpoints */
class CommonEndPointLogic {
    /* 
        Return: void <=> If the received request is not a HTTP GET, set a BAD REQUEST response status and end execution
    */
    public static function ValidateHTTPGETRequest() {
        CommonEndPointLogic::ValidateHTTPRequestType("GET");
    }

    /* 
        Return: void <=> If the received request is not a HTTP POST, set a BAD REQUEST response status and end execution
    */
    public static function ValidateHTTPPOSTRequest() {
        CommonEndPointLogic::ValidateHTTPRequestType("POST");
    }

    /* 
        Return:         void <=> If the received request is not a HTTP POST, set a BAD REQUEST response status and end execution
        requestType:    string = Request type
    */
    private static function ValidateHTTPRequestType($requestType) {
        if ($_SERVER["REQUEST_METHOD"] == $requestType)
            return;

        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("BAD_REQUEST_TYPE");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
    }

    /* 
        Return:         Array["status" => string, "error" => string] = Array containing the credentials validation status and the error, if needed; Ends the execution in case of failure
        username:       string = The username
        hashedPassword: string = The hashed password
    */
    public static function ValidateUserCredentials($username, $hashedPassword) {
        $userCredentialsValidationSuccessState = UserValidation::ValidateCredentials($username,$hashedPassword);

        $stopExecution = true;

        switch ($userCredentialsValidationSuccessState) {
            case SuccessStates::DB_EXCEPT: 
                $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPTION");
            break;
            case SuccessStates::USER_NOT_FOUND: 
                $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("USER_NOT_FOUND");
            break;
            case SuccessStates::WRONG_PASSWORD: 
                $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("WRONG_PASSWORD");
            break;
            case SuccessStates::USER_INACTIVE:
                $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("USER_INACTIVE");
            break;
            case SuccessStates::SUCCESS:
                $responseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

                $stopExecution = false;
            break;
        }

        if (!$stopExecution)
            return $responseStatus;

        echo json_encode($responseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();

        return $responseStatus;
    }

    /*
        Return: Array["status" => string, "error" => string default ""] = Requested success reponse status
    */   
    public static function GetSuccessResponseStatus() {
        return CommonEndPointLogic::GetResponseStatus("SUCCESS", "");
    }

    /*
        Return: Array["status" => string, "error" => string] = Requested failure reponse status
        error:  string = error
    */   
    public static function GetFailureResponseStatus($error) {
        return CommonEndPointLogic::GetResponseStatus("FAILURE", $error);
    }

    /*
        Return: Array["status" => string, "error" => string] = Requested reponse status
        status: string = status
        error:  string = error
    */    
    private static function GetResponseStatus($status, $error) {
        $responseStatus = [
            "status" => $status,
            "error" => $error
        ];
        return $responseStatus;
    }
}

?>