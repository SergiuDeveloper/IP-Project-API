<?php

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

                echo json_decode($failureResponseStatus);
                http_response_code(StatusCodes::OK);
                die();
            }

            foreach ($tagsArray as $tag){
                $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$getTagFromDatabaseStatement);
                $SQLStatement->bindParam(":title", $tag);
                $SQLStatement->execute();

                $tagNameRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

                if($tagNameRow == null){
                    $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$insertTagIntoDatabaseStatement);
                    $SQLStatement->bindParam(":title", $tag);
                    $SQLStatement->execute();

                    $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$getTagFromDatabaseStatement);
                    $SQLStatement->bindParam(":title", $tag);
                    $SQLStatement->execute();
    
                    $tagNameRow = $SQLStatement->fetch(PDO::FETCH_OBJ);
                }

                $tagsIDArray[$tag] = $tagNameRow->ID;
            }

            DatabaseManager::Disconnect();

            return $tagsIDArray;
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

            foreach($postTagsArray as $postTag){
                
                $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedCreation::$insertNewsfeedTagAssociation);
                $SQLStatement->bindParam(":postID", $postIDRow->ID);
                $SQLStatement->bindParam("tagID", $postTagsArrayID[$postTag]);
                $SQLStatement->execute();

            }

            DatabaseManager::Disconnect();

        }

        private static $insertNewsfeedTagAssociation = "
            INSERT INTO Newsfeed_Posts_Tags_Assignations (Newsfeed_Post_ID, Newsfeed_Tag_ID) VALUES (:postID, :tagID)
        ";

        private static $insertPostIntoDatabase = "
            INSERT INTO Newsfeed_Posts (Title, Content, URL, DateTime_Created) VALUES (:title, :content, :url, CURRENT_TIMESTAMP())
        ";

        private static $getPostByNameFromDatabaseStatement = "
            SELECT ID FROM Newsfeed_Posts WHERE Title = :title
        ";

        private static $getTagFromDatabaseStatement = "
            SELECT ID FROM Newsfeed_Tags WHERE Title = :title
        ";

        private static $insertTagIntoDatabaseStatement = "
            INSERT INTO Newsfeed_Tags (Title) VALUES (:title)
        ";
    }

?>