<?php
require_once 'params.php';

function isKioskMode()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params->getBool("kiosk"));
}
?>