<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/displayDefs.php';

session_start();

Authentication::authenticate();

if (!Authentication::isAuthenticated())
{
   if (!isset($_SERVER['PHP_AUTH_USER']))
   {
      // Trigger browswer to send basic HTTP authentication.
      header('WWW-Authenticate: Basic realm="Factory Stats"');
      
      // Authentication cancelled.
   }
   else
   {
      // Basic HTTP authentication failed.
   }
}

?>

<!DOCTYPE html>
<html>
   <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">
   
      <link rel="stylesheet" type="text/css" href="../css/flex.css"/>
      <link rel="stylesheet" type="text/css" href="display.css"/>
      
      <script src="../script/common.js"></script>
      <script src="display.js"></script>
      <script src="slideShow.js"></script>
      <script>
         window.onload = function() {
            display = new Display("slide-show-container");
            display.start();
        }
      </script>

   </head>
   <body id="body" class="flex-vertical" style="align-items: stretch;  justify-content: center;">
   
      <div id="slide-show-container"></div>
   
      <!-- Disconnected -->
      <div id="display-disconnected" class="instructions">
         A connection to the server could not be established.
         <br>
         <br>
         Check your Internet connection and contact your Factory Stats administrator if the problem persists.
      </div>
      
      <!-- Unauthorized -->
      <div id="display-unauthorized" class="instructions">
         This display could not be authorized with the supplied credentials.
         <br>
         <br>
         Contact your Factory Stats system administrator for login instructions.  
      </div>
      
      <!-- Unregistered -->
      <div id="display-unregistered" class="instructions">
         Add this display to a Factory Stats site by going to
         <br>
         <span class="url"><i>&lt;yourfactory&gt;</i>.factorystats.com</span> 
         <br>
         and selecting Display Config
         <br>
         <br>
         Display ID: <span class="uid"></span>  
      </div> 
      
      <!-- Redirecting -->
      <div id="display-redirecting" class="instructions">
         Redirecting to
         <br>
         <span class="url"><span class="subdomain"></span>.factorystats.com</span> 
         <br>
         <br>
         Display ID: <span class="uid"></span>  
      </div>   
      
      <!-- Unconfigured -->
      <div id="display-unconfigured" class="instructions">
         Create a presentation for this display by going to
         <br>
         <span class="url"><span class="subdomain"></span>.factorystats.com</span> 
         <br>
         and selecting Display Config
         <br>
         <br>
         Display ID: <span class="uid"></span>  
      </div>     
      
      <script>
         <?php echo DisplayState::getJavascript("DisplayState") ?>
      </script>      
   
   </body>
</html>