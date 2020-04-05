"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var websitePath = "fiscaldocumentseditest.azurewebsites.net";
var pagePath = "/EndPoints/CreateNewsfeedPost/EndPoint.php";
var requestParameters = {
	username: "user_inexistent",
	hashedPassword: "OParolaObisnuita"
};

function RequestSuccess(responseObject) {
	const testCondition = (responseObject.status == "FAILURE" && responseObject.error == "USER_NOT_FOUND");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Post(websitePath, pagePath, requestParameters, RequestSuccess, RequestFailure);