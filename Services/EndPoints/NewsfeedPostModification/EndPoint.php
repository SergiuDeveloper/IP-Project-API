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
    BAD_REQUEST_TYPE,
    DB_EXCEPTION,
    USER_NOT_FOUND,
    WRONG_PASSWORD,
    USER_INACTIVE
*/

require_once("../../HelperClasses/CommonEndPointLogic.php");
require_once("../../HelperClasses/ValidationHelper.php");
require_once("../../HelperClasses/SuccessStates.php");
require_once("../../HelperClasses/StatusCodes.php");

CommonEndPointLogic::ValidateHTTPPOSTRequest();

$username               = $_POST["username"];
$hashedPassword         = $_POST["hashedPassword"];
$newsfeedPostID         = $_POST["newsfeedPostID"];
$newsfeedPostTitle      = $_POST["newsfeedPostTitle"];
$newsfeedPostContent    = $_POST["newsfeedPostContent"];
$newsfeedPostURL        = $_POST["newsfeedPostURL"];
$newsfeedPostTags       = $_POST["newsfeedPostTags"];

$responseStatus = CommonEndPointLogic::ValidateUserCredentials($username, $hashedPassword);

//to-do

http_response_code(StatusCodes::OK);

?>
