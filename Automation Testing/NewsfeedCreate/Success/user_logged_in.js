"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var websitePath = "fiscaldocumentseditest.azurewebsites.net";
var pagePath = "/EndPoints/CreateNewsfeedPost/EndPoint.php";
var requestParameters = {
	username:"Loghin",
	hashedPassword:"parola",
	nameOfPost:"motoeretardat6",
	contentOfPost:"Continutpulaetare",
	linkOfPost:"www.google.com",
	tagsOfPost: "[\"tag1\",\"tag4\"]"
};

function RequestSuccess(responseObject) {
	const testCondition = (responseObject.status == "SUCCESS" && responseObject.error == "");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Post(websitePath, pagePath, requestParameters, RequestSuccess, RequestFailure);