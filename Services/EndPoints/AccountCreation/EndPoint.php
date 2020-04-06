<?php

require_once("../../HelperClasses/CommonEndPointLogic.php");
require_once("../../HelperClasses/DatabaseManager.php");
require_once("../../HelperClasses/StatusCodes.php");

CommonEndPointLogic::ValIDateHTTPPOSTRequest();

$username = $_POST["username"];
$hashedPassword = $_POST["hashedPassword"];
$email = $_POST["email"];
$firstName = $_POST["firstName"];
$lastName = $_POST["lastName"];

if ($username == null || $hashedPassword == null || $email == null ||  $firstName == null || $lastName == null) {
	$failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDETIAL");
	
	echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}

/**
 * caz particular : username cu caractere ilegle
 * adaugat la 6/4/2020
 * motiv : la adaugarea unui user in institutie, se verifica existenta unui '@' in input pentru a diferentia intre adaugare prin eMail sau prin username
 */

$usernameIllegalCharacters = "@\\/,'\"?><:;[]{}()=+`~!#\$%^&*\033 \t\n\r";

$illegalCharacters = str_split($usernameIllegalCharacters);

foreach($illegalCharacters as $character){
    if(strstr($username, $character)){
        $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("USERNAME_CONTAINS_ILLEGAL_CHAR");

        DatabaseManager::Disconnect();

        echo json_encode($failureResponseStatus), PHP_EOL;
        http_response_code(StatusCodes::OK);
        die();
    }
}

/*

    UPDATE : Instruction is pointless. 
    Verifici User-ul mai jos oricum daca exista sau nu

*/
//$responseStatus = CommonEndPointLogic::ValIDateUserCredentialsNoExit($username, $hashedPassword);  //Mic Patch, functia anterior se inchidea

DatabaseManager::Connect();     //Trebuie sa te conectezi inainte

$getUsernameStatement = DatabaseManager::PrepareStatement('SELECT * from Users where username = :username');
$getUsernameStatement->bindParam(":username", $username);
$getUsernameStatement->execute();
$userRow = $getUsernameStatement->fetch();

if($userRow != null) {
	$failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("USERNAME_ALREADY_EXISTS");
    
    DatabaseManager::Disconnect(); // Si sa te deconectezi

	echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

$getEmailStatement = DatabaseManager::PrepareStatement("SELECT * from Users where email = :email");
$getEmailStatement->bindParam(":email", $email);
$getEmailStatement->execute();
$userRow = $getEmailStatement->fetch();

if($userRow != null) {
	$failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("EMAIL_ALREADY_EXISTS");
	
    DatabaseManager::Disconnect(); // Si sa te deconectezi

	echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

$hashedPassword = password_hash($hashedPassword, PASSWORD_BCRYPT);

$insertUserStatement = DatabaseManager::PrepareStatement("INSERT INTO Users(username, hashed_password, email, first_name, last_name, is_active, datetime_created, datetime_modified) VALUES 
(:username, :hashedPassword, :email, :firstName, :lastName, false, sysdate(), sysdate())");

$insertUserStatement->bindParam(":username", $username);
$insertUserStatement->bindParam(":hashedPassword", $hashedPassword);
$insertUserStatement->bindParam(":email", $email);
$insertUserStatement->bindParam(":firstName", $firstName);
$insertUserStatement->bindParam(":lastName", $lastName);
$insertUserStatement->execute();

$getIDStatement = DatabaseManager::PrepareStatement("SELECT ID from Users where username = :username");
$getIDStatement->bindParam(":username", $username);
$getIDStatement->execute();
$userRow = $getIDStatement->fetch(PDO::FETCH_OBJ); // returneaza ca obiect rezultatul

if($userRow == null) {
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("INSERT_FAILURE");
    
    DatabaseManager::Disconnect(); // Si sa te deconectezi

	echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

$userID = $userRow->ID;
$userActivationKey = hash("md5", $userID, false); /// true = raw binary input, false = string

try{ 

    $insertKeyStatement = DatabaseManager::PrepareStatement("INSERT into user_activation_keys (user_id, unique_key, datetime_created, datetime_used) values
    (:id, :unique_key, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())");
    $insertKeyStatement->bindParam(":id", $userID);
    $insertKeyStatement->bindParam(":unique_key", $userActivationKey);
    $insertKeyStatement->execute();
}
catch(Exception $exception){
	$failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("KEY_INSERT_FAILURE");
	
    DatabaseManager::Disconnect(); // Si sa te deconectezi

	echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

CommonEndPointLogic::SendEmail($email, "Fiscal Documents EDI Activation Key", $userActivationKey);

$successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

DatabaseManager::Disconnect(); // Si sa te deconectezi

echo json_encode($successResponseStatus), PHP_EOL;
http_response_code(StatusCodes::OK);

?>