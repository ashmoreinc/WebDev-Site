// Import jQuery
var script = document.createElement('script');
script.src = 'https://code.jquery.com/jquery-3.4.1.min.js'; // TODO: Maybe switch to the local file.
script.type = 'text/javascript';
document.getElementsByTagName('head')[0].appendChild(script);

function username_taken(username, url, handleDataFunc){
    // If the username is not blank, check the username exists with ajax.
    // Run the function handed as a parameter on the success event.
    if(username !== ""){
        $.ajax({
            url: url,
            type: 'POST',
            data: {username: username},
            dataType: "html",
            success: function(data){
                handleDataFunc(data);
            },
            error: function (){
                handleDataFunc("could not check username");
            }
        });
    }
    // tell the handle function that no username was set
    handleDataFunc("username not set");
}

function updateFollow(username, action, handleDataFunc) {
    if(username !== ""){
        $.ajax({
            url:  window.location.origin + "/resource/ajax/update_follow.php",
            type: 'POST',
            data: {username: username,
                    action: action},
            dataType: "html",
            success: function(data){
                handleDataFunc(data);
            },
            error: function (){
                handleDataFunc("could not check username");
            }
        });
    } else {
        handleDataFunc("username not set");
    }
}

function unfollow(username, btn){
    updateFollow(username, "unfollow", function(data){
        if(data === "success") {
            btn.innerHTML = "Follow";
            btn.onmouseup = function(){follow(username, btn);};
        } else {
            alert(data);
        }
    })
}

function follow(username, btn){
    updateFollow(username, "follow", function(data){
        if(data === "success") {
            btn.innerHTML = "Unfollow";
            btn.onmouseup = function(){unfollow(username, btn);};
        } else {
            alert(data);
        }
    })
}

function updateBlock(username, action, handleDataFunc){
    if(username !== ""){
        $.ajax({
            url: window.location.origin + "/resource/ajax/update_block.php",
            type: 'POST',
            data: {username: username,
                action: action},
            dataType: "html",
            success: function(data){
                handleDataFunc(data);
            },
            error: function (){
                handleDataFunc("could not check username");
            }
        });
    } else {
        handleDataFunc("username not set");
    }
}

function block(username, btn, reloadOnSuccess=true){
    updateBlock(username, "block", function(data){
        if(data === "success") {
            btn.innerHTML = "Unblock";
            btn.onmouseup = function(){
                unblock(username, btn);
            };
            if(reloadOnSuccess) location.reload();
        } else {
            alert(data);
        }
    })
}

function unblock(username, btn, reloadOnSuccess=true){
    updateBlock(username, "unblock", function(data){
        if(data === "success") {
            btn.innerHTML = "Block";
            btn.onmouseup = function(){
                block(username, btn);
            };
            if(reloadOnSuccess) location.reload();
        } else {
            alert(data);
        }
    })
}

function switchLike(postID, btn){
    $.ajax({
        url: window.location.origin + "/resource/ajax/switch_like.php",
        type: 'POST',
        data: {postID: postID},
        dataType: "html",
        success: function(data){
            if(data === "success") {
                if(btn.innerHTML === "Like") {
                    btn.innerHTML = "Unlike";
                } else if(btn.innerHTML === "Unlike") {
                    btn.innerHTML = "Like";
                } else {
                    btn.innerHTML = "What did you do?";
                }
            } else {
                alert("1Could not like. Try again or refresh the page.");
            }
        },
        error: function (){
            alert("2Could not like. Try again or refresh the page.");
        }
    });
}