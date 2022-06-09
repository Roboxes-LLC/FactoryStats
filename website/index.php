<?php

require_once 'common/authentication.php';
require_once 'common/demo.php';
require_once 'common/header.php';
require_once 'common/language.php';
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
   else if ($authenticationResult == AuthenticationResult::INVALID_CUSTOMER)
   {
      $text = "The supplied user has not beed added to this customer site.  Contact your supervisor be added to the system.";
   }
   
   return ($text);
}

function parseLanguage()
{
   $languageType = Language::getLanguage();
   
   $selectedLanguage = getParams()->get("language");

   if ($selectedLanguage == "english")
   {
      $languageType = LanguageType::ENGLISH;
   }
   else if ($selectedLanguage == "spanish")
   {
      $languageType = LanguageType::SPANISH;
   }
   
   return ($languageType);
}

// *****************************************************************************

Time::init();

session_start();

$params = Params::parse();

Language::setLanguage(parseLanguage());

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

if (Demo::isDemoSite())
{
   Demo::generateData();
}

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Factory Stats</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<div class="flex-vertical" style="align-items: flex-start; height:100%;">

   <?php /*Header::render(false, false, false);*/ ?>
   
   <div class="flex-horizontal main splash" style="flex-wrap: wrap; align-items: center;">
   
   <form id="input-form" action="" method="POST">
      <input type="hidden" name="action" value="login">
      
      <div class="flex-vertical login-div" style="padding:10px;">
         <label><?php echo LANG("_USERNAME") ?></label>
         <input type="text" name="username" value="<?php echo getUsername(); ?>">
         <label><?php echo LANG("_PASSWORD") ?></label>
         <input type="password" name="password">
         <div class="flex-horizontal flex-h-center" style="margin-top: 20px; width:100%; color: red;">
            <?php echo getLoginFailureText($authenticationResult); ?>
         </div>         
         <button type="submit">Login</button>
         <div class="flex-horizontal" style="margin-top: 15px;"><div id="english-language-selector" class="language-selector <?php echo (Language::getLanguage() == LanguageType::ENGLISH) ? "selected" : ""; ?>">English</div>&nbsp;&nbsp;|&nbsp;&nbsp;<div id="spanish-language-selector" class="language-selector <?php echo (Language::getLanguage() == LanguageType::SPANISH) ? "selected" : ""; ?>">Espa&#xF1ol</div></div>
      </div>
   </form>
      
   </div>
   
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>

<script>
   document.getElementById("english-language-selector").addEventListener('click', function() {
      document.location = "index.php?language=english";    
   });
   document.getElementById("spanish-language-selector").addEventListener('click', function() {
      document.location = "index.php?language=spanish";    
   });
</script>

<?php
   if (Demo::isDemoSite() && (getAction() == ""))
   {
      Demo::generateData();
      
      Demo::setShowedInstructions(Permission::UNKNOWN, true);
      
      $versionQuery = versionQuery();
      
      echo
<<<HEREDOC
      <div id="demo-modal" class="modal">
         <div class="flex-vertical modal-content demo-modal-content">
            <div id="close" class="close">&times;</div>
            <p class="demo-modal-title">Factory Stats demo</p>         
            <p>Welcome!  Thank you for evaluating Factory Stats, a simple but effective solution for gathering and presenting real-time production data.</p>
            <p>To get started, simply press the login button.  Or login as "admin" (password: "admin") to unlock all options.</p>
         </div>
      </div>
   
      <script src="script/modal.js$versionQuery"></script>
      <script>showModal("demo-modal");</script>
HEREDOC;
   }
?>

</body>

</html>