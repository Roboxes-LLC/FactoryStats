<?php

require_once 'params.php';
require_once 'root.php';
require_once 'usa.php';

require_once 'database.php';  // TODO: Require order matters here, for some reason.

class CustomerInfo
{
   const UNKNOWN_CUSTOMER_ID = 0;
   
   public $customerId;
   public $name;
   public $subdomain;
   public $emailAddress;
   public $addressLine1;
   public $addressLine2;
   public $city;
   public $state;
   public $zipcode;
   public $phone;
   public $timeZone;
   
   public $disableAuthentication;
   
   public static function getSubdomain()
   {
      static $subdomain = null;
      
      $params = Params::parse();
      
      if ($subdomain == null)
      {
         // Allow spoofing of subdomain for testing.
         if ($params->keyExists("subdomain"))
         {
            $subdomain = $params["subdomain"];
         }
         // Otherwise, parse the domain from the HTTP request.
         else
         {
            $tokens = explode('.', $_SERVER['HTTP_HOST']);
            
            if (count($tokens) >= 3)
            {
               $subdomain = $tokens[0];
            }
            else
            {
               $subdomain = "flexscreentest";  // Default to test domain
            }
         }
      }
      
      return ($subdomain);
   }
   
   public static function getDatabase()
   {
      $subdomain = CustomerInfo::getSubdomain();
      
      return ($subdomain);
   }
   
   public static function getImagesFolder()
   {
      global $ROOT;
      
      $subdomain = CustomerInfo::getSubdomain();
      
      return ("$ROOT/$subdomain/images");
   }
   
   public static function getCssFolder()
   {
      global $ROOT;
      
      $subdomain = CustomerInfo::getSubdomain();
      
      return ("$ROOT/$subdomain/css");
   }
   
   public static function getSlidesFolder()
   {
      global $ROOT;
      
      $subdomain = CustomerInfo::getSubdomain();
      
      return ("$ROOT/$subdomain/slides");
   }
   
   public static function getCustomerInfo()
   {
      $customerInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = getCustomerFromSubdomain(CustomerInfo::getSubdomain());
         
         if ($result && ($row = $result->fetch_assoc()))  // Assume only one match.
         {
            $customerInfo = new CustomerInfo();
            $customerInfo->initializeFromDatabaseRow($row);
         }
      }
      
      return  ($customerInfo);
   }
   
   public static function load($customerId)
   {
      $customerInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getCustomer($customerId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $customerInfo = new CustomerInfo();
            
            $customerInfo->initializeFromDatabaseRow($row);
         }
      }
      
      return ($customerInfo);
   }
   
   static function getTimeZoneString($timeZoneId)
   {
      
   }
   
   private function initializeFromDatabaseRow($row)
   {
      if ($row)
      {         
         $this->customerId = intval($row['customerId']);
         $this->name = $row['name'];
         $this->subdomain = $row['subdomain'];
         $this->emailAddress = $row['emailAddress'];
         $this->addressLine1 = $row['addressLine1'];
         $this->addressLine2 = $row['addressLine2'];
         $this->city = $row['city'];
         $this->state = intval($row['state']);
         $this->zipcode = intval($row['zipcode']);
         $this->phone = $row['phone'];
         $this->timeZone = $row['timeZone'];
         
         $this->disableAuthentication = $row['disableAuthentication'];
      }
   }
}

/*
if (isset($_GET["customerId"]))
{
   $customerId = $_GET["customerId"];
   $customerInfo = CustomerInfo::load($customerId);
   
   if ($customerInfo)
   {
      echo "customerId: " .   $customerInfo->customerId .   "<br/>";
      echo "name: " .         $customerInfo->name .         "<br/>";
      echo "subdomain: " .    $customerInfo->subdomain .    "<br/>";
      echo "emailAddress: " . $customerInfo->emailAddress . "<br/>";
      echo "addressLine1: " . $customerInfo->addressLine1 . "<br/>";
      echo "addressLine2: " . $customerInfo->addressLine2 . "<br/>";
      echo "city: " .         $customerInfo->city .         "<br/>";
      echo "state: " .        States::getStateName($customerInfo->state) . "<br/>";
      echo "zipcode: " .      $customerInfo->zipcode .      "<br/>";
      echo "phone: " .        $customerInfo->phone .        "<br/>";
      echo "timeZone: " .     $customerInfo->timeZone .     "<br/>";
      echo "disableAuthentication: " . $customerInfo->disableAuthentication ? "true" : "false" . "<br>";
   }
   else
   {
      echo "No customer info found.";
   }
}
*/
 
?>