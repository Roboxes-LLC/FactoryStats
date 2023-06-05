<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/permissions.php';
require_once ROOT.'/common/roles.php';

class UserInfo
{
   const UNKNOWN_USER_ID = 0;
   
   // TODO: Rework.
   // A special "display" user authToken is hardcoded in SlideInfo::getUrl() and used when loading
   // Factory Stats pages into the slideshow.  This should be reworked so that this hack can be removed.
   const DISPLAY_USER_ID = 13;
   
   const DUMMY_PASSWORD = "DUMMYPASSWORD";
   
   public $userId;
   public $username;
   public $passwordHash;
   public $firstName;
   public $lastName;
   public $roles;
   public $permissions;       // bitfield
   public $email;
   public $authToken;
   
   public function __construct()
   {
      $this->userId = UserInfo::UNKNOWN_USER_ID;
      $this->username = null;
      $this->passwordHash = null;
      $this->firstName = null;
      $this->roles = Role::UNKNOWN;
      $this->permissions = Permission::NO_PERMISSIONS;
      $this->email = null;
      $this->authToken = null;
   }
   
   public static function load($userId)
   {
      $userInfo = null;
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getUser($userId);
         
         if ($result && ($row = $result[0]))
         {
            $userInfo = new UserInfo();
            
            $userInfo->initialize($row);
         }
      }
      
      return ($userInfo);
   }
   
   static public function loadByName($username)
   {
      $userInfo = null;
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getUserByName($username);
         
         if ($result && ($row = $result[0]))
         {
            $userInfo = new UserInfo();
            
            $userInfo->initialize($row);
         }
      }
      
      return ($userInfo);
   }
   
   static public function getUsersByRole($role)
   {
      $users = array();
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getUsersByRole($role);

         foreach ($result as $row)
         {
            $userInfo = new UserInfo();
            
            $userInfo->initialize($row);
            
            $users[] = $userInfo;
         }
      }
      
      return ($users);
   }
   
   static public function getUsersByRoles($roles)
   {
      $users = array();
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getUsersByRoles($roles);
         
         foreach ($result as $row)
         {
            $userInfo = new UserInfo();
            
            $userInfo->initialize($row);
            
            $users[] = $userInfo;
         }
      }
      
      return ($users);
   }
   
   public function getFullName()
   {
      return ($this->firstName . " " . $this->lastName);
   }
   
   public function getCustomers()
   {
      $customers = array();
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getCustomersForUser($this->userId);
         
         foreach ($result as $row)
         {
            $customers[] = intval($row["customerId"]);
         }
      }
      
      return ($customers);
   }
   
   private function initialize($row)
   {
      $this->userId = intval($row['userId']);
      $this->username = $row['username'];
      $this->passwordHash = $row['passwordHash'];
      $this->roles = intval($row['roles']);
      $this->permissions = intval($row['permissions']);
      $this->firstName = $row['firstName'];
      $this->lastName = $row['lastName'];
      $this->email = $row['email'];
      $this->authToken = $row['authToken'];
   }
}

/*
$userInfo = null;

if (isset($_GET["userId"]))
{
   $userId = $_GET["userId"];
   $userInfo = UserInfo::load($userId);
}
else if (isset($_GET["username"]))
{
   $username = $_GET["username"];
   $userInfo = UserInfo::loadByName($username);
}
    
if ($userInfo)
{
   echo "userId: " .           $userInfo->userId .           "<br/>";
   echo "username: " .         $userInfo->username .         "<br/>";
   echo "passwordHash: " .     $userInfo->passwordHash .     "<br/>";
   echo "roles: " .            $userInfo->roles .            "<br/>";
   echo "permissions: " .      $userInfo->permissions .      "<br/>";
   echo "firstName: " .        $userInfo->firstName .        "<br/>";
   echo "lastName: " .         $userInfo->lastName .         "<br/>";
   echo "email: " .            $userInfo->email .            "<br/>";
   echo "authToken: " .        $userInfo->authToken .        "<br/>";
   
   echo "fullName: " . $userInfo->getFullName() . "<br/>";
}
else
{
   echo "No user found.";
}
*/

/*
//
// Script for replacing passwords with password hashes.
//

$database = FactoryStatsDatabase::getInstance();

if ($database && $database->isConnected())
{
   $users = $database->getUsers();
   
   foreach ($users as $user)
   {
      $userId = $user["userId"];
      $password = $user["password"];
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
         
      $database->updatePassword($userId, $password);
      
      echo "User [$userId]: $password -> $passwordHash<br>";
   }
}
*/


?>