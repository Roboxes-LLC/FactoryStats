<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/userInfo.php';
require_once ROOT.'/common/version.php';

session_start();

// Set this variable if an attempt is made to create a duplicate user.
$duplicateUsername = null;

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::USER_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

Time::init(CustomerInfo::getTimeZone());

function renderTable()
{
   echo 
<<<HEREDOC
   <table>
      <tr>
         <th>Name</th>
         <th>Username</th>
         <th>Role</th>
         <th>Email</th>
         <th>Sites</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FactoryStatsGlobalDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getUsersForCustomer(Authentication::getAuthenticatedCustomer()->customerId);
      
      foreach ($result as $row)
      {
         $userInfo = UserInfo::load(intval($row["userId"]));
         
         // TODO: Rework.
         // A special "display" user authToken is hardcoded in SlideInfo::getUrl() and used when loading
         // Factory Stats pages into the slideshow.  Until this is reworked, hide this special user
         // from viewing/editing.         
         if ($userInfo->userId != UserInfo::DISPLAY_USER_ID)
         {
            $name = $userInfo->getFullName();
            
            $roleName = "Unassigned";
            $role = Role::getRole($userInfo->roles);
            if ($role)
            {
               $roleName = $role->roleName;
            }
            
            $siteCount = count($userInfo->getCustomers());
            
            echo 
<<<HEREDOC
            <tr>
               <td>$name</td>
               <td>$userInfo->username</td>
               <td>$roleName</td>
               <td>$userInfo->email</td>
               <td>$siteCount</td>
               <td><button class="config-button" onclick="setUserInfo($userInfo->userId, '$userInfo->firstName', '$userInfo->lastName', '$userInfo->username', '$userInfo->roles', '$userInfo->email', '$userInfo->authToken'); showModal('config-modal');">Configure</button></div></td>
               <td><button class="config-button" onclick="setUserId($userInfo->userId); showModal('confirm-delete-modal');">Delete</button></div></td>
            </tr>
HEREDOC;
         }
      }
   }
   
   echo "</table>";
}

function getRoleOptions()
{
   $options = "";

   $roles = Role::getRoles();
   
   $options .= "<option style=\"display:none\">";
   
   foreach ($roles as $role)
   {
      $options .= "<option value=\"$role->roleId\">$role->roleName</option>";
   }
   
   return ($options);
}

function addUser($firstName, $lastName, $username, $password, $role, $email)
{
   global $duplicateUsername;
   
   $userInfo = new UserInfo();
   
   $roleDetails = Role::getRole($role);
   
   $userInfo->firstName = $firstName;
   $userInfo->lastName = $lastName;
   $userInfo->username = $username;
   $userInfo->passwordHash = password_hash($password, PASSWORD_DEFAULT);
   $userInfo->roles = $role;
   if ($roleDetails)
   {
      $userInfo->permissions = $roleDetails->defaultPermissions;
   }
   $userInfo->email = $email;
   
   $database = FactoryStatsGlobalDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      // Check that the username is unique.
      if (!$database->userExists($userInfo->username))
      {
         $database->newUser($userInfo, CustomerInfo::getCustomerId());
      }
      else
      {
         // Set a global variable that will be used below to show a warning dialog.
         $duplicateUsername = $userInfo->username;
      }
   }
}

function deleteUser($userId)
{
   $database = FactoryStatsGlobalDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteUser($userId);
   }
}

function updateUser($userId, $firstName, $lastName, $username, $password, $role, $email)
{
   $userInfo = UserInfo::load($userId);
   
   if ($userInfo)
   {
      $userInfo->firstName = $firstName;
      $userInfo->lastName = $lastName;
      $userInfo->username = $username;
      $userInfo->roles = $role;
      $userInfo->email = $email;

      // Updated password, if changed.
      if ($password != UserInfo::DUMMY_PASSWORD)
      {
         $userInfo->passwordHash = password_hash($password, PASSWORD_DEFAULT);
      }
      
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $database->updateUser($userInfo);
      }
   }
}

// *****************************************************************************
//                              Action handling

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
   {
      deleteUser($params->get("userId"));
      break;      
   }
   
   case "update":
   {
      if ($params->getInt("userId") == UserInfo::UNKNOWN_USER_ID)
      {
         addUser(
            $params->get("firstName"),
            $params->get("lastName"),
            $params->get("updatedUsername"),
            $params->get("updatedPassword"),
            $params->getInt("role"),
            $params->get("email"),
            $params->get("authToken"));
      }
      else
      {
         updateUser(
            $params->get("userId"),
            $params->get("firstName"),
            $params->get("lastName"),
            $params->get("updatedUsername"),
            $params->get("updatedPassword"),
            $params->getInt("role"),
            $params->get("email"),
            $params->get("authToken"));
      }
      break;
   }
   
   default:
   {
      break;
   }
}

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>User Config</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="user-id-input" type="hidden" name="userId">
   <input id="auth-token-input" type="hidden" name="authToken">   
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <div class="flex-horizontal">
            <button class="config-button" onclick="setUserInfo('', '', '', '', '', '', '', ''); showModal('config-modal');">New User</button>
            <button class="config-button" onclick="window.location.href = 'userCustomerConfig.php';">Sites</button>
         </div>
         <br>
         <?php renderTable();?>
      </div>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <label>First Name</label>
      <input id="first-name-input" type="text" form="config-form" name="firstName" value="">
      <label>Last Name</label>
      <input id="last-name-input" type="text" form="config-form" name="lastName" value="">
      <label>Username</label>
      <input id="username-input" type="text" form="config-form" name="updatedUsername" value="">
      <label>Password</label>
      <input id="password-input" type="password" form="config-form" name="updatedPassword" value="<?php echo UserInfo::DUMMY_PASSWORD ?>">      
      <label>Role</label>
      <select id="role-input" form="config-form" name="role">
         <?php echo getRoleOptions();?>
      </select>
      <label>Email</label>
      <input id="email-input" type="text" form="config-form" name="email" value=""> 
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete user?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<div id="duplidate-username-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>User <?php echo $duplicateUsername ?> already exists</p>
   </div>
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script src="script/userConfig.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);
   
   // Show the duplicate username modal, if necessary.
   if (<?php echo ($duplicateUsername ? "true" : "false")?>)
   {
      showModal("duplidate-username-modal");
   }
</script>

</body>

</html>