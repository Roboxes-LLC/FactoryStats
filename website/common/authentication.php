<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/userInfo.php';

abstract class AuthenticationResult
{
   const AUTHENTICATED = 0;
   const INVALID_USERNAME = 1;
   const INVALID_PASSWORD = 2;
   const INVALID_AUTH_TOKEN = 3;
   const INVALID_CUSTOMER = 4;
}

class Authentication
{
   const AUTH_TOKEN_LENGTH = 32;
   
   static public function isAuthenticated()
   {
      return (isset($_SESSION["authenticated"]) && 
              ($_SESSION["authenticated"] == true) &&
              isset($_SESSION["customerId"]) &&
              ($_SESSION["customerId"] != CustomerInfo::UNKNOWN_CUSTOMER_ID));
   }
   
   static public function getAuthenticatedUser()
   {
      $authenticatedUser = null;
      
      if (Authentication::isAuthenticated())
      {
         $authenticatedUser = UserInfo::load(intval($_SESSION["authenticatedUserId"]));
      }
      
      return ($authenticatedUser);
   }
   
   static public function getAuthenticatedCustomer()
   {
      $authenticatedCustomer = null;
      
      if (Authentication::isAuthenticated())
      {
         $authenticatedCustomer = CustomerInfo::load($_SESSION['customerId']);
      }
      
      return ($authenticatedCustomer);
   }
   
   static public function getPermissions()
   {
      $permissions = 0;
      
      if (isset($_SESSION["permissions"]))
      {
         $permissions= $_SESSION["permissions"];
      }
      
      return ($permissions);
   }
   
   static public function authenticate()
   {
      $result = AuthenticationResult::INVALID_USERNAME;
      
      $params = Params::parse();
      
      // Basic HTTP authentication
      if (isset($_SERVER['PHP_AUTH_USER']))
      {
         $result = Authentication::authenticateUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
      }
      // User/password in GET/PUT params.
      else if ($params->keyExists("username") && $params->keyExists("password"))
      {
         $result = Authentication::authenticateUser($params->get("username"), $params->get("password"));
      }
      // Auth token in GET/PUT params.
      else if ($params->keyExists("authToken"))
      {
         $result = Authentication::authenticateToken($params->get("authToken"));
      }
      
      // Authenticate user against customer.
      if ($result == AuthenticationResult::AUTHENTICATED)
      {
         $customerId = CustomerInfo::getCustomerId($_SESSION['authenticatedUserId']);
         
         if (!Authentication::setCustomer($customerId))
         {
            Authentication::deauthenticate();
            $result = AuthenticationResult::INVALID_CUSTOMER;
         }
      }
      
      return ($result);
   }
   
   static public function deauthenticate()
   {
      $_SESSION['authenticated'] = false;
      unset($_SESSION['authenticatedUserId']);
      unset($_SESSION['permissions']);
      unset($_SESSION['customers']);
      unset($_SESSION['customerId']);
      unset($_SESSION['database']);
   }
   
   static public function checkPermissions($permissionId)
   {
      $permission = Permission::getPermission($permissionId)->bits;
      $userPermissions = Authentication::getPermissions();
      
      return (($userPermissions & $permission) > 0);
   }
   
   static public function setCustomer($customerId)
   {
      $success = false;
      
      if (Authentication::isAuthenticated() &&
          CustomerInfo::validateUserForCustomer($_SESSION['authenticatedUserId'], $customerId))
      {
         $_SESSION["customerId"] = $customerId;
         $_SESSION["database"] = CustomerInfo::getDatabase();
         
         $success = true;
      }
      
      return ($success);
   }
   
   static private function authenticateUser($username, $password)
   {
      $result = AuthenticationResult::INVALID_USERNAME;
      
      $user = UserInfo::loadByName($username);
      
      if ($user == null)
      {
         $result = AuthenticationResult::INVALID_USERNAME;
      }
      else if (!password_verify($password, $user->passwordHash))
      {
         $result = AuthenticationResult::INVALID_PASSWORD;
      }
      else
      {
         $result = AuthenticationResult::AUTHENTICATED;
         
         // Record authentication status and user name.
         $_SESSION['authenticated'] = true;
         $_SESSION['authenticatedUserId'] = $user->userId;
         $_SESSION["permissions"] = $user->permissions;
      }
      
      return ($result);
   }
   
   static private function authenticateToken($authToken)
   {
      $result = AuthenticationResult::INVALID_AUTH_TOKEN;
      
      $users = UserInfo::getUsersByRole(Role::UNKNOWN);
      
      foreach ($users as $user)
      {
         if (($user->authToken != "") &&
               ($authToken == $user->authToken))
         {
            $result = AuthenticationResult::AUTHENTICATED;
            
            // Record authentication status.
            $_SESSION['authenticated'] = true;
            $_SESSION['authenticatedUserId'] = $user->userId;
            $_SESSION["permissions"] = $user->permissions;
         }
      }
      
      return ($result);
   }
}
?>