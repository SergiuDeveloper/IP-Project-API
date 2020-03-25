<?php
require "HelperClasses/ValidationHelper.php";


if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo 'REQUEST_NOT_FOUND', PHP_EOL;
    http_response_code(400);
    die();
}

try{
    $username = $_GET['username'];
    $hashedPass = $_GET['hashedPassword'];
    
    if($username == null || $hashedPass == null){
        echo 'BAD_USERNAME_PASS', PHP_EOL;
        http_response_code(400);
        die();
    }
    
    switch(UserValidation::ValidateCredentials($username,$hashedPass)){
        case UserValidation::$DB_EXCEPT: 
            $response = [
                'status' => 'FAILED',
                'error' => 'DATABASE_FAILED'
            ];
        break;
        case UserValidation::$WRONG_PASSWORD: 
            $response = [
                'status' => 'FAILED',
                'error' => 'WRONG_PASSWORD'
            ];
        break;
        case UserValidation::$USER_NOT_FOUND: 
            $response = [
                'status' => 'FAILED',
                'error' => 'USER_NOT_FOUND'
            ];
        break;
        case UserValidation::$USER_INACTIVE: 
            $response = [
                'status' => 'FAILED',
                'error' => 'USER_INACTIVE'
            ];
        break;
        default:
            $response = [
                'status' => 'SUCCESS',
                'error' => ''
            ];
        break;
    }
    echo json_encode($response), PHP_EOL;
    http_response_code(200);
} catch(Exception $ex){
    echo $ex;
}

?>