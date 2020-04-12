"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var websitePath = "fiscaldocumentseditest.azurewebsites.net";
var pagePath = "/EndPoints/CreateNewsfeedPost/EndPoint.php";
var requestParameters = {
	username:"testuser3",
	hashedPassword:"OParolaObisnuita",
	nameOfPost:"Titlu3",
	contentOfPost:"Continut",
	linkOfPost:"www.google.com",
	tagsOfPost:['tag1','tag2']
};

function RequestSuccess(responseObject) {
	const testCondition = (responseObject.status == "FAILURE" && responseObject.error == "WRONG_PASSWORD");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Post(websitePath, pagePath, requestParameters, RequestSuccess, RequestFailure);