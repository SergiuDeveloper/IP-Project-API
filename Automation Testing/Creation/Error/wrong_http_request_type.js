"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var webpagePath = "http://fiscaldocumentseditest.azurewebsite.net/EndPoints/AccountCreation/EndPoint.php
";
var requestParameters = {
	username: "testuser3",
	hashedPassword: "OParolaObisnuita",
	email:"diana@gmail.com"
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