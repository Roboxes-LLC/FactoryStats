<?php

require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/userInfo.php';

Time::init();

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::USER_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

function renderTable()
{
   echo 
<<<HEREDOC
   <table>
      <tr>
         <th>Employee #</th>
         <th>Name</th>
         <th>Username</th>
         <th>Role</th>
         <th>Email</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getUsers();
      
      while ($result && $row = $result->fetch_assoc())
      {
         $userInfo = UserInfo::load(intval($row["employeeNumber"]));
         
         $name = $userInfo->getFullName();
         
         $roleName = "Unassigned";
         $role = Role::getRole($userInfo->roles);
         if ($role)
         {
            $roleName = $role->roleName;
         }
         
         echo 
<<<HEREDOC
         <tr>
            <td>$userInfo->employeeNumber</td>
            <td>$name</td>
            <td>$userInfo->username</td>
            <td>$roleName</td>
            <td>$userInfo->email</td>
            <td><button class="config-button" onclick="setUserInfo('$userInfo->employeeNumber', '$userInfo->firstName', '$userInfo->lastName', '$userInfo->username', '$userInfo->roles', '$userInfo->email'); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setEmployeeNumber($userInfo->employeeNumber); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function getRoleOptions()
{
   $options = "";

   $roles = Role::getRoles();
   
   foreach ($roles as $role)
   {
      $options .= "<option value=\"$role->roleId\">$role->roleName</option>";
   }
   
   return ($options);
}

function deleteUser($employeeNumber)
{
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteUser($employeeNumber);
   }
}

function updateUser($employeeNumber)
{
   $userInfo = UserInfo::load($employeeNumber);
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->updateUser($userInfo);
   }
}

// *****************************************************************************
//                              Action handling

Time::init();

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
   {
      deleteUser($params->get("employeeNumber"));
      break;      
   }
   
   case "update":
   {
      updateUser($params->get("employeeNumber"),
                 $params->get("firstName"),
                 $params->get("lastName"),
                 $params->get("username"),
                 intval($params->get("role")),
                 $params->get("email"));
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
   
   <title>Hardware Button Status</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css"/>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="button-id-input" type="hidden" name="buttonId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <button class="config-button" onclick="setUserInfo('', '', '', '', '0', ''); showModal('config-modal'); showModal('config-modal');">New User</button>
         <br>
         <?php renderTable();?>
      </div>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <label>Employee #</label>
      <input id="employee-number-input" type="text" form="config-form" name="employeeNumber" value=""> 
      <label>First Name</label>
      <input id="first-name-input" type="text" form="config-form" name="firstName" value="">
      <label>Last Name</label>
      <input id="last-name-input" type="text" form="config-form" name="lastName" value="">
      <label>Username</label>
      <input id="username-input" type="text" form="config-form" name="username" value="">
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

<script src="script/flexscreen.js"></script>
<script src="script/modal.js"></script>
<script src="script/userConfig.js"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setEmployeeNumber(employeeNumber)
   {
      var input = document.getElementById('employee-number-input');
      input.setAttribute('value', employeeNumber);
   }
</script>

</body>

</html>