"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var websitePath = "fiscaldocumentseditest.azurewebsites.net";
var pagePath = "/EndPoints/AccountCreation/EndPoint.php";
var requestParameters = {
	username: "Logheqwin",
	hashedPassword: "parola",
	email:"vlad.loghin00@gmail.com",
	firstName:"dsas",
	lastName:"dasd"

};

function RequestSuccess(responseObject) {
	const testCondition = (responseObject.status == "FAILURE" && responseObject.error == "USER_INACTIVE");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Post(websitePath, pagePath, requestParameters, RequestSuccess, RequestFailure);