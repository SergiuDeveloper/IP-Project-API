"use strict"

const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");

var websitePath = "fiscaldocumentseditest.azurewebsites.net";
var pagePath = "/EndPoints/DeleteNewsfeedPost/EndPoint.php";
var requestParameters = {
	Username:"Loghin",
	Hashed_Password:"parola",
	postTitle:"Titlu"
};

function RequestSuccess(responseObject) {
	const testCondition = (responseObject.status == "SUCCESS" && responseObject.error == "");
	APIUnitTestHelper.Test(testCondition, null, responseObject);
}

function RequestFailure(responseObject) {
	APIUnitTestHelper.Failure(responseObject);
}

APIUnitTestHelper.HTTPRequestsHelper.Post(websitePath, pagePath, requestParameters, RequestSuccess, RequestFailure);
 