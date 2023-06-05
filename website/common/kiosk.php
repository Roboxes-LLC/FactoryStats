<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/params.php';

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