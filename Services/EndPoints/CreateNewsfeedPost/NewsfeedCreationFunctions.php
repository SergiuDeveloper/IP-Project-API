<?php

    require_once("../../HelperClasses/NewsfeedPostTagLinker.php");
    require_once("../../HelperClasses/DatabaseManager.php");

    class NewsfeedCreation{
        
        public static function validateNewsfeedPostAndGetTagsID($title, $tagsArray){
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$getPostByNameFromDatabaseStatement);
            $SQLStatement->bindParam(":title", $title);
            $SQLStatement->execute();

            $newsfeedIDsRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

            if($newsfeedIDsRow != null){
                $failureResponseStatus = CommonEndPointLogic::GetFailureResponseStatus("POST_DUPLICATE");

                DatabaseManager::Disconnect();

                echo json_encode($failureResponseStatus);
                http_response_code(StatusCodes::OK);
                die();
            }

            DatabaseManager::Disconnect();

            return NewsfeedTagLinker::createMissingTagsAndgetPostTagsID($tagsArray);
        }

        public static function CreatePostIntoDatabase($postTitle, $postURL, $postContent){

            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$insertPostIntoDatabase);
            $SQLStatement->bindParam(":title", $postTitle);
            $SQLStatement->bindParam(":content", $postContent);
            $SQLStatement->bindParam(":url", $postURL);
            $SQLStatement->execute();

            DatabaseManager::Disconnect();

        }

        public static function AssociatePostWithTags($postTitle, $postTagsArray, $postTagsArrayID){
            
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$getPostByNameFromDatabaseStatement);
            $SQLStatement->bindParam(":title", $postTitle);
            $SQLStatement->execute();
            $postIDRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

            DatabaseManager::Disconnect();

            NewsfeedTagLinker::AssociatePostWithTags($postIDRow->ID, $postTagsArray, $postTagsArrayID);

        }

        private static $insertPostIntoDatabase = "
            INSERT INTO Newsfeed_Posts (Title, Content, URL, DateTime_Created) VALUES (:title, :content, :url, CURRENT_TIMESTAMP())
        ";

        private static $getPostByNameFromDatabaseStatement = "
            SELECT ID FROM Newsfeed_Posts WHERE Title = :title
        ";
    }

?>