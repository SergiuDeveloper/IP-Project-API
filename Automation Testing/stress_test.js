"use strict"
const APIUnitTestHelper = require("./../../HelperClasses/APIUnitTestHelper.js");
for (var i = 0; i < 10000; ++i) {
	// GET request test
	var websitePath = "fiscaldocumentseditest.azurewebsites.net";
	var pagePath = "/EndPoints/Login/EndPoint.php";
	var requestParameters = {
		username: "testuser",
		hashedPassword: "plmcoaie"
	};

	function GetRequestSuccessCallback(responseObject) {
		const testCondition = (responseObject.status == 'SUCCESS');
		APIUnitTestHelper.Test(testCondition, "Success", responseObject);
	}

	function GetRequestErrorCallback(thrownError) {
		APIUnitTestHelper.Failure(thrownError);
	}

	APIUnitTestHelper.HTTPRequestsHelper.Get(webpagePath, requestParameters, GetRequestSuccessCallback, GetRequestErrorCallback);

	// POST request test
	var websitePath = "fiscaldocumentseditest.azurewebsites.net";
	var pagePath = "/EndPoints/Login/EndPoint.php";
	var requestParameters2 = {
	   username: "testuser3",
	   hashedPassword : "OParolaObisnuita"
	};

	function PostRequestSuccessCallback(statusCodeMessage) {
		APIUnitTestHelper.Success(statusCodeMessage);
	}

	function PostRequestErrorCallback(thrownError) {
		APIUnitTestHelper.Failure(thrownError);
	}

	APIUnitTestHelper.HTTPRequestsHelper.Post(webpagePath2, pagePath, requestParameters2, PostRequestSuccessCallback, PostRequestErrorCallback);
}