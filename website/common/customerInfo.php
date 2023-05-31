<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/usa.php';

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
   
   public static function getCustomerId($userId = UserInfo::UNKNOWN_USER_ID)
   {
      $customerId = CustomerInfo::UNKNOWN_CUSTOMER_ID;
      
      // First, check the $_SESSION variable.
      if ((isset($_SESSION["customerId"])) &&
          (intval($_SESSION["customerId"]) != CustomerInfo::UNKNOWN_CUSTOMER_ID))
      {
         $customerId = intval($_SESSION["customerId"]);
      }
      else
      {
         // Examine the URL for a customer subdomain.
         $customerId = CustomerInfo::getCustomerIdFromUrl();
         
         // Examine the params for a customer id or subdomain.
         if ($customerId == CustomerInfo::UNKNOWN_CUSTOMER_ID)
         {
            $customerId = CustomerInfo::getCustomerIdFromParams();
         }
         
         // Otherwise, go with the user's first associated customer.
         if (($customerId == CustomerInfo::UNKNOWN_CUSTOMER_ID) &&
             ($userId != UserInfo::UNKNOWN_USER_ID))
         {
            $customerId = CustomerInfo::getCustomerIdFromUser($userId);
         }

         // Store any valid customer id.
         if ($customerId != CustomerInfo::UNKNOWN_CUSTOMER_ID)
         {
            $_SESSION["customerId"] = $customerId;
         }
      }
      
      return ($customerId);
   }
   
   public static function isCustomerSpecifiedInUrl()
   {
      return (CustomerInfo::getSubdomainFromUrl() != "");
   }
   
   public static function validateUserForCustomer($userId, $customerId)
   {
      $isValid = false;
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {  
         $userInfo = UserInfo::load($userId);
         
         if ($userInfo)
         {
            $customerIds = $userInfo->getCustomers();
            
            $isValid = in_array($customerId, $customerIds);
         }
      }
      
      return ($isValid);
   }
   
   public static function getSubdomain()
   {
      static $subdomain = null;
      
      if (!$subdomain)
      {         
         $customerId = isset($_SESSION["customerId"]) ? 
                          intval($_SESSION["customerId"]) : 
                          CustomerInfo::UNKNOWN_CUSTOMER_ID;
         
         if ($customerId != CustomerInfo::UNKNOWN_CUSTOMER_ID)
         {
            $customerInfo = CustomerInfo::load($customerId);
            
            if ($customerInfo)
            {
               $subdomain = $customerInfo->subdomain;
            }
         }
         else
         {
            $subdomain = CustomerInfo::getSubdomainFromUrl();
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
      $subdomain = CustomerInfo::getSubdomain();
      
      return ("/$subdomain/images");
   }
   
   public static function getCssFolder()
   {
      $subdomain = CustomerInfo::getSubdomain();
      
      return ("/$subdomain/css");
   }
   
   public static function getSlideImagesFolder()
   {
      $subdomain = CustomerInfo::getSubdomain();
      
      return ("/$subdomain/uploads/images");
   }
   
   public static function getSlideImagesUploadFolder()
   {
      $slideImagesFolder = CustomerInfo::getSlideImagesFolder();
      
      return ("{$_SERVER['DOCUMENT_ROOT']}/$slideImagesFolder");
   }
      
   public static function load($customerId)
   {
      $customerInfo = null;
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getCustomer($customerId);
         
         if ($result && ($row = $result[0]))
         {
            $customerInfo = new CustomerInfo();
            
            $customerInfo->initializeFromDatabaseRow($row);
         }
      }
      
      return ($customerInfo);
   }
   
   public static function getCustomerOptions($customerIds, $selectedCustomerId)
   {
      $html = "";
      
      foreach ($customerIds as $customerId)
      {
         $customerInfo = CustomerInfo::load($customerId);
         
         $selected = ($customerId == $selectedCustomerId) ? "selected" : "";
            
         $html .= "<option value=\"$customerInfo->customerId\" $selected>$customerInfo->name</option>";
      }
      
      return ($html);
   }
   
   public static function getTimeZone()
   {
      return (($customerInfo = CustomerInfo::load(CustomerInfo::getCustomerId())) ? 
                 $customerInfo->timeZone : 
                 Time::DEFAULT_TIME_ZONE);
   }
   
   // Hack requested by customer on 5/2/2023 in order to hide embarrassing stats.
   public static function isDemoMode()
   {
      return (($customerInfo = CustomerInfo::load(CustomerInfo::getCustomerId())) ?
                 ($customerInfo->addressLine2 == "DEMO") :
                 false);
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
   
   private static function getSubdomainFromUrl()
   {
      global $SUBDOMAIN;
      
      $subdomain = null;
      
      // Allow spoofing of subdomain using $SUBDOMAIN variable defined in root.php.
      if (isset($SUBDOMAIN))
      {
         $subdomain = $SUBDOMAIN;
      }
      else if (isset($_SERVER['HTTP_HOST']))
      {
         $tokens = explode('.', $_SERVER['HTTP_HOST']);
         
         // Look for the domain in the URL.
         // I.e. <subdomain>.factorystats.com
         if ((count($tokens) == 3) &&
             (strtolower($tokens[0]) != "www"))
         {
            $subdomain = $tokens[0];
         }
      }
      
      return ($subdomain);      
   }
   
   private static function getCustomerIdFromUrl()
   {
      $customerId = CustomerInfo::UNKNOWN_CUSTOMER_ID;
      
      $subdomain = CustomerInfo::getSubdomainFromUrl();
      
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
   
   private static function getCustomerIdFromUser($userId)
   {
      $customerId = CustomerInfo::UNKNOWN_CUSTOMER_ID;
      
      if ($userId != UserInfo::UNKNOWN_USER_ID)
      {
         $database = FactoryStatsGlobalDatabase::getInstance();
         
         if ($database && $database->isConnected())
         {
            $result = $database->getCustomersForUser($userId);
            
            if ($result && ($row = $result[0]))
            {
               $customerId = intval($row["customerId"]);
            }
         }
      }      
      
      return ($customerId);
   }
   
   private static function getCustomerIdFromParams()
   {      
      $customerId = CustomerInfo::UNKNOWN_CUSTOMER_ID;
      
      $params = Params::parse();
      
      if ($params->keyExists("customerId"))
      {
         if (CustomerInfo::load($params->getInt("customerId")))
         {
            $customerId = $params->getInt("customerId");
         }
      }
      else if ($params->keyExists("subdomain"))
      {
         $subdomain = $params->get("subdomain");
         
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