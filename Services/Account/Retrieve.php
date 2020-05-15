<?php

if(!defined('ROOT'))
{
    define('ROOT', dirname(__FILE__) . '/..');
}

require_once(ROOT . "/Account/Utility/ModifyAccountManager.php");

require_once(ROOT . "/Utility/Utilities.php");

CommonEndPointLogic::ValidateHTTPGETRequest();

$email = $_GET["email"];
$hashedPassword = $_GET["hashedPassword"];
$apiKey = $_GET["apiKey"];

if($apiKey != null){
    try {
        $credentials = APIKeyHandler::getInstance()->setAPIKey($apiKey)->getCredentials();
    } catch (APIKeyHandlerKeyUnbound $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNBOUND_KEY"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    } catch (APIKeyHandlerAPIKeyInvalid $e) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_KEY"))
            ->send();
    }

    $email = $credentials->getEmail();
    //$hashedPassword = $credentials->getHashedPassword();
}
else {
    if (($email == null || $hashedPassword == null)) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
}

$userQuery = "SELECT * FROM users WHERE Email = :email";

try
{
    DatabaseManager::Connect();

    $userQueryStatement = DatabaseManager::PrepareStatement($userQuery);
    $userQueryStatement->bindParam(":email", $email);
    $userQueryStatement->execute();

    $userRow = $userQueryStatement->fetch(PDO::FETCH_ASSOC);

    if ($userRow == null)
    {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("USER_NOT_FOUND"))
            ->send();
    }

    $id = $userRow["ID"];
    $firstName = $userRow["First_Name"];
    $lastName = $userRow["Last_Name"];
    $isActive = $userRow["Is_Active"];
    $dateCreated = $userRow["DateTime_Created"];
    $dateModified = $userRow["DateTime_Modified"];

    DatabaseManager::Disconnect();
}
catch (Exception $databaseException)
{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
        ->send();
}

try {
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
        ->addResponseData("ID", $id)
        //->addResponseData("Hashed_Password", $hashedPassword)
        ->addResponseData("Email", $email)
        ->addResponseData("First_Name", $firstName)
        ->addResponseData("Last_Name", $lastName)
        ->addResponseData("Is_Active", $isActive)
        ->addResponseData("DateTime_Created", $dateCreated)
        ->addResponseData("DateTime_Modified", $dateModified)
        ->send();
}

catch(Exception $e)
{
    ResponseHandler::getInstance()
        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
        ->send(StatusCodes::INTERNAL_SERVER_ERROR);
}

?>