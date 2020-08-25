<?php

require_once 'common/authentication.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/version.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getAction()
{
   $params = getParams();
   
   return ($params->get("action"));
}

function getUsername()
{
   $params = getParams();
   
   return ($params->get("username"));
}

function getPassword()
{
   $params = getParams();
   
   return ($params->get("password"));
}

function redirect($url)
{
   unset($_SESSION["redirect"]);
   
   header("Location: $url");
   exit;
}

function getLoginFailureText($authenticationResult)
{
   $text = "";
   
   if ($authenticationResult == AuthenticationResult::INVALID_USERNAME)
   {
      $text = "A user by that name could not be found.  Contact your supervisor to be added to the system.";
   }
   else if ($authenticationResult == AuthenticationResult::INVALID_PASSWORD)
   {
      $text = "The supplied password is incorrect.  Contact your supervisor if you forgot or need to reset your password.";
   }
   
   return ($text);
}

// *****************************************************************************

Time::init();

session_start();

$params = Params::parse();

$authenticationResult = null;

if (getAction() == "logout")
{
   Authentication::deauthenticate();
   
   session_unset();
}
else if (getAction() == "login")
{
   $authenticationResult = Authentication::authenticate();
}

if (Authentication::isAuthenticated())
{
   if ($params->keyExists("redirect"))
   {
      redirect($params->keyExists("redirect"));
   }
   else
   {
      redirect("workstationSummary.php");
   }
}

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Flexscreen Counter</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<div class="flex-vertical" style="align-items: flex-start; height:100%;">

   <?php Header::render(false);?>
   
   <div class="flex-horizontal main splash" style="flex-wrap: wrap; align-items: center;">
   
   <form id="input-form" action="" method="POST">
      <input type="hidden" name="action" value="login">
      
      <div class="flex-vertical login-div" style="padding:10px;">
         <label>Username</label>
         <input type="text" name="username" value="<?php echo getUsername(); ?>">
         <label>Password</label>
         <input type="password" name="password">
         <div class="flex-horizontal flex-h-center" style="margin-top: 20px; width:100%; color: red;">
            <?php echo getLoginFailureText($authenticationResult); ?>
         </div>         
         <button type="submit">Login</button>
      </div>
   </form>
      
   </div>
   
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script>
</script>

</body>

</html>