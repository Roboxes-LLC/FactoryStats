<?php

require_once '../common/params.php';
require_once '../common/version.php';

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
   
   <body class="flex-horizontal" style="align-items: flex-end;  justify-content: flex-end;">
         <div class="uid" style="margin: 20px; font-size: 75px;"><?php echo getUid(); ?></div>  
   </body>
   
</html>
