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
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";

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

    /**
     * Returns a HTML widget for a post.
     * @return string|null
     */
    public function getWidgetOld($showInteract=true): ?string{
        if(is_null($this->user) || is_null($this->likes) || is_null($this->replies)){
            return null;
        }

        if($showInteract) {
            $widget = "<div class=\"post\">
                            <div class=\"row\">
                                <div class=\"col-lg-10 content\">
                                    <div class=\"row user-info\">
                                        <div class=\"img\">";
        } else {
            $widget = "<div class=\"post\">
                            <div class=\"row\">
                                <div class=\"col-lg-12 content\">
                                    <div class=\"row user-info\">
                                        <div class=\"img\">";
        }

        if(is_null($this->user->getDisplayImage()) || $this->user->getDisplayImage() == ""){
            $widget .= "<img src=\"http://localhost/resource/images/profile/default.jpg\">";
        } else {
            $widget .= "<img src=\"http://localhost/resource/images/profile/" . $this->user->getDisplayImage() . "\">";
        }

        $widget .=  "</div>
                                        <div class=\"name\">
                                            <h3>" . $this->user->getName() . "</h3>
                                        </div>
                                        <div class=\"username\">
                                            <a><a href=\"http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $this->user->getUsername() . "\">@" . $this->user->getUsername() . "</a></p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class=\"row post-content\">
                                        " . $this->content . "
                                    </div>
                                </div>" . "
                                <div class=\"col-lg interact-splitter\">
                                </div>
                                <div class=\"col-lg-2 interact\">
                                    <div class=\"row\">
                                        <div class=\"col-sm-4 like-count\">
                                            " . $this->likes . "
                                        </div>
                                        <div class=\"col-sm-8 like-button\">";

        if(is_null($this->liked) || $this->liked==false){
            $widget .= "<button class=\"btn btn-dark btn-block mt-auto\" onclick=\"switchLike(" . $this->postID . ", this)\">Like</button>";
        } else {
            $widget .= "<button class=\"btn btn-dark btn-block mt-auto\" onclick=\"switchLike(" . $this->postID . ", this)\">Unlike</button>";
        }


        $widget .= "</div>
                                    </div>
                                    <hr>
                                    <div class=\"row\">
                                        <div class=\"col-sm-4 reply-count\">
                                            " . $this->replies . "
                                        </div>
                                        <div class=\"col-sm-8 like-button\">
                                            <button class=\"btn btn-dark btn-block\">Reply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>";

        return $widget;
    }

    public function getWidget($showInteract=true): ?string{
        if($showInteract){
            $fileLocation = $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/postFormat.html";
        } else {
            $fileLocation = $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/postFormatNoInteract.html";
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
     * @return int
     */
    public function getReplyToID(): int
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
     * @return string
     */
    public function getMediaFilename(): string
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