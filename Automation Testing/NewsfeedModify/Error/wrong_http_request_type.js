"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var webpagePath = "http://fiscaldocumentseditest.azurewebsites.net/EndPoints/NewsfeedPostNotification/EndPoint.php";
var requestParameters = {
	username : "Loghin",
	hashedPassword : "parola",
	newsfeedPostID : "2",
	newsfeedPostTitle : "TITLE",
	newsfeedPostContent :"CONTENT",
	newsfeedPostURL:"URL",
	newsfeedPostTags : ['tag1','tag2'] 
};

function RequestSuccess(responseObject) {
	responseObject = JSON.parse(responseObject);
	
	const testCondition = (responseObject.status == "FAILURE" && responseObject.error == "BAD_REQUEST_TYPE");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Get(webpagePath, requestParameters, RequestSuccess, RequestFailure);