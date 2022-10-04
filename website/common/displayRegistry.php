<?php

require_once 'database.php';

class DisplayRegistry
{
   const UNKNOWN_SUBDOMAIN = "";
   
   static function isRegistered($uid)
   {
      $isRegistered = false;
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $isRegistered = $database->isDisplayRegistered($uid);   
      }
      
      return ($isRegistered);
   }
   
   static function register($uid)
   {      
      $result = null;
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->registerDisplay($uid);
      }
      
      return ($result);
   }
   
   static function unregister($uid)
   {
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && 
          $database->isConnected() && 
          $database->isDisplayRegistered($uid))
      {
         $database->uregisterDisplay($uid);
      }
   }
   
   static function associateWithSubdomain($uid, $subdomain)
   {
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && 
          $database->isConnected() && 
          $database->isDisplayRegistered($uid))
      {
         $database->associateDisplayWithSubdomain($uid, $subdomain);
      }
   }
   
   static function getAssociatedSubdomain($uid)
   {
      $domain = DisplayRegistry::UNKNOWN_SUBDOMAIN;
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $domain = $database->getAssociatedSubdomainForDisplay($uid);
      }
      
      return ($domain);
   }
   
   static function getAssociatedCustomerId($uid)
   {
      $customerId = CustomerInfo::UNKNOWN_CUSTOMER_ID;
      
      $subdomain = DisplayRegistry::getAssociatedSubdomain($uid);
      
      if ($subdomain)
      {
         $database = FactoryStatsGlobalDatabase::getInstance();
         
         if ($database && $database->isConnected())
         {
            $result = $database->getCustomerFromSubdomain($subdomain);
            
            if ($result && ($row = $result[0]))
            {
               $customerId = intval($row["customerId"]);
            }
         }
      }
      
      return ($customerId);
   }
}

?>