<?php

require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/presentationInfo.php';
require_once 'common/root.php';
require_once 'common/slideInfo.php';
require_once 'common/upload.php';
require_once 'common/version.php';

Time::init();

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::PRESENTATION_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

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

function getSlideId()
{
   $slideId = SlideInfo::UNKNOWN_SLIDE_ID;
   
   $params = getParams();
   
   if ($params->keyExists("slideId"))
   {
      $slideId = $params->getInt("slideId");
   }
   
   return ($slideId);
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
   $presentationName = "<unknown>";
   
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
         <th>Content</th>
         <th>Duration</th>
         <th>Enabled</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;

   $presentationInfo = getPresentationInfo();
   
   if ($presentationInfo)
   {
      foreach ($presentationInfo->slides as $slideInfo)
      {
         $content = $slideInfo->getContentDescription();
         
         $enabled = $slideInfo->enabled ? "Enabled" : "Disabled";
         
         echo
<<<HEREDOC
         <tr>
            <td>$content</td>
            <td>$slideInfo->duration</td>
            <td>$enabled</td>
            <td><button class="config-button" onclick="setSlideConfig($slideInfo->slideId, $slideInfo->slideType); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setSlideId($slideInfo->slideId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }

   echo "</table>";
}

function getSlideTypeOptions()
{
   $options = "<option style=\"display:none\">";
   
   $presentationInfo = getPresentationInfo();
   
   if ($presentationInfo)
   {
      foreach (SlideType::$values as $slideType)
      {
         $label = SlideType::getLabel($slideType);
         
         $options .= "<option value=\"$slideType\">$label</option>";
      }
   }
   
   return ($options);
}

function deleteSlide($slideId)
{
   $database = FlexscreenDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $database->deleteSlide($slideId);
   }
}

function addSlide(
   $slideType,
   $duration,
   $enabled,
   $url,
   $image,
   $shiftId,
   $stationIds)
{
   $slideInfo = new SlideInfo();
   
   $slideInfo->presentationId = getPresentationId();
   $slideInfo->slideType = $slideType;
   $slideInfo->slideIndex = 0;  // TODO
   $slideInfo->duration = $duration;   
   $slideInfo->enabled = $enabled;
   $slideInfo->url = $url;
   $slideInfo->image = $image;
   $slideInfo->shiftId = $shiftId;
   $slideInfo->stationIds = $stationIds;
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->newSlide($slideInfo);
   }
}

function updateSlide(
   $slideId,
   $slideType,
   $duration,
   $enabled,
   $url,
   $image,
   $shiftId,
   $stationIds)
{
   $slideInfo = SlideInfo::load($slideId);
   
   if ($slideInfo)
   {
      $slideInfo->presentationId = getPresentationId();
      $slideInfo->slideType = $slideType;
      $slideInfo->duration = $duration;
      $slideInfo->enabled = $enabled;
      $slideInfo->url = $url;
      $slideInfo->image = $image;
      $slideInfo->shiftId = $shiftId;
      $slideInfo->stationIds = $stationIds;

      $database = FlexscreenDatabase::getInstance();
   
      if ($database && $database->isConnected())
      {
         $database->updateSlide($slideInfo);
      }
   }
}

// *****************************************************************************
//                              Action handling

switch (getParams()->get("action"))
{
   case "delete":
   {
      deleteSlide(getSlideId());
      break;
   }

   case "update":
   {
      $params = getParams();
      
      //
      // Upload any specified image file.
      //
      
      $imageFile = $params->get("image");
      
      if (isset($_FILES["newImage"]) && ($_FILES["newImage"]["name"] != ""))
      {
         $uploadStatus = Upload::uploadSlideImage($_FILES["newImage"]);
         
         if ($uploadStatus == UploadStatus::UPLOADED)
         {
            $imageFile = basename($_FILES["newImage"]["name"]);
         }
      }   
      
      if (getSlideId() == SlideInfo::UNKNOWN_SLIDE_ID)
      {
         addSlide(
            $params->getInt("slideType"),
            $params->getInt("duration"),
            $params->getBool("enabled"),
            $params->get("url"),
            $imageFile,
            $params->getInt("shift"),
               array($params->getInt("station1"),
                     $params->getInt("station2"),
                     $params->getInt("station3"),
                     $params->getInt("station4")));
      }
      else
      {
         updateSlide(
            $params->getInt("slideId"),
            $params->getInt("slideType"),
            $params->getInt("duration"),
            $params->getBool("enabled"),
            $params->get("url"),
            $imageFile,
            $params->getInt("shift"),
            array($params->getInt("station1"),
                  $params->getInt("station2"),
                  $params->getInt("station3"),
                  $params->getInt("station4")));
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

   <title>Slide Config</title>

   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>

</head>

<body>

<form id="config-form" method="post" enctype="multipart/form-data">
   <input id="action-input" type="hidden" name="action">
   <input id="presentation-id-input" type="hidden" name="presentationId" value="<?php echo getPresentationId()?>">
   <input id="slide-id-input" type="hidden" name="slideId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <div class="flex-horizontal" style="align-self: flex-start"><a class="nav-link" style="color: #14a3db;" href="presentationConfig.php?action=edit&presentationId=<?php echo getPresentationId()?>">Back to Presentation</a></div>
         <br>
         <div style="align-self: flex-start">Presentation: <span style="color: yellow;"><?php echo getPresentationName(); ?></span></div>
         <br>
         <?php renderTable();?>
         <br>
         <button class="config-button" onclick="setSlideConfig(0); showModal('config-modal');">Add Slide</button>
      </div>
   </div>   

</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      
      <div class="flex-vertical input-block">
         <label>Type</label>
         <select id="slide-type-input" form="config-form" name="slideType" onchange="onSlideTypeUpdated()">
            <?php echo getSlideTypeOptions(); ?>
         </select>
      </div>
      
      <div class="flex-horizontal input-block">
         <label>Duration</label>
         <input id="duration-input" type="number" style="width: 50px;" form="config-form" name="duration">
      </div>
      
      <div id="url-slide-params">
         <div class="flex-vertical input-block">
            <label>URL</label>
            <input id="url-input" type="text" form="config-form" name="url">
         </div>
      </div>
      
      <div id="image-slide-params">
         <div class="flex-vertical input-block">
            <label>Image</label>
            <img id="image-thumbnail" class="thumbnail">
         </div>
         <div class="flex-vertical input-block">            
            <input id="image-input" type="hidden" name="image">
            <input type="file" name="newImage" form="config-form">
         </div>
      </div>
      
      <div id="shift-based-slide-params">
         <div class="flex-vertical input-block">
            <label>Shift</label>
            <select id="shift-input" form="config-form" name="shift">
               <?php echo ShiftInfo::getShiftOptions(ShiftInfo::UNKNOWN_SHIFT_ID, true); ?>
            </select>
         </div>
      </div>
      
      <div id="workstation-slide-params">
         <div class="flex-horizontal input-block">
            <label>Stations</label>
         </div>
         
         <div class="flex-horizontal input-block">
            <select id="station-1-input" form="config-form" name="station1">
               <?php echo StationInfo::getStationOptions(StationInfo::UNKNOWN_STATION_ID); ?>
            </select>
         </div>
         
         <div class="flex-horizontal input-block">
            <select id="station-2-input" form="config-form" name="station2">
               <?php echo StationInfo::getStationOptions(StationInfo::UNKNOWN_STATION_ID); ?>
            </select>
         </div>
         
         <div class="flex-horizontal input-block">
            <select id="station-3-input" form="config-form" name="station3">
               <?php echo StationInfo::getStationOptions(StationInfo::UNKNOWN_STATION_ID);?>
            </select>
         </div>         
      
         <div class="flex-horizontal input-block">
            <select id="station-4-input" form="config-form" name="station4">
               <?php echo StationInfo::getStationOptions(StationInfo::UNKNOWN_STATION_ID); ?>
            </select>
         </div>         
      </div>
                  
      <div class="flex-horizontal input-block">
         <label>Enabled</label>
         <input id="enabled-input" type="checkbox" form="config-form" name="enabled">
      </div>
      
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete slide?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script src="script/common.js<?php echo versionQuery();?>"></script>
<script src="script/presentationConfig.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setPresentationId(presentationId)
   {
      var input = document.getElementById('presentation-id-input');
      input.value = presentationId;
   }
   
   function setSlideId(slideId)
   {
      var input = document.getElementById('slide-id-input');
      input.value = slideId;
   }

   function setSlideConfig(slideId)
   {
      setSlideId(slideId);
      
      if (slideId == <?php echo SlideInfo::UNKNOWN_SLIDE_ID; ?>)
      {
         resetSlideConfig();
      }
      else
      {
         for (slideInfo of presentationInfo.slides)
         {
            if (slideInfo.slideId == slideId)
            {
               document.getElementById('slide-type-input').value = slideInfo.slideType;
               document.getElementById('duration-input').value = slideInfo.duration;
               document.getElementById('enabled-input').checked = slideInfo.enabled;
               document.getElementById('url-input').value = slideInfo.url;
               document.getElementById('image-input').value = slideInfo.image;
               document.getElementById('image-thumbnail').src = "<?php echo CustomerInfo::getSlideImagesFolder(); ?>/" + slideInfo.image;
               document.getElementById('shift-input').value = slideInfo.shiftId;
               
               for (var i = 0; i < <?php echo SlideInfo::MAX_STATION_IDS; ?>; i++)
               {
                  var id = "station-" + (i + 1) + "-input";
                  
                  document.getElementById(id).value = slideInfo.stationIds[i];               
               }
               break;
            }
         }
      }
      
      showCustomSlideParams();
   }
   
         
   function resetSlideConfig()
   {
      document.getElementById('slide-type-input').value = <?php echo SlideType::UNKNOWN; ?>;
      document.getElementById('duration-input').value = 0;
      document.getElementById('enabled-input').checked = false;
      
      resetCustomSlideParams();
   }
   
   function resetCustomSlideParams()
   {
      document.getElementById('url-input').value = "";
      document.getElementById('image-thumbnail').src = "<?php echo $IMAGES_DIR; ?>/no-image-icon-6.png";
      document.getElementById('shift-input').value = <?php echo ShiftInfo::UNKNOWN_SHIFT_ID; ?>;
      
      for (var i = 0; i < <?php echo SlideInfo::MAX_STATION_IDS?>; i++)
      {
         var id = "station-" + (i + 1) + "-input";
         
         document.getElementById(id).value = <?php echo StationInfo::UNKNOWN_STATION_ID; ?>;               
      }
   }
   
   function onSlideTypeUpdated()
   {
      resetCustomSlideParams();
      
      showCustomSlideParams();
   }   
 
   function showCustomSlideParams()
   {
      var slideType = parseInt(document.getElementById('slide-type-input').value);
      
      hide("url-slide-params");
      hide("image-slide-params");
      hide("shift-based-slide-params");
      hide("workstation-slide-params");
      
      switch (slideType)
      {
         case <?php echo SlideType::URL ?>:
         {
            show("url-slide-params", "block");
            break;
         }
         
         case <?php echo SlideType::IMAGE ?>:
         {
            show("image-slide-params", "block");
            break;
         }
         
         case <?php echo SlideType::WORKSTATION_SUMMARY_PAGE ?>:
         {
            show("shift-based-slide-params", "block");
            break;
         }
         
         case <?php echo SlideType::WORKSTATION_PAGE ?>:
         {
            show("shift-based-slide-params", "block");
            show("workstation-slide-params", "block");
            break;
         }
         
         default:
         {
            break;
         }
      }
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }
   
   var presentationInfo = null;

   // Load and store the current config for all slides associated with the presentation. 
   getPresentation(<?php echo getPresentationId(); ?>, 
                   function(newPresentationInfo) {presentationInfo = newPresentationInfo;});
</script>

</body>

</html>