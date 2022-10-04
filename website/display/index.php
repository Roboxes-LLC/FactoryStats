<?php

require_once '../common/authentication.php';

session_start();

Authentication::authenticate();

if (!Authentication::isAuthenticated())
{
   if (!isset($_SERVER['PHP_AUTH_USER']))
   {
      // Trigger browswer to send basic HTTP authentication.
      header('WWW-Authenticate: Basic realm="Factory Stats"');
      
      // Authentication cancelled.
      $_SESSION["redirect"] = "display/";
      header('Location: /pages/unauthorized.php');
      exit;
   }
   else
   {
      // Basic HTTP authentication failed.
      $_SESSION["redirect"] = "display/";
      header('Location: /pages/unauthorized.php');
      exit;
   }
}

?>

<!DOCTYPE html>
<html>
   <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">
   
      <link rel="stylesheet" type="text/css" href="../css/flex.css"/>
      <link rel="stylesheet" type="text/css" href="../css/pages.css"/>
      
      <script src="../script/common.js"></script>
      <script src="display.js"></script>
      <script src="slideShow.js"></script>
      <script>
         window.onload = function() {
            display = new Display("body");
            display.start();
        }
      </script>

   </head>
   <body id="body">
      <!-- Blank Factory Stats page -->
   </body>
</html>