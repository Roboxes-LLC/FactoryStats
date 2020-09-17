<?php

require_once 'root.php';

class Presentation
{
   const UNKNOWN_PRESENTATION_ID = 0;
   
   public $presentationId;   
   public $name;
   
   public function __construct()
   {
      $this->presentationId = Presentation::UNKNOWN_PRESENTATION_ID;
      $this->name = "";
   }
   
   public static function load($presentationId)
   {
      $presentation = new Presentation();
      
      // TODO
      
      return ($presentation);
   }

   public static function getDefaultPresentation()
   {
      $presentation = new Presentation();
      
      return ($presentation);
   }
   
   public function getTabRotateConfig()
   {
      global $ROOT;
      
      $tabRotateConfig = new stdClass();
      
      $tabRotateConfig->settingsReloadIntervalMinutes = 1;
      $tabRotateConfig->fullscreen = false;
      $tabRotateConfig->autoStart = true;
      $tabRotateConfig->lazyLoadTabs = true;
      $tabRotateConfig->websites = array();
      
      $website = new stdClass();
      $website->url = "http://" . $_SERVER['HTTP_HOST'] . $ROOT . "/workstationSummary.php?kiosk=true&username=operator&password=1234";
      $website->duration = 3;
      $website->tabReloadIntervalSeconds = 120;
      
      $tabRotateConfig->websites[] = $website;
      
      return ($tabRotateConfig);
   }
}

?>