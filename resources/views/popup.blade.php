<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Authorization</title>
</head>
<body>
    <button onclick="test()">Authorize</button>
    <script type="text/javascript">

    function test() {
        // Open a popup window with the authorization URL
        var popup = window.open('{{ $authorizationUrl }}', 'oauth_popup', 'width=600,height=400');
        // Close the popup and redirect the parent window to the callback URL when the child window is closed
        window.onunload = function(){
            window.opener.location.reload(true);
        };
        
    }
    </script>
</body>
</html>
