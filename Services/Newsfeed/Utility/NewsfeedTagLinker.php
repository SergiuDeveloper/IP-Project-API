<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/../..');
    }

    require_once(ROOT . "/Utility/DatabaseManager.php");
    require_once(ROOT . "/Utility/CommonEndPointLogic.php");
    require_once(ROOT . "/Utility/StatusCodes.php");
    require_once(ROOT . "/Utility/ResponseHandler.php");

    class NewsfeedTagLinker{
        
        public static function createMissingTagsAndGetPostTagsID($tagsArray){
            try {
                DatabaseManager::Connect();

                $tagsIDArray = array();

                foreach ($tagsArray as $tag) {
                    $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedTagLinker::$getTagFromDatabaseStatement);
                    $SQLStatement->bindParam(":title", $tag);
                    $SQLStatement->execute();

                    $tagNameRow = $SQLStatement->fetch(PDO::FETCH_OBJ);

                    if ($tagNameRow == null) {
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
            catch(Exception $exception){
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

        public static function AssociatePostWithTags($postID, $postTagsArray, $postTagsArrayID){
            try {
                DatabaseManager::Connect();

                foreach ($postTagsArray as $postTag) {

                    $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedTagLinker::$insertNewsfeedTagAssociation);
                    $SQLStatement->bindParam(":postID", $postID);
                    $SQLStatement->bindParam("tagID", $postTagsArrayID[$postTag]);
                    $SQLStatement->execute();

                }

                DatabaseManager::Disconnect();
            }
            catch(Exception $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                */
            }
        }

        public static function RemoveAllTagsForPost($postID){
            try {
                DatabaseManager::Connect();

                $SQLStatement = DatabaseManager::PrepareStatement(NewsfeedTagLinker::$removeAllTagsStatement);
                $SQLStatement->bindParam(":postID", $postID);
                $SQLStatement->execute();

                DatabaseManager::Disconnect();
            }
            catch(Exception $exception){
                ResponseHandler::getInstance()
                    ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
                    ->send();
                /*
                $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");

                echo json_encode($response), PHP_EOL;
                http_response_code(StatusCodes::OK);
                die();
                 */
            }
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
