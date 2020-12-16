<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";
$curUser = null;
$logged_on = false;

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


?>
<html lang="en">
    <head>
        <title>Exclusive</title>

        <?php // Import the header from a central location
            require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/standardhead.php";
        ?>
    </head>
    <body>
    <?php

    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/navbar.php";

    ?>

    <div class="container home-feed">
        <?php
        if($logged_on){
            ?>
            <div class="posts">
                <?php
                // Query database
                $sql = "SELECT posts.content, posts.postID, users.id, users.name, users.username, users.displayImageFilename, post_likes.likeID, 
                            (SELECT COUNT(*)
                            FROM post_likes 
                            WHERE post_likes.postID=posts.postID) as likes,
                            (SELECT COUNT(*)
                            FROM posts as psts
                            WHERE psts.replyToID=posts.postID) as replies
                        FROM posts
                        LEFT JOIN post_likes ON posts.postID=post_likes.postID and post_likes.userID=" . $curUser->getId() . "
                        LEFT JOIN users ON users.id=posts.userID
                        LEFT JOIN user_connections ON user_connections.secondUserID=users.id
                        WHERE user_connections.firstUserID=" . $curUser->getId() . " AND user_connections.secondUserID=posts.userID AND user_connections.isFollowing=1
                            AND (EXISTS(SELECT * 
                                        FROM users
                                        WHERE users.id=posts.userID
                                            AND users.isPrivate=0)
                                OR EXISTS(SELECT * 
                                          FROM user_connections
                                          WHERE user_connections.firstUserID=posts.userID
                                            AND user_connections.secondUserID=" . $curUser->getId() . "
                                            AND user_connections.isFollowing=1))
                        ORDER BY posts.datetime DESC";


                $results = $conn->query($sql);

                if($results->num_rows > 0) {
                    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/User.php";
                    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/Post.php";
                    while($row = $results->fetch_assoc()){

                        $user = new User($row["id"], $row["id"]==$curUser->getId());

                        $post = new Post($row["postID"], $row["replyToID"], $row["content"], "", "", $user,
                            !is_null($row["likeID"]), $row["likes"], $row["replies"]);

                        echo $post->getWidget();
                        ?>

                        <!--<div class="post">
                            <div class="row">
                                <div class="col-lg-10 content">
                                    <div class="row user-info">
                                        <div class="img">
                                            <?php
                                            if(is_null($row["displayImageFilename"]) || $row["displayImageFilename"]=="") {
                                                ?> <img src="http://localhost/resource/images/profile/default.jpg" > <?php
                                            } else {
                                                ?> <img src="http://localhost/resource/images/profile/<?php echo $row["displayImageFilename"]; ?>" > <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="name">
                                            <h3><?php echo $row["name"]; ?></h3>
                                        </div>
                                        <div class="username">
                                            <p>@<?php echo $row["username"]; ?></p>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row post-content">
                                        <?php echo $row["content"]; ?>
                                    </div>
                                </div>
                                <div class="col-lg interact-splitter">
                                </div>
                                <div class="col-lg-2 interact">
                                    <div class="row">
                                        <div class="col-sm-4 like-count">
                                            <?php echo $row["likes"]; ?>
                                        </div>
                                        <div class="col-sm-8 like-button">
                                            <?php

                                            if(is_null($row["likeID"])){
                                                ?> <button class="btn btn-dark btn-block mt-auto" onclick="switchLike(<?php echo $row["postID"]; ?>, this)">Like</button> <?php
                                            } else {
                                                ?> <button class="btn btn-dark btn-block mt-auto" onclick="switchLike(<?php echo $row["postID"]; ?>, this)">Unlike</button> <?php
                                            }

                                            ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4 reply-count">
                                            <?php echo $row["replies"]; ?>
                                        </div>
                                        <div class="col-sm-8 like-button">
                                            <button class="btn btn-dark btn-block">Reply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> -->

                        <?php
                    }
                } else {
                    ?>
                    <div class="alert alert-dark" role="alert">
                        No one you follow has posted anything.. You may need more interesting friends.
                    </div>
                    <?php
                }

                ?>
            </div>
        <?php
        } else {
            ?>

            <div class="alert-alert-dark" role="alert">
                You need to log on first.
            </div>

            <?php
        }
        ?>
    </div>

    </body>
</html>

<?php
$conn->close();
?>