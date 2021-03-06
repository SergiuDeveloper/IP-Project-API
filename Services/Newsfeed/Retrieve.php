<?php

    if(!defined('ROOT')){
        define('ROOT', dirname(__FILE__) . '/..');
    }

    require_once(ROOT . "/Utility/Utilities.php");

    CommonEndPointLogic::ValidateHTTPGETRequest();

    $email          = $_GET["email"];
    $hashedPassword = $_GET["hashedPassword"];
    $postsCount     = $_GET["postsCount"];

    $apiKey = $_GET["apiKey"];

    if($apiKey != null){
        try {
            $credentials = APIKeyHandler::getInstance()->setAPIKey($apiKey)->getCredentials();
        } catch (APIKeyHandlerKeyUnbound $e) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("UNBOUND_KEY"))
                ->send(StatusCodes::INTERNAL_SERVER_ERROR);
        } catch (APIKeyHandlerAPIKeyInvalid $e) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INVALID_KEY"))
                ->send();
        }

        $email = $credentials->getEmail();
        //$hashedPassword = $credentials->getHashedPassword();
    } else {
        if ($email == null || $hashedPassword == null) {
            ResponseHandler::getInstance()
                ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_CREDENTIAL"))
                ->send(StatusCodes::BAD_REQUEST);
        }
        CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);
    }

    if ($postsCount == null) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("NULL_INPUT"))
            ->send(StatusCodes::BAD_REQUEST);
    }

    //CommonEndPointLogic::ValidateUserCredentials($email, $hashedPassword);

    /**
     * LIMIT with param does not work, needs hardcoding
     */
    $getTagsQuery = "
    SELECT t.title FROM newsfeed_tags t join newsfeed_posts_tags_assignations r ON t.ID = r.Newsfeed_Tag_ID 
    JOIN newsfeed_posts p ON p.ID = r.Newsfeed_Post_ID
    WHERE p.ID = :postID;
        ";

    $postsArray = array();
    try {
        DatabaseManager::Connect();

        $getPosts = DatabaseManager::PrepareStatement("SELECT ID, title, content, 
          URL, DateTime_Created FROM Newsfeed_Posts Order by DateTime_Created LIMIT $postsCount");
        $getPosts->execute();

        while($getPostsRow = $getPosts->fetch(PDO::FETCH_ASSOC)){
            $post = new Posts();
            $post->set_title($getPostsRow["title"]);
            $post->set_content($getPostsRow["content"]);
            $post->set_URL($getPostsRow["URL"]);
            $post->set_dateCreated($getPostsRow["DateTime_Created"]);

            $getTags = DatabaseManager::PrepareStatement($getTagsQuery);
            $getTags->bindParam(":postID", $getPostsRow["ID"]);
            $getTags->execute();
            while($getTagsRow = $getTags->fetch(PDO::FETCH_ASSOC)){
                $post->add_tags($getTagsRow['title']);
            }

            array_push($postsArray,$post);
        }

        DatabaseManager::Disconnect();
    }
    catch (Exception $databaseException) {
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT"))
            ->send();
        /*
        $response = CommonEndPointLogic::GetFailureResponseStatus("DB_EXCEPT");
        echo json_encode($response);
        http_response_code(StatusCodes::OK);
        die();
        */
    }

    try{
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetSuccessResponseStatus())
            ->addResponseData("posts", $postsArray)
            ->send();
    }
    catch (Exception $exception){
        ResponseHandler::getInstance()
            ->setResponseHeader(CommonEndPointLogic::GetFailureResponseStatus("INTERNAL_SERVER_ERROR"))
            ->send(StatusCodes::INTERNAL_SERVER_ERROR);
    }

    /*
    $responseSuccess = CommonEndPointLogic::GetSuccessResponseStatus();
    echo json_encode($responseSuccess), PHP_EOL;
    echo json_encode($postsArray), PHP_EOL;
    http_response_code(StatusCodes::OK);
    */

    class Posts {
        public $title;
        public $content;
        public $URL;
        public $dateCreated;
        public $tags;
        function __construct()
        {
          $this->tags = array();
        }
        function set_title($title) {
          $this->title = $title;
        }
        function set_content($content) {
            $this->content = $content;
        }
        function set_URL($URL) {
          $this->URL = $URL;
        }
        function set_dateCreated($dateCreated) {
          $this->dateCreated = $dateCreated;
        }
        function add_tags($tag) {
          array_push($this->tags, $tag);
        }
      }

?>
