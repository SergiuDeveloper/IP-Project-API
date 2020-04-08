"use strict"
const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var websitePath = "fiscaldocumentseditest.azurewebsites.net";
var pagePath = "/EndPoints/CreateNewsfeedPost/EndPoint.php";
var requestParameters = {
	username:"Loghin",
	hashedPassword:"parola",
	nameOfPost:"motoeretardat4",
	contentOfPost:"Continutpulaetare",
	linkOfPost:"www.google.com",
	tagsOfPost: JSON.stringify(JSON.parse('{"0":"tag1","1":"tag2"}'))
};

function RequestSuccess(responseObject) {
	const testCondition = (responseObject.status == "SUCCESS" && responseObject.error == "");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Post(websitePath, pagePath, requestParameters, RequestSuccess, RequestFailure);