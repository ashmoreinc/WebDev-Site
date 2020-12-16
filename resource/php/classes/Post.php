<?php

class Post
{
    private $postID, $replyToID, $content, $mediaFilename, $datetime, $likes, $liked, $replies;


    private ?User $user;

    /**
     * Post constructor.
     * @param $postID
     * @param $replyToID
     * @param $content
     * @param $mediaFilename
     * @param $datetime
     * @param null $user
     * @param null $liked
     * @param null $likes
     * @param null $replies
     */
    public function __construct($postID, $replyToID, $content, $mediaFilename, $datetime, $user=null, $liked=null, $likes=null, $replies=null){
        $this->postID = $postID;
        $this->replyToID = $replyToID;
        $this->content = $content;
        $this->mediaFilename = $mediaFilename;
        $this->datetime = $datetime;

        $this->liked = $liked;
        $this->likes = $likes;
        $this->replies = $replies;

        $this->user = $user;
    }

    /**
     * Returns a Post class from a PostID input. Returns NULL if a post cannot be found.
     * @param $postID
     * @return Post|null
     */
    public static function getFromID($postID): ?Post {
        require_once "../dbconn.php";
        require_once "dbConnNotCreatedException.php";
        require_once "../session_management.php";

        try {
            $conn = getConn();
        } catch (dbConnNotCreatedException $e) {
            return null;
        }
        $curUser = getLoggedInUser($conn);


        // Get the post
        $postResult = $conn->query("SELECT userID, replyToID, content, mediaFilename, datetime, 
                                    (SELECT COUNT(*)
                                     FROM post_likes
                                     WHERE post_likes.postID=$postID) as likes,
                                    (SELECT COUNT(*)
                                     FROM posts as psts
                                     WHERE psts.replyToID=$postID) as replies
                                FROM posts
                                WHERE postID=$postID");

        // Get the status of if there has been a like.
        if (is_null($curUser)) {
            $liked = false;
        } else {
            $likedResult = $conn->query("SELECT COUNT(*) as cnt
                                            FROM post_likes
                                            WHERE postID=$postID AND userID=" . $curUser->getId());

            $liked = $likedResult->fetch_assoc()["cnt"] > 0;
        }
        $conn->close();


        if($postResult->num_rows > 0) {
            $row = $postResult->fetch_assoc();

            // Check if is current user
            if(!is_null($curUser)) {
                $isCurrentUser = $row["userID"] == $curUser->getId();
            } else {
                $isCurrentUser = false;
            }
            $user = new User($row["userID"], $isCurrentUser);

            return new Post($postID, $row["replyToID"], $row["content"], $row["mediaFileName"],
                $row["datetime"], $user, $liked, $row["likes"], $row["replies"]);
        } else {
            return NULL;
        }
    }

    public function getWidget($showInteract=true): ?string{
        if($showInteract){
            $fileLocation = "../../site-elements/postFormat.html";

            // Now check if we own the post
            require_once "../dbconn.php";
            require_once "dbConnNotCreatedException.php";
            require_once "../session_management.php";

            try {
                $conn = getConn();
            } catch (dbConnNotCreatedException $e) {
                return null;
            }
            // Get the current user
            $curUser = getLoggedInUser($conn);

            if(!is_null($curUser)){
                if($this->user->getId() == $curUser->getId()) {
                    $fileLocation = "../../site-elements/postFormatOwned.html";
                }
            }

        } else {
            $fileLocation = "../../site-elements/postFormatNoInteract.html";
        }

        $widgetFile = fopen($fileLocation, "r");
        $html = fread($widgetFile, filesize($fileLocation));

        // Replace all variables
        $html = str_replace("{SERVER_NAME}", $_SERVER["SERVER_NAME"], $html);
        $html = str_replace("{NAME}", $this->user->getName(), $html);
        $html = str_replace("{USERNAME}", $this->user->getUsername(), $html);
        $html = str_replace("{IMAGE_FILE}", ($this->user->getDisplayImage() == "" or
            is_null($this->user->getDisplayImage())) ? "default.jpg" : $this->user->getDisplayImage(), $html);
        $html = str_replace("{CONTENT}", $this->content, $html);
        $html = str_replace("{POST_ID}", $this->postID, $html);
        $html = str_replace("{LIKE_COUNT}", $this->likes, $html);
        $html = str_replace("{REPLY_COUNT}", $this->replies, $html);
        $html = str_replace("{LIKE_BTN_TEXT}", $this->liked ? "Unlike" : "Like", $html);

        // Date time section
        $date = new DateTime($this->datetime);
        $html = str_replace("{DATE}", $date->format("H:m d/m/Y"), $html);

        return $html;

    }

    // Getters
    /**
     * @return int
     */
    public function getPostID(): int
    {
        return $this->postID;
    }

    /**
     * @return null|int
     */
    public function getReplyToID(): ?int
    {
        return $this->replyToID;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return null|string
     */
    public function getMediaFilename(): ?string
    {
        return $this->mediaFilename;
    }

    /**
     * @return string
     */
    public function getDatetime(): string
    {
        return $this->datetime;
    }

    /**
     * @return null|User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return null|int
     */
    public function getLikes(): ?int
    {
        return $this->likes;
    }

    /**
     * @return null|bool
     */
    public function getLiked(): ?bool
    {
        return $this->liked;
    }

    /**
     * @return null|int
     */
    public function getReplies(): ?int
    {
        return $this->replies;
    }



}