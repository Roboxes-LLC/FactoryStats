<?php

require_once 'root.php';
require_once 'slideInfo.php';

class PresentationInfo
{
   const UNKNOWN_PRESENTATION_ID = 0;
   
   public $presentationId;   
   public $name;
   public $slides;
   
   public function __construct()
   {
      $this->presentationId = PresentationInfo::UNKNOWN_PRESENTATION_ID;
      $this->name = "";
      $this->slides = array();
   }
   
   public static function load($presentationId)
   {
      $presentationInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getPresentation($presentationId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $presentationInfo = new PresentationInfo();
            
            $presentationInfo->presentationId = intval($row['presentationId']);
            $presentationInfo->name = $row['name'];
            
            $result = $database->getSlidesForPresentation($presentationId);
            
            while ($result && ($row = $result->fetch_assoc()))
            {
               $slideInfo = new SlideInfo;
               $slideInfo->initializeFromDatabaseRow($row);
               
               $presentationInfo->slides[] = $slideInfo;
            }            
         }
      }
      
      return ($presentationInfo);
   }

   public static function getDefaultPresentation()
   {
      $presentation = new PresentationInfo();
      
      $slideInfo = new SlideInfo();
      $slideInfo->slideType = SlideType::WORKSTATION_SUMMARY_PAGE;
      $slideInfo->duration = 0;
      $slideInfo->shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
      
      $presentation->slides[] = $slideInfo;
      
      return ($presentation);
   }
   
   public function getTabRotateConfig()
   {
      //global $ROOT;
      
      $tabRotateConfig = new stdClass();
      
      $tabRotateConfig->settingsReloadIntervalMinutes = 1;
      $tabRotateConfig->fullscreen = false;
      $tabRotateConfig->autoStart = true;
      $tabRotateConfig->lazyLoadTabs = true;
      $tabRotateConfig->websites = array();
      
      foreach ($this->slides as $slideInfo)
      {
         $website = new stdClass();
         
         $website->url = $slideInfo->getUrl();
         $website->duration = $slideInfo->duration;
         $website->tabReloadIntervalSeconds = 120;
         
         $tabRotateConfig->websites[] = $website;
      }

      /*
      $website = new stdClass();
      $website->url = "http://" . $_SERVER['HTTP_HOST'] . $ROOT . "/workstationSummary.php?kiosk=true&username=operator&password=1234";
      $website->duration = 3;
      $website->tabReloadIntervalSeconds = 120;
      
      $tabRotateConfig->websites[] = $website;
      */
      
      return ($tabRotateConfig);
   }
}

/*
if (isset($_GET["presentationId"]))
{
   $presentationId = $_GET["presentationId"];
   $presentationInfo = PresentationInfo::load($presentationId);
 
   if ($presentationInfo)
   {
      echo "presentationId: " . $presentationInfo->presentationId . "<br/>";
      echo "name: " .           $presentationInfo->name .           "<br/>";
      echo "slides: " .         count($presentationInfo->slides) .  "<br/>";
   }
}
*/

?>