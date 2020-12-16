<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/Post.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/User.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/common_functions.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";

$curUser = null;
$logged_on = false;

// Create database connection
try {
    $conn = getConn();
    $logged_on = isLoggedIn($conn);
    if ($logged_on){
        $curUser = getLoggedInUser($conn);
    }
} catch (dbConnNotCreatedException $e) {
    $conn = null;
    header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error', true, 500);
    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/no_dbconn.php";
    die();
}

if(isset($_GET["pid"])){
    $postID = steriliseInput($conn, $_GET["pid"]);
    $post = Post::getFromID((int)$postID);
} else {
    $post = null;
}


// Check if the current user has viewing privileges
//      Ie not blocked, or private and not following
$canView = false;

if(!is_null($post)) {
    // Get the post authors private status
    $sql = "SELECT isPrivate
            FROM users 
            WHERE id=" . $post->getUser()->getId();

    $result = $conn->query($sql);

    if($result->num_rows > 0) {
        $isPrivate = $result->fetch_assoc()["isPrivate"];
    } else {
        $post = null;
    }

    if(!is_null($post)) {
        if ($logged_on) {
            // Check block and follow status of post author to current user
            $sql = "SELECT isBlocked, isFollowing
                    FROM user_connections
                    WHERE firstUserID=" . $post->getUser()->getId() . " AND secondUserID=" . $curUser->getId();

            $isFollowing = false;
            $isBlocked = false;

            $result = $conn->query($sql);

            if($result->num_rows > 0) {
                $row = $result->fetch_assoc();

                $isFollowing = $row["isFollowing"] == 1;
                $isBlocked = $row["isBlocked"] == 1;
            }

            // Now choose whether to show the post
            if(!$isBlocked) {
                if(!$isPrivate || $isFollowing) {
                    $canView = true;
                }
            }
        } else {
            // User can view if page isn't private
            if ($isPrivate != 1) {
                $canView = true;
            } else {
                // Cant find the user, therefore cannot show the post.
                $post = NULL;
            }
        }
    }
}

?>
<html>
<head>
    <?php
    // Check if post is available
    if(is_null($post)){
        echo "<title>No post could be found.</title>";
    } else if($canView) {
        echo "<title>View Post - " . substr($post->getContent(), 0, 7) ."...</title>";
    } else {
        echo "<title>Cannot view post.</title>";
    }

    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/standardhead.php";

    ?>
</head>
<body>

<?php
// Import the nav bar
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/navbar.php";

?>

<div class="container post-thread">
    <div class="origin">
        <?php

        if (is_null($post)){
            ?>
                <div class="alert alert-danger" role="alert">
                    No post could be found.
                </div>
            <a href="http://<?php echo $_SERVER["SERVER_NAME"]?>" class="btn btn-primary">Return Home</a>
            <?php
        } else {
            if(!is_null($post->getReplyToID())){
                $replyTo = Post::getFromID($post->getReplyToID());
                ?>
                    <div class="post-before">
                        <?php echo $replyTo->getWidget(); ?>
                    </div>
                    <div class="row align-items-stretch post-current">
                        <div class="col- thread-ext-cont">
                            <div class="thread-extender"></div>
                        </div>
                        <div class="col">
                            <?php echo $post->getWidget(); ?>
                        </div>
                    </div>
                <?php


            } else {
                echo $post->getWidget();
            }

        }
        ?>
    </div>
    <div class="replies">
        <?php
        if(!is_null($post)) {
            // Get the replies
            $sql = "SELECT posts.postID, posts.userID, posts.content, posts.content, posts.datetime, posts.mediaFilename, 
                        (SELECT COUNT(*)
                            FROM post_likes
                            WHERE post_likes.postID=posts.postID) AS likes,
                        (SELECT COUNT(*)
                            FROM posts as psts
                            WHERE psts.replyToID=posts.postID) AS replies,
                        (SELECT COUNT(*)
                            FROM post_likes
                            WHERE post_likes.postID=posts.postID
                                AND post_likes.userID=8) AS liked
                    FROM posts
                    LEFT JOIN users
                    ON users.id=posts.userID
                    LEFT JOIN user_connections
                    ON user_connections.firstUserID=posts.userID AND user_connections.secondUserID=" . $curUser->getId() . "
                    WHERE posts.replyToID=" . $post->getPostID() . "
                            AND (posts.userID=" . $curUser->getId() . " OR 
                                ( # Check the original post is ours
                                    EXISTS (SELECT * FROM posts as pst WHERE pst.postID=posts.replyToID AND pst.userID=" . $curUser->getId() . ")
                                ) OR 
                                ( # Check user connections/viewing rights	
                                    (# Check for block
                                        (user_connections.firstUserID=posts.userID AND user_connections.secondUserID=" . $curUser->getId() . " AND user_connections.isBlocked!=1) 
                                        OR
                                        NOT EXISTS (SELECT * FROM user_connections WHERE user_connections.firstUserID=posts.userID AND user_connections.secondUserID=" . $curUser->getId() . ")
                                    )
                                    AND 
                                    (# Check for private or follow
                                        (users.isPrivate!=1 OR (user_connections.firstUserID=posts.userID AND user_connections.secondUserID=" . $curUser->getId() . " AND user_connections.isFollowing=1))
                                    )                
                                ))";

            $result=$conn->query($sql);

            if($result->num_rows > 0) {

                // Signify this is now the replies
                ?>
                <hr>
                <?php

                while($row=$result->fetch_assoc()){
                    $replyUser = new User($row["userID"], $row["userID"] == $curUser->getId());
                    $replyPost = new Post($row["postID"], $post->getPostID(), $row["content"], $row["mediaFilename"], $row["datetime"],
                    $replyUser, (bool)$row["liked"], $row["likes"], $row["replies"]);

                    echo $replyPost->getWidget();
                }
            } else {
                ?>

                <div class="alert alert-light" role="alert">
                    There are no replies to this post that you are able to view.
                </div>

                <?php
            }
        }
        ?>
    </div>
</div>


</body>
</html>
<?php
$conn->close();
?>