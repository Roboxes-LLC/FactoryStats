<?php

abstract class DisplaySize
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const AUTO = DisplaySize::FIRST;
   const SMALL = 2;
   const MEDIUM = 3;
   const LARGE = 4;
   const LAST = 5;
   const COUNT = DisplaySize::LAST - DisplaySize::FIRST;

   public static $values = array(DisplaySize::AUTO, DisplaySize::SMALL, DisplaySize::MEDIUM, DisplaySize::LARGE);

   public static function getLabel($displaySize)
   {
      $labels = array("", "Auto", "Small", "Medium", "Large");
      
      return ($labels[$displaySize]);
   }
   
   public static function getOptions($selectedDisplaySize)
   {
      $html = "<option style=\"display:none\">";
      
      foreach (DisplaySize::$values as $displaySize)
      {
         $selected = ($displaySize == $selectedDisplaySize) ? "selected" : "";
         $label = DisplaySize::getLabel($displaySize);
         
         $html .= "<option value=\"$displaySize\" $selected>$label</option>";
      }
      
      return ($html);
   }
   
   public static function getClass($displaySize)
   {
      $classes = array("", "display-auto", "display-small", "display-medium", "display-large");
      
      return ($classes[$displaySize]);
   }
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "AUTO", "SMALL", "MEDIUM", "LARGE");
      
      $html = "var $enumName = {";
      
      for ($displaySize = DisplaySize::UNKNOWN; $displaySize < DisplaySize::LAST; $displaySize++)
      {
         $html .= "{$varNames[$displaySize]}: $displaySize";
         $html .= ($displaySize < (DisplaySize::LAST - 1) ? ", " : "");
      }
      
      $html .= "};\n";
      
      return ($html);
   } 
}

abstract class ChartSize
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const SMALL = ChartSize::FIRST;
   const MEDIUM = 2;
   const LARGE = 3;
   const LAST = 4;
   const COUNT = ChartSize::LAST - ChartSize::FIRST;
   
   public static $values = array(ChartSize::SMALL, ChartSize::MEDIUM, ChartSize::LARGE);
   
   public static function getJavascript($enumName)
   {
      // Note: Keep synced with enum.
      $varNames = array("UNKNOWN", "SMALL", "MEDIUM", "LARGE");
      
      $html = "var $enumName = {";
      
      for ($chartSize = ChartSize::UNKNOWN; $chartSize < ChartSize::LAST; $chartSize++)
      {
         $html .= "{$varNames[$chartSize]}: $chartSize";
         $html .= ($chartSize < (ChartSize::LAST - 1) ? ", " : "");
      }
      
      $html .= "};\n";
      
      return ($html);
   }
}

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