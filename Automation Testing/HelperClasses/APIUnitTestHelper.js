"use strict"

/*
    Unit test helper class for the API
*/
module.exports =
class APIUnitTestHelper {
    /*
        Return:         void <=> Checks a condition; If true, it logs the success message; otherwise, it logs the failure message as an error
        testCondition:  boolean = Condition to check
        successMessage: string = Success message
        failureMessage: string = Failure message
    */
    static Test(testCondition, successMessage, failureMessage) {
        if (testCondition) {
			if (successMessage === null)
				APIUnitTestHelper.Success(null);
			else
				APIUnitTestHelper.Success(successMessage);
		}
        else {
			if (failureMessage === null)
				APIUnitTestHelper.Failure(null);
			else
				APIUnitTestHelper.Failure(failureMessage);
		}
	}

     /*
        Return:         void <=> Logs a success message
        successMessage: string = Success message to log
    */
    static Success(successMessage) {
		if (successMessage === null) {
			console.log("Test Success");
			return;
		}
		
		if (typeof successMessage === 'object')
			successMessage = JSON.stringify(successMessage);
        console.log(`Test Success: ${successMessage}`);
    }

     /*
        Return:         void <=> Logs a failure message as error
        failureMessage: string = Failure message to log as error
    */
    static Failure(failureMessage) {
		if (failureMessage === null) {
				console.log("Test Failure");
				return;
		}
		
		if (typeof failureMessage === 'object')
			failureMessage = JSON.stringify(failureMessage);
        console.error(`Test Failure: ${failureMessage}`);
    }

    /*
        HTTP requests helper class for the API
    */
    static HTTPRequestsHelper = class {
        static STATUS_CODE_SUCCESS = 200;

        /*
            Return:             void <=> Performs a HTTP GET request on a URL, calling a success callback if the operation was successfull; otherwise, it calls the error callback
            webpagePath:        string = Page URL
            requestParameters:  Object = Request parameters
            successCallback:    function(string) = Success callback, having a string parameter, representing a success message
            errorCallback:      function(string) = Failure callback, having a string parameter, representing a failure message
        */
        static Get(webpagePath, requestParameters, successCallback, errorCallback) {
            try {
                const http = require("http");

				webpagePath = webpagePath.replace("https://", "http://");
				if (!webpagePath.startsWith("http://"))
					webpagePath = "http://" + webpagePath;

				var webpagePathWithParameters = webpagePath;
				
				if (requestParameters !== null) {
					const requestParametersEntries = Object.entries(requestParameters);
					if (requestParametersEntries != null)
						webpagePathWithParameters += "?";

					requestParametersEntries.forEach(entry => {
						const requestParameterKey = entry[0];
						const requestParameterValue = entry[1];
							
						webpagePathWithParameters += `${requestParameterKey}=${requestParameterValue}&`;
					});

					webpagePathWithParameters = webpagePathWithParameters.slice(0, -1);
				}

                http.get(webpagePathWithParameters, (response) => {
                    var responseData = "";

                    response.on("data", (responseDataChunk) => {
                        responseData += responseDataChunk;
                    });
                    
                    response.on("end", () => {
                        try {
                            const returnedObject = JSON.parse(responseData);
                            successCallback(responseData);
                        }
                        catch (thrownException) {
                            errorCallback(thrownException);	
                        }
                    });
                }).on("error", (thrownError) => {
                    errorCallback(thrownError);
                });
            }
            catch (thrownException) {
                errorCallback(thrownException);
            }
        }

        /*
            Return:             void <=> Performs a HTTP POST request on a URL, calling a success callback if the operation was successfull; otherwise, it calls the error callback
            websiteURL:         string = Website root URL
            pagePath:           string = Path to requested page
            requestParameters:  Object = Request parameters
            successCallback:    function(string) = Success callback, having a string parameter, representing a success message
            errorCallback:      function(string) = Failure callback, having a string parameter, representing a failure message
        */
        static Post(websiteURL, pagePath, requestParameters, successCallback, errorCallback) {
            try {
                const http = require("http");
                const querystring = require("querystring");
				
				websiteURL = websiteURL.replace("https://", "");
				websiteURL = websiteURL.replace("http://", "");
				
				var requestParametersByteLength = 0;
				if (requestParameters !== null) {
					var requestParametersJSONEncoded = querystring.stringify(requestParameters);
					requestParametersByteLength = Buffer.byteLength(requestParametersJSONEncoded);
				}
				
				console.log(requestParametersJSONEncoded);

                var requestOptions = {
                    hostname: websiteURL,
                    port: 80,
                    path: pagePath,
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                        "Content-Length": requestParametersByteLength
                    }
                };

                var httpRequest = http.request(requestOptions, (response) => {
                    var responseData = "";

                    response.on("data", (responseDataChunk) => {
                        responseData += responseDataChunk;
                    });
                    
                    response.on("end", () => {
                        try {
                            const returnedObject = JSON.parse(responseData);
                            successCallback(returnedObject);
                        }
                        catch (thrownException) {
							console.log(thrownException);
                            errorCallback(thrownException);    
                        }
                    });
                }).on("error", (thrownError) => {
                    errorCallback(thrownError);
                });
                  
				if (requestParameters !== null) 
					httpRequest.write(requestParametersJSONEncoded);
				
                httpRequest.end();
            }
            catch (thrownException) {
                errorCallback(thrownException);
            }   
        }
    }
}
