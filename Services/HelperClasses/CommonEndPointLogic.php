<?php

require_once("ValidationHelper.php");
require_once("StatusCodes.php");
require_once("DatabaseManager.php");

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
        Return:         void <=> If the credentials are incorrect, close the session
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
            return;

        echo json_encode($responseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    /* 
        Return:         void <=> If the credentials are incorrect or the user is not an administrator, close the session
        username:       string = The username
        hashedPassword: string = The hashed password
    */
    public static function ValidateAdministrator($username, $hashedPassword) {
        CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

        DatabaseManager::Connect();

        $getAdministratorStatement = DatabaseManager::PrepareStatement("SELECT * FROM Administrators WHERE Users_ID = (SELECT ID FROM Users WHERE Username = :username)");
        $getAdministratorStatement->bindParam(":username", $username);
        $getAdministratorStatement->execute();

        $administratorRow = $getAdministratorStatement->fetch();

        DatabaseManager::Disconnect();

        if ($administratorRow != null)
            return;

        $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("NOT_ADMIN");
        echo json_encode($responseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }

    /*
        Return:     void <=> Sends an email. On failure, stop execution and log
        receiver:   string = The receiver's email address
        subject:    string = The email's subject
        content:    string = The email's content
    */
    public static function SendEmail($receiver, $subject, $activationKey) {
        $url = "https://api.sendgrid.com/api/mail.send.json";
        $emailUser = "azure_0a4e0665ba1ddbf27cff9409f952abb8@azure.com";
        $emailPassword = "FiscalDocsEDI123";
        $content = CommonEndPointLogic::composeEmailBody($activationKey);

        $requestParameters = array(
            "api_user" => $emailUser,
            "api_key"  => $emailPassword,
            "to"       => $receiver,
            "subject"  => $subject,
            "html"     => $content,
            "text"     => $content,
            "from"     => "azure_0a4e0665ba1ddbf27cff9409f952abb8@azure.com",
            "fromname" => "Fiscal Documents EDI"
        );
       
        $curlSession = curl_init($url);
       
        curl_setopt($curlSession, CURLOPT_POST, true);
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, $requestParameters);
        curl_setopt($curlSession, CURLOPT_HEADER, false);
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, false);
       
        $azureEmailAPIResponse = curl_exec($curlSession);
        curl_close($curlSession);

        $azureEmailAPIResponse = json_decode($azureEmailAPIResponse);

        if ($azureEmailAPIResponse->message == "success")
            return;

        $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("CONFIRMATION_EMAIL_SEND_FAILURE");
        http_response_code(StatusCodes::OK);
        die();
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

    private static function composeEmailBody($activationKey){
        return "Hello!<br>Your activation link is below :<br> http://fiscaldocumentseditest.azurewebsites.net/EndPoints/AccountActivation/EndPoint.php?Unique_Key=$activationKey <br>";
    }
}

?>