<?php

abstract class DisplayStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const UNCONFIGURED = DisplayStatus::FIRST;
   const DISABLED = 2;
   const READY = 3;
   const LAST = 4;
   const COUNT = DisplayStatus::LAST - DisplayStatus::FIRST;
   
   public static $values = array(DisplayStatus::UNCONFIGURED, DisplayStatus::DISABLED, DisplayStatus::READY);
   
   public static function getLabel($displayStatus)
   {
      $labels = array("---", "Unconfigured", "Disabled", "Ready");
      
      return ($labels[$displayStatus]);
   }
   
   public static function getClass($displayStatus)
   {
      $labels = array("", "display-unconfigured", "display-disabled", "display-ready");
      
      return ($labels[$displayStatus]);
   }
}

abstract class DisplayState
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const DISCONNECTED = DisplayState::FIRST;
   const UNAUTHORIZED = 2;
   const UNREGISTERED = 3;
   const REDIRECTING = 4;
   const UNCONFIGURED = 5;
   const DISABLED = 6;
   const READY = 7;
   const LAST = 8;
   const COUNT = DisplayState::LAST - DisplayState::FIRST;
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "DISCONNECTED", "UNAUTHORIZED", "UNREGISTERED", "REDIRECTING", "UNCONFIGURED", "DISABLED", "READY");
      
      $html = "var $enumName = {";
      
      for ($displayState = DisplayState::UNKNOWN; $displayState < DisplayState::LAST; $displayState++)
      {
         $html .= "{$varNames[$displayState]}: $displayState";
         $html .= ($displayState < (DisplayState::LAST - 1) ? ", " : "");
      }
      
      $html .= "};\n";
      
      return ($html);
   } 
}

?>