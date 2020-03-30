<?php

    require_once("../../HelperClasses/DatabaseManager.php");
    require_once("../../HelperClasses/StatusCodes.php");
    require_once("../../HelperClasses/CommonEndPointLogic.php");
    require_once("../../HelperClasses/ValidationHelper.php");

    CommonEndPointLogic::ValidateHTTPPOSTRequest();

    $username = $_POST['username'];
    $password = $_POST['hashedPassword'];
    $postTitle = $_POST['postName'];

    if($username == null
        || $password == null
        || $postTitle == null
    ){
        $response = CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT");

        http_response_code(StatusCodes::BAD_REQUEST);
        echo json_encode($response);
        die();
    }

    UserValidation::ValidateCredentials($username, $password);

    DatabaseManager::Connect();

    $SQLStatement = DatabaseManager::PrepareStatement("SELECT ID FROM Newsfeed_Posts WHERE Title = :title");

    $SQLStatement->bindParam(":title", $postTitle);
    $SQLStatement->execute();

    $postRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

    if($postRow == null){
        $response = CommonEndPointLogic::GetFailureResponseStatus("POST_NOT_IN_DATABASE");

        http_response_code(StatusCodes::OK);
        echo json_encode($response);
        die();
    }

    try{
        $SQLStatement = DatabaseManager::PrepareStatement("DELETE FROM Newsfeed_Posts_Tags_Assignations WHERE Newsfeed_Post_ID = :id");
        $SQLStatement->bindParam(":id", $postRow->ID);
        $SQLStatement->execute();

        $SQLStatement = DatabaseManager::PrepareStatement("DELETE FROM Newsfeed_Posts WHERE ID = :id");
        $SQLStatement->bindParam(":id", $postRow->ID);
        $SQLStatement->execute();
    }
    catch(Exception $except){
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

        http_response_code(StatusCodes::OK);
        echo json_encode($response);
        die(); 
    }

    DatabaseManager::Disconnect();

    $response = CommonEndPointLogic::GetSuccessResponseStatus();

    http_response_code(StatusCodes::OK);
    echo json_encode($response);    

?>