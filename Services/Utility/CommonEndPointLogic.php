<?php

if( ! defined('ROOT') ){
    define('ROOT', dirname(__FILE__) . '/..');
}

require_once (ROOT . '/Utility/DatabaseManager.php');
require_once (ROOT . '/Utility/StatusCodes.php');
require_once (ROOT . '/Utility/SuccessStates.php');
require_once (ROOT . '/Utility/UserValidation.php');
require_once(ROOT . "/Utility/ResponseHandler.php");

/**
 * Class containing common operations used in the API endpoints
 */
class CommonEndPointLogic {

    /**
     * @return void If the received request is not a HTTP GET, set a BAD REQUEST response status and end execution
     */
    public static function ValidateHTTPGETRequest() {
        CommonEndPointLogic::ValidateHTTPRequestType("GET");
    }

    /**
     * @return void If the received request is not a HTTP POST, set a BAD REQUEST response status and end execution
     */
    public static function ValidateHTTPPOSTRequest() {
        CommonEndPointLogic::ValidateHTTPRequestType("POST");
    }

    /**
     * @param $requestType  string  Request type
     * @return              void    If the received request is not a HTTP POST, set a BAD REQUEST response status and end execution
     */
    private static function ValidateHTTPRequestType($requestType) {
        if ($_SERVER["REQUEST_METHOD"] == $requestType)
            return;

        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("BAD_REQUEST_TYPE"))
            ->send(StatusCodes::BAD_REQUEST);

        /*
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("BAD_REQUEST_TYPE");

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::BAD_REQUEST);
        die();
        */
    }

    /**
     * @param $email            string  Email
     * @param $hashedPassword   string  Hashed Password
     * @return                  void    If the credentials are incorrect, close the session
     */
    public static function ValidateUserCredentials($email, $hashedPassword) {
        $userCredentialsValidationSuccessState = UserValidation::ValidateCredentials($email,$hashedPassword);

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
            default:
                $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("INVALID_ACTION");
        }

        if (!$stopExecution)
            return;

        ResponseHandler::getInstance()
            ->setResponseHeader($responseStatus)
            ->send();

        /*
        echo json_encode($responseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    /**
     * @param $email            string  Email
     * @param $hashedPassword   string  Hashed Password
     * @return                  void    If the credentials are incorrect or the user is not an administrator, close the session
     */
    public static function ValidateAdministrator($email, $hashedPassword) {
        CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

        DatabaseManager::Connect();

        $getAdministratorStatement = DatabaseManager::PrepareStatement(self::$getAllAdministratorInfoStatement);
        $getAdministratorStatement->bindParam(":email", $email);
        $getAdministratorStatement->execute();

        $administratorRow = $getAdministratorStatement->fetch();

        DatabaseManager::Disconnect();

        if ($administratorRow != null)
            return;

        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NOT_ADMIN"))
            ->send();
        /*
        $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("NOT_ADMIN");
        echo json_encode($responseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    /**
     * @param $receiver         string  The receiver's email address
     * @param $subject          string  The email's subject
     * @param $activationKey    string  The email's content
     * @return                  void    Sends an email. On failure, stop execution and log
     * @throws Exception
     */
    public static function SendEmail($receiver, $subject, $activationKey) {
        if (!CommonEndPointLogic::$sendGridCredentialsBound)
            CommonEndPointLogic::BindSendGridCredentials();

        $content = CommonEndPointLogic::composeEmailBody($activationKey);

        $requestParameters = array(
            "api_user" => CommonEndPointLogic::$sendGridURL,
            "api_key"  => CommonEndPointLogic::$sendGridPassword,
            "to"       => $receiver,
            "subject"  => $subject,
            "html"     => $content,
            "text"     => $content,
            "from"     => CommonEndPointLogic::$sendGridUsername,
            "fromname" => CommonEndPointLogic::$sendGridNickname
        );
       
        $curlSession = curl_init(CommonEndPointLogic::$sendGridURL);
       
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

        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("CONFIRMATION_EMAIL_SEND_FAILURE"))
            ->send();
        /*
        $responseStatus = CommonEndPointLogic::GetFailureResponseStatus("CONFIRMATION_EMAIL_SEND_FAILURE");
        echo json_encode($responseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    /**
     * @return array ['status' => string, 'error' => string] Requested success response status
     */
    public static function GetSuccessResponseStatus() {
        return CommonEndPointLogic::GetResponseStatus("SUCCESS", "");
    }

    /**
     * @param $error    string  The error's message
     * @return          array   ["status" => string, "error" => string] = Requested failure response status
     */
    public static function GetFailureResponseStatus($error) {
        return CommonEndPointLogic::GetResponseStatus("FAILURE", $error);
    }

    /**
     * @param $status   string  The status string
     * @param $error    string  The error's message
     * @return          array   ["status" => string, "error" => string] = Requested response status
     */
    private static function GetResponseStatus($status, $error) {
        return $responseStatus = [
            "status" => $status,
            "error" => $error
        ];
    }

    /**
     * @throws Exception
     */
    private static function BindSendGridCredentials() {
        $jsonFileContent = file_get_contents(CommonEndPointLogic::$sendGridJSONFilePath);

        if ($jsonFileContent === false)
            throw new Exception("Could not get SendGrid Credentials JSON file content!");

        $dbCredentialsJSON = json_decode($jsonFileContent, true);
        if ($dbCredentialsJSON === null)
            throw new Exception("SendGrid Credentials JSON syntax error!");

        $dbCredentialsJSON = $dbCredentialsJSON["EmailCredentials"];
        if ($dbCredentialsJSON === null)
            throw new Exception("Bad SendGrid Credentials JSON object format");
        CommonEndPointLogic::$sendGridURL =         $dbCredentialsJSON["URL"];
        CommonEndPointLogic::$sendGridUsername =    $dbCredentialsJSON["Username"];
        CommonEndPointLogic::$sendGridPassword =    $dbCredentialsJSON["Password"];
        CommonEndPointLogic::$sendGridNickname =    $dbCredentialsJSON["Nickname"];
        if (CommonEndPointLogic::$sendGridURL === null || CommonEndPointLogic::$sendGridUsername === null || CommonEndPointLogic::$sendGridPassword === null || CommonEndPointLogic::$sendGridNickname === null)
            throw new Exception("Bad SendGrid Credentials JSON object format");

        CommonEndPointLogic::$sendGridCredentialsBound = true;
    }

    /**
     * @param $activationKey    string  The key to be inserted into the email link
     * @return                  string  The full email content string, with markdown encoding
     */
    private static function composeEmailBody($activationKey){
        return "
            Hello!<br>
            Your activation link is below :<br> 
            <a href='http://fiscaldocumentseditest.azurewebsites.net/Account/Activation.php?uniqueKey=$activationKey'>
                http://fiscaldocumentseditest.azurewebsites.net/Account/Activation.php?uniqueKey=$activationKey 
            </a> <br>";
    }

    private static $getAllAdministratorInfoStatement = "
        SELECT * FROM Administrators WHERE Users_ID = (SELECT ID FROM Users WHERE Email = :email)
    ";

    private static $sendGridURL;
    private static $sendGridUsername;
    private static $sendGridPassword;
    private static $sendGridNickname;
    private static $sendGridJSONFilePath = "./../Sensitive/SendGrid.json";
    private static $sendGridCredentialsBound = false;
}