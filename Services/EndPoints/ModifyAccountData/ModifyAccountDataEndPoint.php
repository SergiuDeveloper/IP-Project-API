<?php

    /**
     * TODO : Standardize Errors
     * TODO : Document
     * TODO : Apply Code Standardization where possible
     */
    
    require_once ('ModifyAccountDataFunctions.php');

    if($_SERVER["REQUEST_METHOD"] != "GET"){
        echo "REQUEST_NOT_FOUND", PHP_EOL;
        http_response_code(400);
        die();
    }

    $inputUsername              = $_GET['username'];
    $inputCurrentHashedPassword = $_GET['currentHashedPassword'];
    $inputNewHashedPassword     = $_GET['newHashedPassword'];
    $inputNewFirstName          = $_GET['newFirstName'];
    $inputNewLastName           = $_GET['newLastName'];

    if( $inputUsername == null || $inputCurrentHashedPassword == null ){
        echo "BAD_USERNAME_PASSWORD", PHP_EOL;
        http_response_code(400);
        die();
    }

    $userRow = ModifyAccountManager::fetchIDandHashedPassword($inputUsername);

    $response = ModifyAccountManager::prepareResponse($userRow, $inputCurrentHashedPassword);

    if($response['status'] == 'SUCCESS')
        ModifyAccountManager::updateFieldsInDatabase(
            $userRow['ID'],
            $inputNewHashedPassword,
            $inputNewFirstName,
            $inputNewLastName
        );

    echo json_encode($response), PHP_EOL;
    http_response_code(200);
?>

