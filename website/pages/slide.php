<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/customerInfo.php';
require_once ROOT.'/common/slideInfo.php';

session_start();

Authentication::authenticate();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::WORKSTATION)))
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

function getSlideInfo()
{
   $slideInfo = null;
   
   $slideId = getSlideId();
   
   if ($slideId != SlideInfo::UNKNOWN_SLIDE_ID)
   {
      $slideInfo = SlideInfo::load($slideId);
   }
   
   return ($slideInfo);
}

function getImage()
{
   $image = "";
   
   $slideInfo = getSlideInfo();
   
   if ($slideInfo && ($slideInfo->slideType == SlideType::IMAGE))
   {
      $image = CustomerInfo::getSlideImagesFolder() . "/" . $slideInfo->image;
   }
   
   return ($image);
}

function getCaption()
{
   $caption = "";
   
   $slideInfo = getSlideInfo();
   
   if ($slideInfo && ($slideInfo->slideType == SlideType::IMAGE))
   {
      //$caption = $slideInfo->caption;  // TODO
   }
   
   return ($caption);
}

?>

<!DOCTYPE html>

<html>

   <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      
      <style>
         body, html {
           height: 100%;
           margin: 0;
           font: 400 15px/1.8 "Lato", sans-serif;
           color: #777;
         }

         .bgimg-1, .bgimg-2, .bgimg-3 {
            position: relative;
            opacity: 1.00;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
         }
         
         .bgimg-1 {
            background-image: url("<?php echo getImage() ?>");
            height: 100%;
         }

         .caption {
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            text-align: center;
            color: #000;
            display: <?php echo (getCaption() == "") ? "none" : "inherit" ?>;
         }

         .caption span.border {
            background-color: #111;
            color: #fff;
            padding: 18px;
            font-size: 25px;
            letter-spacing: 10px;
         }

         h3 {
            letter-spacing: 5px;
            text-transform: uppercase;
            font: 20px "Lato", sans-serif;
            color: #111;
         }
      </style>
   </head>
   
   <body>

      <div class="bgimg-1">
         <div class="caption">
            <span class="border"><?php echo getCaption() ?></span><br>
         </div>
      </div>

   </body>
   
</html>
