<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';

class Plugin
{
   const UNKNOWN_PLUGIN_ID = 0;
   
   public $pluginId;
   public $class;
   public $config;
   public $status;
   
   public function __construct()
   {
      $this->plugin = Plugin::UNKNOWN_PLUGIN_ID;
      $this->class = null;
      $this->config = null;
      $this->status = null;
   }
   
   // **************************************************************************
   // Component interface
   
   public static function load($pluginId)
   {
      // Not supported.  Use PluginFactory to create plugins.
   }
   
   public static function save($plugin)
   {
      $success = false;
      
      if ($plugin->pluginId == Plugin::UNKNOWN_PLUGIN_ID)
      {
         $success = FactoryStatsDatabase::getInstance()->addPlugin($plugin);
         
         $plugin->pluginId = intval(FactoryStatsDatabase::getInstance()->lastInsertId());
      }
      else
      {
         $success = FactoryStatsDatabase::getInstance()->updatePlugin($plugin);
      }
      
      return ($success);
   }
   
   public static function delete($pluginId)
   {
      return (FactoryStatsDatabase::getInstance()->deletePlugin($pluginId));
   }
   
   public function initialize($row)
   {
      $this->pluginId = intval($row["pluginId"]);
      $this->class = $row["class"];
      
      // $config and $status should be initialized in derived classes.
   }
   
   // **************************************************************************
   
   public function register()
   {
      // Implement in derrived classes.
   }  
   
   public function handleEvent($event, $payload)
   {
      // Implement in derrived classes.
   }
}

?>