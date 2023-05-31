<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/plugin/plugin.php';
require_once ROOT.'/plugin/pluginManager.php';

session_start();

unset($_SESSION["plugins"]);
unset($_SESSION["pluginRegistry"]);

// *****************************************************************************

class TestPlugin extends Plugin
{
   public function __construct()
   {
      $this->class = "TestPlugin";
      
      $this->config = (object)["enabled" => true];
      $this->status = (object)["count" => 0];
   }
   
   public function register()
   {
      PluginManager::register($this, PluginEvent::STATION_COUNT_CHANGED);
   }
   
   public function handleEvent($event, $payload)
   {
      if ($event == PluginEvent::STATION_COUNT_CHANGED)
      {         
         $this->status->count++;
         
         echo "TestPlugin::handleEvent: Got STATION_COUNT_CHANGED.  Count = {$this->status->count}<br>";
         
         Plugin::save($this);
      }
   }
}

// *****************************************************************************

class PluginTest
{   
   public static function run()
   {
      echo "Running PluginTest ...<br>";
      
      $test = new PluginTest();
      
      $test->testSave_Add();
      
      if (PluginTest::$newPluginId != Plugin::UNKNOWN_PLUGIN_ID)
      {
         PluginManager::initialize();
         
         $test->testHandleEvent();
         
         $test->testDelete();
      }
   }
   
   public function testSave_Add()
   {
      echo "Plugin::save(newPlugin)<br>";
      
      $plugin = new TestPlugin();
      
      Plugin::save($plugin);
      
      PluginTest::$newPluginId = $plugin->pluginId;
      
      $plugin = Plugin::load(PluginTest::$newPluginId);
      
      var_dump($plugin);      
   }
   
   public function testHandleEvent()
   {
      echo "PlugManager::handleEvent()<br>";
      
      for ($i = 0; $i < 5; $i++)
      {
         PluginManager::handleEvent(PluginEvent::STATION_COUNT_CHANGED, new stdClass());
      }
   }   
   
   /*
   public function testSave_Update()
   {
      echo "Defect::save(existingDefect)<br>";
      
      $defect = Defect::load(DefectTest::$newDefectId);
      
      $defect->dateTime = Time::incrementDay(Time::now());
      $defect->author = DefectTest::OTHER_AUTHOR_ID;
      $defect->category = DefectTest::OTHER_CATEGORY;
      $defect->severity = DefectTest::OTHER_SEVERITY;
      $defect->status = DefectTest::OTHER_STATUS;
      $defect->siteId = DefectTest::OTHER_SITE_ID;
      $defect->appPage = DefectTest::OTHER_APP_PAGE;
      $defect->title = DefectTest::OTHER_TITLE;
      $defect->description = DefectTest::OTHER_DESCRIPTION;
      
      Defect::save($defect);
      
      var_dump($defect);
   }
   */
   
   public function testDelete()
   {
      echo "Plugin::delete()<br>";
      
      Plugin::delete(PluginTest::$newPluginId);
   }
   
   private static $newPluginId = 0;
}

PluginTest::run();

?>