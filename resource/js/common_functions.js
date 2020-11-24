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