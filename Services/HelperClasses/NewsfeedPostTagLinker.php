<?php

    require_once("DatabaseManager.php");

    class NewsfeedTagLinker{
        
        public static function createMissingTagsAndgetPostTagsID($tagsArray){
            DatabaseManager::Connect();

            foreach ($tagsArray as $tag){
                $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedTagLinker::$getTagFromDatabaseStatement);
                $SQLStatement->bindParam(":title", $tag);
                $SQLStatement->execute();

                $tagNameRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

                if($tagNameRow == null){
                    $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedTagLinker::$insertTagIntoDatabaseStatement);
                    $SQLStatement->bindParam(":title", $tag);
                    $SQLStatement->execute();

                    $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedTagLinker::$getTagFromDatabaseStatement);
                    $SQLStatement->bindParam(":title", $tag);
                    $SQLStatement->execute();
    
                    $tagNameRow = $SQLStatement->fetch(PDO::FETCH_OBJ);
                }

                $tagsIDArray[$tag] = $tagNameRow->ID;
            }

            DatabaseManager::Disconnect();

            return $tagsIDArray;
        }

        public static function AssociatePostWithTags($postID, $postTagsArray, $postTagsArrayID){
            
            DatabaseManager::Connect();

            foreach($postTagsArray as $postTag){
                
                $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedTagLinker::$insertNewsfeedTagAssociation);
                $SQLStatement->bindParam(":postID", $postID);
                $SQLStatement->bindParam("tagID", $postTagsArrayID[$postTag]);
                $SQLStatement->execute();

            }

            DatabaseManager::Disconnect();

        }

        public static function RemoveAllTagsForPost($postID){
            
            DatabaseManager::Connect();

            $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedTagLinker::$removeAllTagsStatement);
            $SQLStatement->bindParam(":postID", $postID);
            $SQLStatement->execute();

            DatabaseManager::Disconnect();

        }

        private static $removeAllTagsStatement = "
            DELETE FROM Newsfeed_Posts_Tags_Assignations WHERE Newsfeed_Post_ID = :postID
        ";

        private static $insertNewsfeedTagAssociation = "
            INSERT INTO Newsfeed_Posts_Tags_Assignations (Newsfeed_Post_ID, Newsfeed_Tag_ID) VALUES (:postID, :tagID)
        ";

        private static $getTagFromDatabaseStatement = "
            SELECT ID FROM Newsfeed_Tags WHERE Title = :title
        ";

        private static $insertTagIntoDatabaseStatement = "
            INSERT INTO Newsfeed_Tags (Title) VALUES (:title)
        ";
    }

?>