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
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getPresentation($presentationId);
         
         if ($result && ($row = $result[0]))
         {
            $presentationInfo = new PresentationInfo();
            
            $presentationInfo->presentationId = intval($row['presentationId']);
            $presentationInfo->name = $row['name'];
            
            $result = $database->getSlidesForPresentation($presentationId);
            
            foreach ($result as $row)
            {
               $slideInfo = new SlideInfo;
               $slideInfo->initializeFromDatabaseRow($row);
               
               $presentationInfo->slides[] = $slideInfo;
            }            
         }
      }
      
      return ($presentationInfo);
   }

   public static function getDefaultPresentation($uid)
   {
      global $HTTP;
      
      $presentation = new PresentationInfo();
      
      $subdomain = CustomerInfo::getSubdomain();
      
      $slideInfo = new SlideInfo();
      $slideInfo->slideType = SlideType::URL;
      $slideInfo->duration = 0;
      $slideInfo->url = $HTTP . "://$subdomain.factorystats.com/pages/default.php?uid=$uid";
      $slideInfo->enabled = true;
      
      $presentation->slides[] = $slideInfo;
      
      return ($presentation);
   }
   
   public static function getUnauthorizedPresentation()
   {
      $presentation = new PresentationInfo();
      
      $slideInfo = new SlideInfo();
      $slideInfo->slideType = SlideType::URL;
      $slideInfo->duration = 0;
      $slideInfo->url = $HTTP . "://{$_SERVER['HTTP_HOST']}/pages/unauthorized.php";
      $slideInfo->enabled = true;
      
      $presentation->slides[] = $slideInfo;
      
      return ($presentation);
   }
   
   public static function getUnregisteredPresentation($uid)
   {
      global $DISPLAY_REGISTRY;
      global $HTTP;

      $presentation = new PresentationInfo();

      $slideInfo = new SlideInfo();
      $slideInfo->slideType = SlideType::URL;
      $slideInfo->duration = 0;
      $slideInfo->url = $HTTP . "://{$_SERVER['HTTP_HOST']}/pages/unregistered.php?uid=$uid";
      $slideInfo->enabled = true;
      
      $presentation->slides[] = $slideInfo;
      
      return ($presentation);
   }
   
   public static function getRedirectingPresentation($uid)
   {
      global $DISPLAY_REGISTRY;
      global $HTTP;
      
      $presentation = new PresentationInfo();
      
      $subdomain = DisplayRegistry::getAssociatedSubdomain($uid);
      if (!$subdomain || ($subdomain == ""))
      {
         $subdomain = $DISPLAY_REGISTRY;
      }
      
      $slideInfo = new SlideInfo();
      $slideInfo->slideType = SlideType::URL;
      $slideInfo->duration = 0;
      $slideInfo->url = $HTTP . "://{$_SERVER['HTTP_HOST']}/pages/redirecting.php?uid=$uid";
      $slideInfo->enabled = true;
      
      $presentation->slides[] = $slideInfo;
      
      return ($presentation);
   }
   
   public static function getUnconfiguredPresentation($uid)
   {
      global $DISPLAY_REGISTRY;
      global $HTTP;
      
      $presentation = new PresentationInfo();
      
      $subdomain = DisplayRegistry::getAssociatedSubdomain($uid);
      if (!$subdomain || ($subdomain == ""))
      {
         $subdomain = $DISPLAY_REGISTRY;
      }
      
      $slideInfo = new SlideInfo();
      $slideInfo->slideType = SlideType::URL;
      $slideInfo->duration = 0;
      $slideInfo->url = $HTTP . "://{$_SERVER['HTTP_HOST']}/pages/unconfigured.php?uid=$uid";
      $slideInfo->enabled = true;
      
      $presentation->slides[] = $slideInfo;
      
      return ($presentation);
   }
   
   public function getTabRotateConfig($scaling = DisplaySize::AUTO)
   {
      $tabRotateConfig = new stdClass();
      
      $tabRotateConfig->settingsReloadIntervalMinutes = 1;
      $tabRotateConfig->fullscreen = false;
      $tabRotateConfig->autoStart = true;
      $tabRotateConfig->lazyLoadTabs = true;
      $tabRotateConfig->websites = array();
      
      foreach ($this->slides as $slideInfo)
      {
         $website = new stdClass();
         
         if ($slideInfo->enabled)
         {
            $website->url = $slideInfo->getUrl($scaling);
            $website->duration = $slideInfo->duration;
            $website->tabReloadIntervalSeconds = $slideInfo->reloadInterval;
            
            $tabRotateConfig->websites[] = $website;
         }
      }
      
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