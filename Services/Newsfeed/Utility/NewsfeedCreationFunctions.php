<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Newsfeed/Utility/NewsfeedTagLinker.php");
    require_once(ROOT . "/Utility/DatabaseManager.php");
    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    class NewsfeedCreation{
        
        public static function validateNewsfeedPostAndGetTagsID($title, $tagsArray){
            try {
                DatabaseManager::Connect();

                $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$getPostByNameFromDatabaseStatement);
                $SQLStatement->bindParam(":title", $title);
                $SQLStatement->execute();

                $newsfeedIDsRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

                if ($newsfeedIDsRow != null) {
                    ResponseHandler::getInstance()
                        ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("POST_DUPLICATE"))
                        ->send();
                    /*
                    $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("POST_DUPLICATE");

                    DatabaseManager::Disconnect();

                    echo json_encode($failureResponseStatus);
                    http_response_code(StatusCodes::OK);
                    die();
                    */
                }

                DatabaseManager::Disconnect();

                return NewsfeedTagLinker::createMissingTagsAndGetPostTagsID($tagsArray);
            }
            catch (Exception $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
                die();
            }
        }

        public static function CreatePostIntoDatabase($postTitle, $postURL, $postContent){
            try {
                DatabaseManager::Connect();

                $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$insertPostIntoDatabase);
                $SQLStatement->bindParam(":title", $postTitle);
                $SQLStatement->bindParam(":content", $postContent);
                $SQLStatement->bindParam(":url", $postURL);
                $SQLStatement->execute();

                DatabaseManager::Disconnect();
            }
            catch (Exception $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
                die();
            }
        }

        public static function AssociatePostWithTags($postTitle, $postTagsArray, $postTagsArrayID){
            try {
                DatabaseManager::Connect();

                $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$getPostByNameFromDatabaseStatement);
                $SQLStatement->bindParam(":title", $postTitle);
                $SQLStatement->execute();
                $postIDRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

                DatabaseManager::Disconnect();

                NewsfeedTagLinker::AssociatePostWithTags($postIDRow->ID, $postTagsArray, $postTagsArrayID);
            }
            catch (Exception $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
                die();
            }
        }

        private static $insertPostIntoDatabase = "
            INSERT INTO Newsfeed_Posts (Title, Content, URL, DateTime_Created) VALUES (:title, :content, :url, CURRENT_TIMESTAMP())
        ";

        private static $getPostByNameFromDatabaseStatement = "
            SELECT ID FROM Newsfeed_Posts WHERE Title = :title
        ";
    }


?>
