<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/customerInfo.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/version.php';

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getUid()
{
   $uid = "";
   
   $params = getParams();
   
   if ($params->keyExists("uid"))
   {
      $uid = $params->get("uid");
   }
   
   return ($uid);
}

?>

<!DOCTYPE html>

<html>

   <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      
      <link rel="stylesheet" type="text/css" href="../css/flex.css<?php echo versionQuery();?>"/>
      <link rel="stylesheet" type="text/css" href="../css/pages.css<?php echo versionQuery();?>"/>
   </head>
   
   <body class="flex-vertical" style="align-items: stretch;  justify-content: center;">
      <div class="instructions">
         Create a presentation for this display by going to
         <br>
         <span class="url"><?php echo CustomerInfo::getSubdomain() ?>.factorystats.com</span> 
         <br>
         and selecting Display Config
         <br>
         <br>
         Display ID: <span class="uid"><?php echo getUid(); ?></span>  
      </div>    
   </body>
   
</html>
