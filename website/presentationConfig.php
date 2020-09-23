<?php

require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/presentationInfo.php';
require_once 'common/version.php';

Time::init();

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::PRESENTATION_CONFIG)))
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
         <th>Name</th>
         <th>Slides</th>
         <th></th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;

   $database = FlexscreenDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $result = $database->getPresentations();

      while ($result && $row = $result->fetch_assoc())
      {
         $presentationInfo = PresentationInfo::load($row["presentationId"]);

         $id = "presentation-" . $presentationInfo->presentationId;
         
         $slideCount = count($presentationInfo->slides);

         echo
<<<HEREDOC
         <tr>
            <td>$presentationInfo->name</td>
            <td>$slideCount</td>
            <td><button class="config-button" onclick="document.location = 'slideConfig.php?presentationId=$presentationInfo->presentationId'">Configure</button></div></td>
            <td><button class="config-button" onclick="setPresentationId($presentationInfo->presentationId); showModal('confirm-delete-modal');">Delete</button></div></td>
            <td><button class="config-button" onclick="setPresentationId($presentationInfo->presentationId); showModal('preview-modal');">Preview</button></div></td>
         </tr>
HEREDOC;
      }
   }

   echo "</table>";
}

function deletePresentation($presentationId)
{
   $database = FlexscreenDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $database->deletePresentation($presentationId);
   }
}

function addPresentation($name)
{
   $presentationInfo = new PresentationInfo();
   $presentationInfo->name = $name;
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->newPresentation($presentationInfo);
   }
}

function updatePresentation($presentationId, $name)
{
   $presentationInfo = PresentationInfo::load($presentationId);
   $presentationInfo->name = $name;

   $database = FlexscreenDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $database->updatePresentation($presentationInfo);
   }
}

// *****************************************************************************
//                              Action handling

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
   {
      deletePresentation($params->getInt("presentationId"));
      break;
   }

   case "update":
   {
      if ($params->getInt("presentationId") == PresentationInfo::UNKNOWN_PRESENTATION_ID)
      {
         addPresentation(
            $params->get("name"));
      }
      else
      {
         updatePresentation(
            $params->getInt("presentationId"),
            $params->get("name"));
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

   <title>Presentation Config</title>

   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>

</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="presentation-id-input" type="hidden" name="presentationId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <button class="config-button" onclick="setPresentationConfig(0, ''); showModal('config-modal');">New Presentation</button>
         <br>
         <?php renderTable();?>
      </div>
   </div>   

</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      
      <div class="flex-vertical input-block">
         <label>Name</label>
         <input id="name-input" form="config-form" name="name">
      </div>
      
      <div class="flex-vertical input-block">
         <label>Slides</label>
         <table id="slide-table">
            <tr id="slide-template-row">
               <td class="content"></td>
               <td><button class="config-button edit-slide-button" type="submit" form="config-form">E</button></td>
               <td><button class="config-button delete-slide-button" type="submit" form="config-form">D</button></td>
            </tr>
         </table>
         <div class="flex-horizontal">
            <button class="config-button" onclick="addSlide()">+</button>
         </div>
      </div>
      
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete presentation?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setPresentationId(presentationId)
   {
      var input = document.getElementById('presentation-id-input');
      input.value = presentationId;
   }

   function setPresentationConfig(presentationId, name)
   {
      setPresentationId(presentationId);
      
      document.getElementById('name-input').value = name;
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }
</script>

</body>

</html>