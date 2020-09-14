<?php
abstract class ButtonPress
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const SINGLE_CLICK = ButtonPress::FIRST;
   const DOUBLE_CLICK = 2;
   const HOLD = 3;
   const LAST = 4;
   const COUNT = ButtonPress::LAST - ButtonPress::FIRST;
   
   public static $values = array(ButtonPress::SINGLE_CLICK, ButtonPress::DOUBLE_CLICK, ButtonPress::HOLD);
   
   public static function getLabel($buttonPress)
   {
      $labels = array("---", "SINGLE_CLICK", "DOUBLE_CLICK", "HOLD");
      
      return ($labels[$buttonPress]);
   }
}

abstract class ButtonAction
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const INCREMENT_COUNT = ButtonAction::FIRST;
   const DECREMENT_COUNT = 2;
   const PAUSE_STATION = 3;
   const LAST = 4;
   const COUNT = ButtonAction::LAST - ButtonAction::FIRST;
   
   public static $values = array(ButtonAction::INCREMENT_COUNT, ButtonAction::DECREMENT_COUNT, ButtonAction::PAUSE_STATION);
   
   public static function getLabel($buttonAction)
   {
      $labels = array("---", "Increment count", "Decrement count", "Pause station");
      
      return ($labels[$buttonAction]);
   }
}

abstract class ButtonStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const UNCONFIGURED = ButtonStatus::FIRST;
   const DISABLED = 2;
   const READY = 3;
   const LAST = 4;
   const COUNT = ButtonStatus::LAST - ButtonStatus::FIRST;
   
   public static $values = array(ButtonStatus::UNCONFIGURED, ButtonStatus::DISABLED, ButtonStatus::READY);
   
   public static function getLabel($buttonStatus)
   {
      $labels = array("---", "Unconfigured", "Disabled", "Ready");
      
      return ($labels[$buttonStatus]);
   }
   
   public static function getClass($buttonStatus)
   {
      $labels = array("", "button-unconfigured", "button-disabled", "button-ready");
      
      return ($labels[$buttonStatus]);
   }
}