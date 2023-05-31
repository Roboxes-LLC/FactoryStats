<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/plugin/plugin.php';

// Include all Plugin derivatives.
require_once ROOT.'/plugin/precisionCounter.php';

class PluginManager
{      
   public static function initialize()
   {
      PluginManager::getPlugins();
   }
   
   public static function register($plugin, $event)
   {
      $registry = &PluginManager::getRegistry();
      
      if (!isset($registry[$event]))
      {
         $registry[$event] = array();
      }
      
      if (!in_array($plugin->pluginId, $registry[$event]))
      {
         $registry[$event][] = $plugin->pluginId;
      }      
   }
   
   public static function handleEvent($event, $payload)
   {
      if (!PluginManager::isInitialized())
      {
         PluginManager::initialize();
      }
      
      $registry = &PluginManager::getRegistry();
      
      if (isset($registry[$event]))
      {
         foreach ($registry[$event] as $pluginId)
         {
            $plugin = PluginManager::getPlugin($pluginId);
            
            if ($plugin)
            {
               $plugin->handleEvent($event, $payload);
            }
         }
      }
   }
   
   // **************************************************************************
   
   private static function isInitialized()
   {
      return (isset($_SESSION["pluginRegistry"]) && isset($_SESSION["plugins"]));
   }
   
   private static function &getRegistry()
   {
      if (!isset($_SESSION["pluginRegistry"]))
      {
         $_SESSION["pluginRegistry"] = array();
      }
      
      return ($_SESSION["pluginRegistry"]);         
   }
   
   private static function &getPlugins()
   {
      if (!isset($_SESSION["plugins"]))
      {
         $_SESSION["plugins"] = array();
         
         $result = FactoryStatsDatabase::getInstance()->getPlugins();
         
         foreach ($result as $row)
         {
            $plugin = PluginManager::createPlugin($row);
            
            if ($plugin)
            {
               $_SESSION["plugins"][$plugin->pluginId] = $plugin;
               
               $plugin->register();
            }
         }
      }
      
      return ($_SESSION["plugins"]);
   }
   
   private static function getPlugin($pluginId)
   {
      $plugin = null;
      
      $plugins = &PluginManager::getPlugins();
      
      if (isset($plugins[$pluginId]))
      {
         $plugin = $plugins[$pluginId];
      }
      
      return ($plugin);
   }
   
   private static function createPlugin($row)
   {
      $class = $row["class"];
      
      if (class_exists($class))
      {
         $plugin = new $class;
         
         if ($plugin)
         {
            $plugin->initialize($row);
         }
      }
      
      return ($plugin);
   }
}
