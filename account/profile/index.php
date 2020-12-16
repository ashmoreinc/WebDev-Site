<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";

// Get the currently logged on user if any
$curUser = null;
$logged_on = false;

try {
    $conn = getConn();
    $logged_on = isLoggedIn($conn);
} catch (dbConnNotCreatedException $error) {
    die($error->getMessage());
}

if ($logged_on){
    $curUser = getLoggedInUser($conn);
}
// Get the data for the users page
$pageUser = null;
// If there is a user id given, check the database for it
if(isset($_GET["user"])){
    $uname = $_GET["user"];
    $result = $conn->query("SELECT id FROM users WHERE username='$uname'");

    if($result){
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            try {
                if(is_null($curUser)){
                    $pageUser = new User($row["id"]);
                } else if ($row["id"] == $curUser->getId()) {
                    $pageUser = new User($row["id"], true);
                } else {
                    $pageUser = new User($row["id"]);
                }
            } catch (userNotFoundException $ignored) {}
        }
    }
}

// Check the loggedin users relationships, if any, to this user
$isBlocked = false; // curuser -> pageuser
$isBlocking = false; // pageuser -> cursuer
$isFollowed = false; // pageuser -> curuser
$isFollowing = false; // curuser -> pageuser

if($logged_on && !is_null($pageUser)) {
    // Check the current users connections to the page user
    $sql = "SELECT isFollowing, isBlocked 
            FROM user_connections 
            WHERE firstUserID=" . $curUser->getId() . "
                AND secondUserID=" . $pageUser->getId();

    $result = $conn->query($sql);

    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $isBlocking = $row["isBlocked"] == 1;
        $isFollowing = $row["isFollowing"] == 1;
    }

    // Check the page users connections to the current users
    $sql = "SELECT isFollowing, isBlocked 
            FROM user_connections 
            WHERE firstUserID=" . $pageUser->getId() . "
                AND secondUserID=" . $curUser->getId();

    $result = $conn->query($sql);

    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $isBlocked = $row["isBlocked"] == 1;
        $isFollowed = $row["isFollowing"] == 1;
    }
}

?>
<html lang="en">
<head>
    <?php
        // Create the title as the user name if there is a user
        if(!is_null($pageUser)){
            echo "<title>" . $pageUser->getName() . "</title>";
        } else {
            echo "<title>User not found.</title>";
        }

        // Import the header from a central location
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/standardhead.php";
    ?>

    <script>
        function showFollowers(){
            let posts = document.getElementById("posts-section");
            let followers = document.getElementById("followers-section");
            let following = document.getElementById("following-section");

            posts.classList.add("hidden");
            followers.classList.remove("hidden");
            following.classList.add("hidden");
        }

        function showFollowing(){
            let posts = document.getElementById("posts-section");
            let followers = document.getElementById("followers-section");
            let following = document.getElementById("following-section");

            posts.classList.add("hidden");
            followers.classList.add("hidden");
            following.classList.remove("hidden");
        }

        function showPosts(){
            let posts = document.getElementById("posts-section");
            let followers = document.getElementById("followers-section");
            let following = document.getElementById("following-section");

            posts.classList.remove("hidden");
            followers.classList.add("hidden");
            following.classList.add("hidden");
        }
    </script>
</head>
<body>
    <?php
        // Import the navbar
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/navbar.php";
    ?>

    <div class="profile-banner jumbotron">
        <div class="container-md profile-container">
            <div class="row">
                <?php
                    if(!is_null($pageUser)){ //  If a user for this page has been set, create the header features for a full profile
                        ?>
                        <div class="profile-img col-md-4">
                            <?php
                                if(is_null($pageUser->getDisplayImage()) or $pageUser->getDisplayImage() == "") {
                                    ?> <img src="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/resource/images/profile/default.jpg"> <?php
                                } else {
                                    echo "<img src=\"http://" . $_SERVER["SERVER_NAME"] . "/resource/images/profile/" . $pageUser->getDisplayImage() . "\">";
                                }

                            ?>
                        </div>
                        <div class="profile-info col-md-8">
                            <div class="row">
                                <div class="col">
                                    <h1 class="display-4"><?php echo $pageUser->getName(); ?></h1>
                                    <p class="lead">@<?php echo $pageUser->getUsername() ?> <?php if($isFollowed) {echo "<span class=\"follows-back-msg\">Follows you</span>"; } ?></p>
                                </div>
                                <div class="col">
                                    <?php
                                        if(!$pageUser->getIsCurrentUser()){
                                    ?>
                                        <div class="dropdown float-right user-options">
                                            <button class="btn btn-outline-dark" type="button" id="userOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-three-dots" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <!-- 3 dots glyph -->
                                                    <path fill-rule="evenodd" d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                                </svg>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userOptions">
                                                <?php
                                                    if($isBlocking) {
                                                        ?> <a class="dropdown-item" onmouseup="unblock('<?php echo $pageUser->getUsername(); ?>', this);">Unblock</a> <?php
                                                    } else {
                                                        ?> <a class="dropdown-item" onmouseup="block('<?php echo $pageUser->getUsername(); ?>', this);">Block</a> <?php
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                        <?php
                                        }
                                        ?>

                                </div>
                            </div>
                            <hr class="profile-divider my-4">
                            <p><?php

                                $bio = $pageUser->getBio();

                                if(is_null($bio)){
                                    echo "This user does not have a bio.";
                                } else if($bio == "") {
                                    echo "This user does not have a bio.";
                                } else {
                                    echo $bio;
                                }

                                ?></p>
                            <?php
                                if(!$pageUser->getIsCurrentUser()){ // Only show the follow button if it is not the current user page
                                    if(!$isBlocked && !$isBlocking){
                                        if($isFollowing) {
                                            ?>
                                                <button class="profile-btn btn btn-dark btn-md" role="button" onmouseup="unfollow('<?php echo $pageUser->getUsername(); ?>', this)">Unfollow</button>
                                            <?php

                                        } else {
                                            ?>
                                                <button class="profile-btn btn btn-dark btn-md" role="button" onmouseup="follow('<?php echo $pageUser->getUsername(); ?>', this);">Follow</button>
                                            <?php
                                        }
                                    }
                                }
                            ?>
                        </div>
                        <?php
                    } else { // Identify that no user has been selected.
                        ?>
                        <div class="profile-img col-md-4">
                            <img src="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/resource/images/profile/default.jpg">
                        </div>
                        <div class="profile-info col-md-8">
                            <h1 class="display-4">User not found</h1>
                            <p class="lead"></p>
                            <hr class="my-4">
                            <p>There is no user selected. Try search again.</p>
                        </div>
                        <?php
                    }
                ?>
            </div>
        </div>
    </div>

    <div class="container section-nav">
        <div class="row">
            <div class="col- ml-5 px-1">
                <button id="post-nav-btn" class="btn btn-secondary" onclick="showPosts()">Posts</button>
            </div>
            <div id="follows-nav-btn" class="col- px-1">
                <button class="btn btn-secondary" onclick="showFollowing()">Follows</button>
            </div>
            <div id="followers-nav-btn" class="col- px-1">
                <button class="btn btn-secondary" onclick="showFollowers()">Followers</button>
            </div>
        </div>
    </div>

    <div id="posts-section" class="container posts-section follow-section">
        <?php
        // Check for post delete errors and success. Errors can show when no account is loaded. So we dont need to check
        //  For an account
        if(isset($_GET["del-err"])) {
            ?>
                <div class="alert alert-danger" role="alert">
                    <?php steriliseInput($conn, $_GET["del-err"]) ?>
                </div>
            <?php
        }

        if(isset($_GET["del-succ"])) {
            ?>
            <div class="alert alert-success" role="alert">
                Post successfully deleted.
            </div>
            <?php
        }

        if($pageUser->getIsCurrentUser()){
            ?>

            <div class="post-composer">
                <?php

                if(isset($_GET["post-comp-error"])) {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <?php
                        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/common_functions.php";
                        echo steriliseInput($conn, $_GET["post-comp-error"]); ?>
                    </div>
                    <?php
                }
                if(isset($_GET["post-comp-success"])){
                    ?>
                    <div class="alert alert-success" role="alert">
                        Your post has successfully uploaded.
                    </div>
                    <?php
                }
                ?>
                <h3>Compose new post</h3>
                <hr>
                <form method="post" action="http://<?php echo $_SERVER["SERVER_NAME"] . "/account/profile/post/create_post.php"; ?>">
                    <div class="form-group">
                        <textarea name="post-content" class="form-control" id="post-input-area" rows="3" placeholder="Tell the world something interesting..."></textarea>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">Post</button>
                    </div>
                </form>
            </div>

            <?php
        }

        if(!$pageUser->getIsCurrentUser() && $isBlocked) {
            ?>
            <div class="alert alert-dark" role="alert">
                You are blocked from viewing this profile.
            </div>
            <?php
        } else if (!$pageUser->getIsCurrentUser() && $pageUser->getIsPrivate() && !$isFollowed) { // Check if the user is private and not following us
            ?>
            <div class="alert alert-dark" role="alert">
                This users account is private. You will be able to see their posts once you both follow each other.
            </div>
            <?php
        } else{
            // Get all recent posts.
            if($logged_on) {
                $sql = "SELECT posts.postID, replyToID, content, mediaFilename, posts.datetime as time, post_likes.likeID, (
                            SELECT COUNT(*)
                            FROM post_likes
                            WHERE post_likes.postID=posts.postID
                        ) as likes, (
                            SELECT COUNT(*)
                            FROM posts as pst
                            WHERE pst.replyToID=posts.postID
                        ) as replies
                        FROM posts
                        LEFT JOIN post_likes
                        ON post_likes.postID = posts.postID and post_likes.userID=" . $curUser->getId() . "
                        WHERE posts.userID=" . $pageUser->getId() . "
                        ORDER BY posts.datetime DESC
                        ";
            } else { // Same query, but likeID will always be null
                $sql = "SELECT posts.postID, replyToID, content, mediaFilename, posts.datetime as time, post_likes.likeID, (
                            SELECT COUNT(*)
                            FROM post_likes
                            WHERE post_likes.postID=posts.postID
                        ) as likes, (
                            SELECT COUNT(*)
                            FROM posts as pst
                            WHERE pst.replyToID=posts.postID
                        ) as replies
                        FROM posts
                        LEFT JOIN post_likes
                        ON post_likes.likeID = -1
                        WHERE posts.userID=" . $pageUser->getId() . "
                        ORDER BY posts.datetime DESC
                        ";
            }

            $results = $conn->query($sql);

            if($results->num_rows > 0) {
                require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/Post.php";
                while($row = $results->fetch_assoc()){
                    $post = new Post($row["postID"], $row["replyToID"], $row["content"], "", $row["time"], $pageUser,
                        !is_null($row["likeID"]), $row["likes"], $row["replies"]);

                    echo $post->getWidget();
                }
            } else {
                ?>
                <div class="alert alert-dark" role="alert">
                    This user has no posts... Boring.
                </div>
                <?php
            }
        }
        ?>
    </div>
    <div id="following-section" class="container hidden followers-section follow-section">
        <?php
        if($pageUser->getIsCurrentUser() || ($pageUser->getShowFollowing() && !$isBlocked && (!$pageUser->getIsPrivate() || $isFollowed))){
            ?>

            <div class="head-container">
                <h3><?php echo $pageUser->getName(); ?> follows</h3>
            </div>

            <?php

            $sql = "SELECT users.name, users.username, users.displayImageFilename, users.bio
                    FROM users
                    LEFT JOIN user_connections
                    ON user_connections.secondUserID=users.id
                    WHERE user_connections.isFollowing=1
                        AND user_connections.firstUserID=" . $pageUser->getId();

            $result = $conn->query($sql);

            if($result->num_rows > 0) {
                while($row=$result->fetch_assoc()){
                    ?>

                    <div class="user-result">
                        <div class="row">
                            <div class="profile-image col-md-2 d-flex justify-content-center">
                                <a href="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $row["username"] ?>">
                                    <img class="mr-auto ml-auto" src="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/resource/images/profile/" . ((is_null($row["displayImageFilename"]) || $row["displayImageFilename"] == "") ? "default.jpg" : $row["displayImageFilename"]); ?>">
                                </a>
                            </div>
                            <div class="content col-md-8">
                                <div class="user-info row">
                                    <h1><?php echo $row["name"] ?></h1>
                                    <h5><a href="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $row["username"] ?>">@<?php echo $row["username"] ?></a></h5>
                                </div>
                                <hr>
                                <div class="user-bio row">
                                    <p><?php

                                        if(is_null($row["bio"])){
                                            echo "This user does not have a bio.";
                                        } else if($row["bio"] == "") {
                                            echo "This user does not have a bio.";
                                        } else {
                                            echo $row["bio"];
                                        }

                                        ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                }
            } else {
                ?>

                <div class="alert alert-light" role="alert">
                    Nobody. How boring!
                </div>
                <?php
            }

        }

        ?>
    </div>
    <div id="followers-section" class="container hidden following-section follow-section">
        <?php
        if($pageUser->getIsCurrentUser() || ($pageUser->getShowFollowers() && !$isBlocking && (!$pageUser->getIsPrivate() || $isFollowed))){
            ?>

            <div class="head-container">
                <h3><?php echo $pageUser->getName(); ?> is followed by</h3>
            </div>

            <?php

            $sql = "SELECT users.name, users.username, users.displayImageFilename, users.bio
                    FROM users
                    LEFT JOIN user_connections
                    ON user_connections.firstUserID=users.id
                    WHERE user_connections.isFollowing=1
                        AND user_connections.secondUserID=" . $pageUser->getId();

            $result = $conn->query($sql);

            if($result->num_rows > 0) {
                while($row=$result->fetch_assoc()){
                    ?>

                    <div class="user-result">
                        <div class="row">
                            <div class="profile-image col-md-2 d-flex justify-content-center">
                                <a href="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $row["username"] ?>">
                                    <img class="mr-auto ml-auto" src="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/resource/images/profile/" . ((is_null($row["displayImageFilename"]) || $row["displayImageFilename"] == "") ? "default.jpg" : $row["displayImageFilename"]); ?>">
                                </a>
                            </div>
                            <div class="content col-md-8">
                                <div class="user-info row">
                                    <h1><?php echo $row["name"] ?></h1>
                                    <h5><a href="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $row["username"] ?>">@<?php echo $row["username"] ?></a></h5>
                                </div>
                                <hr>
                                <div class="user-bio row">
                                    <p><?php

                                        if(is_null($row["bio"])){
                                            echo "This user does not have a bio.";
                                        } else if($row["bio"] == "") {
                                            echo "This user does not have a bio.";
                                        } else {
                                            echo $row["bio"];
                                        }

                                        ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                }
            } else {
                ?>

                <div class="alert alert-light" role="alert">
                    Nobody. How boring!
                </div>
                <?php
            }

        }

        ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>