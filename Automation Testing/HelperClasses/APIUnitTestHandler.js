"use strict"

module.exports =
class APIUnitTestHandler {
    static Test(testCondition, successMessage, failureMessage) {
        if (testCondition)
            APIUnitTestHandler.Success(successMessage);
        else
            APIUnitTestHandler.Failure(failureMessage);
    }

    static Success(successMessage) {
        console.log(`Test Success: ${successMessage}`);
    }

    static Failure(failureMessage) {
        console.error(`Test Failure: ${failureMessage}`);
    }

    static HTTPRequestsHandler = class {
        static STATUS_CODE_SUCCESS = 200;

        static Get(webpagePath, requestParameters, successCallback, errorCallback) {
            try {
                const http = require("http");

                var webpagePathWithParameters = webpagePath;
                const requestParametersEntries = Object.entries(requestParameters);
                if (requestParametersEntries != null)
                    webpagePathWithParameters += "?";

                requestParametersEntries.forEach(entry => {
                    const requestParameterKey = entry[0];
                    const requestParameterValue = entry[1];
                        
                    webpagePathWithParameters += `${requestParameterKey}=${requestParameterValue}&`;
                });

                webpagePathWithParameters = webpagePathWithParameters.slice(0, -1);

                http.get(webpagePathWithParameters, (response) => {
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

        static Post(webpagePath, requestParameters, successCallback, errorCallback) {
            try {
                const http = require("http");

                var webpagePathWithParameters = webpagePath;
                const requestParametersEntries = Object.entries(requestParameters);
                if (requestParametersEntries != null)
                    webpagePathWithParameters += "?";

                requestParametersEntries.forEach(entry => {
                    const requestParameterKey = entry[0];
                    const requestParameterValue = entry[1];
                        
                    webpagePathWithParameters += `${requestParameterKey}=${requestParameterValue}&`;
                });

                webpagePathWithParameters = webpagePathWithParameters.slice(0, -1);

                http.get(webpagePathWithParameters, (response) => {
                    var responseData = "";

                    response.on("data", (responseDataChunk) => {
                        responseData += responseDataChunk;
                    });
                    
                    response.on("end", () => {
                        if (response.statusCode == APIUnitTestHandler.HTTPRequestsHandler.STATUS_CODE_SUCCESS)
                            successCallback(`Status code: ${response.statusCode}`);
                        else
                            errorCallback(`Status code: ${response.statusCode}`);
                    });
                }).on("error", (thrownError) => {
                    errorCallback(thrownError);
                });
            }
            catch (thrownException) {
                errorCallback(thrownException);
            }
        }
    }
}