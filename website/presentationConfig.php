<?php

require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/presentationInfo.php';
require_once 'common/version.php';

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::PRESENTATION_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

Time::init(CustomerInfo::getTimeZone());

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getPresentationId()
{
   $presentationId = PresentationInfo::UNKNOWN_PRESENTATION_ID;
   
   $params = getParams();
   
   if ($params->keyExists("presentationId"))
   {
      $presentationId = $params->getInt("presentationId");
   }
   
   return ($presentationId);
}

function getPresentationInfo()
{
   $presentationInfo = null;
   
   $presentationId = getPresentationId();
   
   if ($presentationId != PresentationInfo::UNKNOWN_PRESENTATION_ID)
   {
      $presentationInfo = PresentationInfo::load($presentationId);
   }
   
   return ($presentationInfo);
}

function getPresentationName()
{
   $presentationName = "";
   
   $presentationInfo = getPresentationInfo();
   
   if ($presentationInfo)
   {
      $presentationName = $presentationInfo->name;
   }
   
   return ($presentationName);
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
      </tr>
HEREDOC;

   $database = FactoryStatsDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $result = $database->getPresentations();

      foreach ($result as $row)
      {
         $presentationInfo = PresentationInfo::load($row["presentationId"]);
         
         $slideCount = count($presentationInfo->slides);

         echo
<<<HEREDOC
         <tr>
            <td>$presentationInfo->name</td>
            <td>$slideCount</td>
            <td><button class="config-button" onclick="setPresentationConfig($presentationInfo->presentationId, '$presentationInfo->name'); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setPresentationId($presentationInfo->presentationId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }

   echo "</table>";
}

function deletePresentation($presentationId)
{
   $database = FactoryStatsDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $database->deletePresentation($presentationId);
   }
}

function addPresentation($name)
{
   $presentationInfo = new PresentationInfo();
   $presentationInfo->name = $name;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->newPresentation($presentationInfo);
   }
}

function updatePresentation($presentationId, $name)
{
   $presentationInfo = PresentationInfo::load($presentationId);
   $presentationInfo->name = $name;

   $database = FactoryStatsDatabase::getInstance();

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
   <input id="action-input" type="hidden" name="action" value="<?php echo getParams()->get("action"); ?>">
   <input id="presentation-id-input" type="hidden" name="presentationId" value="<?php echo getPresentationId(); ?>">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
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
         <input id="name-input" form="config-form" name="name" value="<?php echo getPresentationName(); ?>">
      </div>
      
      <div class="flex-horizontal">
         <button id="edit-slides-button" class="config-button" onclick="editSlides()">Edit Slides</button>
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

<script src="script/common.js<?php echo versionQuery();?>"></script>
<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);
   
   // Set the supplied presentation id and open the edit dialog if set.
   if ((document.getElementById('action-input').value == "edit") && 
       (document.getElementById('presentation-id-input').value != 0))
   {
      showModal('config-modal');
   }

   function setPresentationId(presentationId)
   {
      var input = document.getElementById('presentation-id-input');
      input.value = presentationId;
   }

   function setPresentationConfig(presentationId, name)
   {
      setPresentationId(presentationId);
      
      document.getElementById('name-input').value = name;
      
      // Hide the edit slides button for new presentations.
      if (presentationId != 0)
      {
         show('edit-slides-button', 'block');
      }
      else
      {
         hide('edit-slides-button');
      }
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }
   
   function editSlides()
   {
      var presentationId = parseInt(document.getElementById('presentation-id-input').value);
      document.location = "slideConfig.php?presentationId=" + presentationId;
   }
</script>

</body>

</html>