"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var websitePath = "fiscaldocumentseditest.azurewebsites.net";
var pagePath = "/EndPoints/Login/EndPoint.php";
var requestParameters = {
	username: "testuser3",
	hashedPassword: "OParolaNeobisnuita"
};

function RequestSuccess(responseObject) {
	const testCondition = (responseObject.status == "FAILURE" && responseObject.error == "WRONG_PASSWORD");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Post(websitePath, pagePath, requestParameters, RequestSuccess, RequestFailure);