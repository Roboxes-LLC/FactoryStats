<?php

require_once 'common/breakDescription.php';
require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/version.php';

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::BREAK_CONFIG)))
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
         <th>Break code</th>
         <th>Description</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getBreakDescriptions();
      
      foreach ($result as $row)
      {
         $breakDescription = BreakDescription::load($row["breakDescriptionId"]);
         
         echo 
<<<HEREDOC
         <tr>
            <td>$breakDescription->code</td>
            <td>$breakDescription->description</td>
            <td><button class="config-button" onclick="setBreakDescriptionId($breakDescription->breakDescriptionId); setBreakDescriptionInfo('$breakDescription->code', '$breakDescription->description'); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setBreakDescriptionId($breakDescription->breakDescriptionId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function addBreakDescription($code, $description)
{
   $breakDescription = new BreakDescription();
   $breakDescription->code = $code;
   $breakDescription->description = $description;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->newBreakDescription($breakDescription);
   }
}

function deleteBreakDescription($breakDescriptionId)
{
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteBreakDescription($breakDescriptionId);
   }
}

function updateBreakDescription($breakDescriptionId, $code, $description)
{
   $breakDescription = BreakDescription::load($breakDescriptionId);
   $breakDescription->code = $code;
   $breakDescription->description = $description;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->updateBreakDescription($breakDescription);
   }
}

// *****************************************************************************
//                              Action handling

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
   {
      deleteBreakDescription($params->get("breakDescriptionId"));
      break;
   }
      
   case "update":
   {
      if (is_numeric($params->get("breakDescriptionId")))
      {
         updateBreakDescription(
            $params->get("breakDescriptionId"),
            $params->get("code"),
            $params->get("description"));
      }
      else
      {
         addBreakDescription(
            $params->get("code"),
            $params->get("description"));
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
   
   <title>Break Description Config</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="break-description-id-input" type="hidden" name="breakDescriptionId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <button class="config-button" onclick="setBreakDescriptionInfo('', ''); showModal('config-modal'); showModal('config-modal');">New Break Code</button>
         <br>
         <?php renderTable();?>
      </div>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <label>Break code</label>
      <input id="code-input" type="text" form="config-form" name="code">
      <label>Description</label>
      <input id="description-input" type="text" form="config-form" name="description">
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete break code?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setBreakDescriptionId(breakDescriptionId)
   {
      var input = document.getElementById('break-description-id-input');
      input.setAttribute('value', breakDescriptionId);
   }

   function setBreakDescriptionInfo(code, description)
   {
      var input = document.getElementById('code-input');
      input.setAttribute('value', code);
      
      input = document.getElementById('description-input');
      input.setAttribute('value', description);
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }
</script>

</body>

</html>