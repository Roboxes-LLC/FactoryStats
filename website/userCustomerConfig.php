<?php

require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/userInfo.php';
require_once 'common/version.php';

session_start();

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
HEREDOC;
   
   $customerIds = Authentication::getAuthenticatedUser()->getCustomers();
   
   foreach ($customerIds as $customerId)
   {
      $customerInfo = CustomerInfo::load($customerId);
      
      if ($customerInfo)
      {
         echo
<<<HEREDOC
         <th>$customerInfo->name</th>
HEREDOC;
      }
   }
   
   echo "</tr>";
   
   $database = FactoryStatsGlobalDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getUsersForCustomers($customerIds);
      
      foreach ($result as $row)
      {
         $userInfo = UserInfo::load(intval($row["userId"]));
         
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
            <td>$name</td>
            <td>$userInfo->username</td>
HEREDOC;

         $userCustomerIds = $userInfo->getCustomers();
         
         foreach ($customerIds as $customerId)
         {
            $customerInfo = CustomerInfo::load($customerId);
            
            $checked = in_array($customerId, $userCustomerIds) ? "checked" : "";
            
            echo
<<<HEREDOC
            <td><input type="checkbox" data-userId="$userInfo->userId" value="$customerId" onclick="onCustomerClicked(this)" $checked></td>
HEREDOC;
         }
         
         echo "</tr>";
      }
   }
   
   echo "</table>";
}

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>User Site Assignments</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <div class="flex-horizontal" style="align-self: flex-start"><a class="nav-link" style="color: #14a3db;" href="userConfig.php">Back to Users</a></div>
         <br>
         <?php renderTable();?>
      </div>
   </div>
     
</div>

<!--  Modal dialogs -->

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script src="script/userConfig.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);
</script>

</body>

</html>