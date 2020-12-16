<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/common_functions.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/Post.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";


try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    die();
}

if(isset($_POST["pid"])){
    $postId = steriliseInput($conn, $_POST["pid"]);
    $post = Post::getFromID((int)$postId);
} else {
    die();
}
?>


<div id="reply-overlay" class="reply">
    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-x exit-button" viewBox="0 0 16 16" onclick="this.parentElement.remove();">
        <path fill-rule="evenodd" d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
    </svg>
    <div id="reply-overlay-widget" class="row vertical-align vcenter vh-100 widget">
        <div class="col-12 align-self-center">
            <div class="container">
                <div id="reply-post-view">
                    <?php
                        echo $post->getWidget(false);
                    ?>
                    <div class="post-composer">
                        <?php

                        if(isset($_GET["post-comp-error"])) {
                            ?>
                            <div class="alert alert-danger" role="alert">
                                <?php
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
                        <h3>Reply</h3>
                        <hr>
                        <form method="post" action="http://<?php echo $_SERVER["SERVER_NAME"] . "/account/profile/post/create_post.php"; ?>">
                            <input type="hidden" name="repliesTo" value="<?php echo $postId; ?>">
                            <input type="hidden" name="navToPost" value="1">
                            <div class="form-group">
                                <textarea name="post-content" class="form-control" id="post-input-area" rows="3" placeholder="Tell the world something interesting..."></textarea>
                            </div>
                            <div class="form-group">
                                <button class="btn btn-primary" type="submit">Post</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
$conn ->close();