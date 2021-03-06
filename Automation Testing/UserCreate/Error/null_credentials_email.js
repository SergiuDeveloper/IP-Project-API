"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var websitePath = "fiscaldocumentseditest.azurewebsites.net";
var pagePath = "/EndPoints/AccountCreation/EndPoint.php";
var requestParameters = {
	username: "ddasdadas",
	hashedPassword: "parola1234567890",
	firstName:"test",
	lastName: "pula"
};

function RequestSuccess(responseObject) {
	const testCondition = (responseObject.status == "FAILURE" && responseObject.error == "NULL_CREDETIAL");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Post(websitePath, pagePath, requestParameters, RequestSuccess, RequestFailure);