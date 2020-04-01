<?php

/*
Input: {
    username: string,
    hashedPassword: string,
    newsfeedPostID: int,
    newsfeedPostTitle: string,
    newsfeedPostContent: string,
    newsfeedPostURL: string,
    newsfeedPostTags: Array<string>
}

Output: {
    responseStatus: {
        status: string,
        error: string
    }
}

Response Status Error Codes:
    NULL_CREDENTIAL,
    NULL_NEWSFEED_POST_ID,
    BAD_REQUEST_TYPE,
    DB_EXCEPTION,
    USER_NOT_FOUND,
    WRONG_PASSWORD,
    USER_INACTIVE,
    NOT_ADMIN,
    POST_NOT_FOUND
*/

require_once("../../HelperClasses/CommonEndPointLogic.php");
require_once("../../HelperClasses/DatabaseManager.php");
require_once("../../HelperClasses/SuccessStates.php");
require_once("../../HelperClasses/StatusCodes.php");

CommonEndPointLogic::ValidateHTTPPOSTRequest();

$username               = $_POST["username"];
$hashedPassword         = $_POST["hashedPassword"];
$newsfeedPostID         = $_POST["newsfeedPostID"];
$newsfeedPostTitle      = $_POST["newsfeedPostTitle"];
$newsfeedPostContent    = $_POST["newsfeedPostContent"];
$newsfeedPostURL        = $_POST["newsfeedPostURL"];
$newsfeedPostTags       = json_decode($_POST["newsfeedPostTags"]);

if ($username == null || $hashedPassword == null) {
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL");
	
	echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}

if ($newsfeedPostID == null) {
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("NULL_NEWSFEED_POST_ID");
	
	echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::BAD_REQUEST);
    die();
}

CommonEndPointLogic::ValidateAdministrator($username, $hashedPassword);

DatabaseManager::Connect();

$getPostStatement = DatabaseManager::PrepareStatement("SELECT * FROM Newsfeed_Posts WHERE ID = :newsfeedPostID");
$getPostStatement->bindParam(":newsfeedPostID", $newsfeedPostID);
$getPostStatement->execute();

$newsfeedPostRow = $getPostStatement->fetch();
if ($newsfeedPostRow == null) {
    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("POST_NOT_FOUND");
    
    DatabaseManager::Disconnect();

	echo json_encode($failureResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

if ($newsfeedPostTitle != null || $newsfeedPostContent != null | $newsfeedPostURL != null) {
    $firstModification = true;

    $modifyPostEntryQuery = "UPDATE Newsfeed_Posts SET";
    if ($newsfeedPostTitle != null) {
        $modifyPostEntryQuery = sprintf("%s Title = :newsfeedPostTitle", $modifyPostEntryQuery, $newsfeedPostTitle);
        $firstModification = false;
    }
    if ($newsfeedPostContent != null) {
        $modifyPostEntryQuery = sprintf("%s%s Content = :newsfeedPostContent", $modifyPostEntryQuery, $firstModification ? "" : ",", $newsfeedPostContent);
        $firstModification = false;
    }
    if ($newsfeedPostURL != null)
        $modifyPostEntryQuery = sprintf("%s%s URL = :newsfeedPostURL", $modifyPostEntryQuery, $firstModification ? "" : ",", $newsfeedPostURL);
    $modifyPostEntryQuery = sprintf("%s WHERE ID = :newsfeedPostID", $modifyPostEntryQuery, $newsfeedPostID);

    $modifyPostStatement = DatabaseManager::PrepareStatement($modifyPostEntryQuery);
    if ($newsfeedPostTitle != null)
        $modifyPostStatement->bindParam(":newsfeedPostTitle", $newsfeedPostTitle);
    if ($newsfeedPostContent != null)
        $modifyPostStatement->bindParam(":newsfeedPostContent", $newsfeedPostContent);
    if ($newsfeedPostURL != null)
        $modifyPostStatement->bindParam(":newsfeedPostURL", $newsfeedPostURL);
    $modifyPostStatement->bindParam(":newsfeedPostID", $newsfeedPostID);
    $modifyPostStatement->execute();
}

if ($newsfeedPostTags == null) {
    $successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();
    
    DatabaseManager::Disconnect();

	echo json_encode($successResponseStatus), PHP_EOL;
    http_response_code(StatusCodes::OK);
    die();
}

foreach ($newsfeedPostTags as $newsfeedPostTag) {
    $getNewsfeedPostTagStatement = DatabaseManager::PrepareStatement("SELECT * FROM Newsfeed_Tags WHERE Title = :newsfeedPostTag");
    $getNewsfeedPostTagStatement->bindParam(":newsfeedPostTag", $newsfeedPostTag);
    $getNewsfeedPostTagStatement->execute();
    $newsfeedPostTagRow = $getNewsfeedPostTagStatement->fetch(PDO::FETCH_OBJ);

    if ($newsfeedPostTagRow == null) {
        $createNewsfeedPostTagStatement = DatabaseManager::PrepareStatement("INSERT INTO Newsfeed_Tags(Title) VALUES(:newsfeedPostTag)");
        $createNewsfeedPostTagStatement->bindParam(":newsfeedPostTag", $newsfeedPostTag);
        $createNewsfeedPostTagStatement->execute();

        $getNewsfeedPostTagStatement = DatabaseManager::PrepareStatement("SELECT * FROM Newsfeed_Tags WHERE Title = :newsfeedPostTag");
        $getNewsfeedPostTagStatement->bindParam(":newsfeedPostTag", $newsfeedPostTag);
        $getNewsfeedPostTagStatement->execute();
        $newsfeedPostTagRow = $getNewsfeedPostTagStatement->fetch(PDO::FETCH_OBJ);
    }
    
    $newsfeedTagID = $newsfeedPostTagRow->ID;

    $getNewsfeedPostTagsAssociationStatement = DatabaseManager::PrepareStatement("SELECT * FROM Newsfeed_Posts_Tags_Assignations WHERE Newsfeed_Post_ID = :newsfeedPostID AND Newsfeed_Tag_ID = :newsfeedTagID");
    $getNewsfeedPostTagsAssociationStatement->bindParam(":newsfeedPostID", $newsfeedPostID);
    $getNewsfeedPostTagsAssociationStatement->bindParam(":newsfeedTagID", $newsfeedTagID);
    $getNewsfeedPostTagsAssociationStatement->execute();
    $newsfeedPostTagsAssociationRow = $getNewsfeedPostTagsAssociationStatement->fetch();

    if ($newsfeedPostTagsAssociationRow == null) {
        $associatePostWithTagStatement = DatabaseManager::PrepareStatement("INSERT INTO Newsfeed_Posts_Tags_Assignations(Newsfeed_Post_ID, Newsfeed_Tag_ID) VALUES(:newsfeedPostID, :newsfeedTagID)");
        $associatePostWithTagStatement->bindParam(":newsfeedPostID", $newsfeedPostID);
        $associatePostWithTagStatement->bindParam(":newsfeedTagID", $newsfeedTagID);
        $associatePostWithTagStatement->execute();
    }
}

DatabaseManager::Disconnect();

$successResponseStatus = CommonEndPointLogic::GetSuccessResponseStatus();

echo json_encode($successResponseStatus), PHP_EOL;
http_response_code(StatusCodes::OK);

?>