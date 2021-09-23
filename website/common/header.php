<?php 

require_once 'authentication.php';
require_once 'customerInfo.php';
require_once 'kiosk.php';
require_once 'root.php';
require_once 'shiftInfo.php';

class Header
{
   public static function getHtml($includeShiftIdInput = false, $includeStationFilterInput = false, $includeCustomerFilterInput = false)
   {
      global $ROOT;
      
      $shiftIdInput = "";
      if ($includeShiftIdInput)
      {
         // Retrive the currently selected shift id.
         $shiftId = ShiftInfo::getShiftId();
          
         $shiftOptions = ShiftInfo::getShiftOptions($shiftId, false);
         
         $shiftIdInput = 
<<<HEREDOC
            <select id="shift-id-input" class="header-input" name="shiftId" onchange="onShiftSelectionUpdate(); storeInSession('shiftId', this.value); update();">$shiftOptions</select>
HEREDOC;
      }
      
      $stationFilterInput = "";
      if ($includeStationFilterInput)
      {
         // Retrieve any station filter that is currently selected.
         $stationFilter = Header::getStationFilter();
         
         $stationFilterOptions = StationFilter::getOptions($stationFilter);
         
         
         $stationFilterInput =
<<<HEREDOC
         <select id="station-filter-input" class="header-input" name="stationFilter" onchange="storeInSession('stationFilter', this.value); update();">$stationFilterOptions</select>
HEREDOC;
      }
      
      $customerFilterInput = "";
      if ($includeCustomerFilterInput)
      {
         // Retrieve any customer filter that is currently selected.
         $customerFilter = Header::getCustomerFilter();
         
         $customerIds = array();
         $userInfo = Authentication::getAuthenticatedUser();
         if ($userInfo)
         {
            $customerIds = $userInfo->getCustomers($customerIds);
         }
         
         $customerFilterOptions = CustomerInfo::getCustomerOptions($customerIds, $customerFilter);
         
         
         $customerFilterInput =
<<<HEREDOC
         <select id="customer-filter-input" class="header-input" name="customerFilter" onchange="storeInSession('customerFilter', this.value); update();">$customerFilterOptions</select>
HEREDOC;
      }
      
      $imagesFolder = CustomerInfo::getImagesFolder();
      
      $html = 
<<<HEREDOC
      <div class="flex-horizontal header">
         <div class="flex-horizontal" style="width:33%; justify-content:flex-start; margin-left: 20px;">
            <div class="flex-horizontal">$shiftIdInput $stationFilterInput</div>
            &nbsp;
            <!-- TODO: Include a visual indicator of the current shift -->
            <!--i id="am-indicator" class="material-icons" style="color: yellow; font-size: 35px;">wb_sunny</i>
            <i id="pm-indicator" class="material-icons" style="color: #ffffff; font-size: 35px;">brightness_3</i-->
         </div>

         <div class="flex-horizontal" style="width:33%; justify-content:center;">
            <img class="header-image" src="$imagesFolder/flexscreen-logo.png" width="350px">
         </div>

         <div class="flex-horizontal" style="width:33%; justify-content:flex-end; margin-right: 20px;">
HEREDOC;
      
      if (!isKioskMode() && Authentication::isAuthenticated())
      {
         $username = Authentication::getAuthenticatedUser()->username;
         
         $html .=
<<<HEREDOC
            $customerFilterInput
            <i class="material-icons" style="margin-right:5px; color: #ffffff; font-size: 35px;">person</i>
            <div class="nav-username">$username &nbsp | &nbsp</div>
            <a class="nav-link" href="$ROOT/index.php?action=logout">Logout</a>
HEREDOC;
      }
         
      $html .= 
<<<HEREDOC
         <div id="screen-res-div"></div>
         </div>
      </div>
HEREDOC;
      
      return ($html);
   }
   
   public static function render($includeShiftIdInput = false, $includeStationFilterInput = false, $includeCustomerFilterInput = false)
   {
      echo (Header::getHtml($includeShiftIdInput, $includeStationFilterInput, $includeCustomerFilterInput));
   }
   
   private static function getStationFilter()
   {
      $stationFilter = StationFilter::ALL_STATIONS;
      
      $params = Params::parse();
      
      if ($params->keyExists("stationFilter"))
      {
         $stationFilter = $params->getInt("stationFilter");
      }
      
      return ($stationFilter);
   }
   
   private static function getCustomerFilter()
   {
      $customerFilter = CustomerInfo::UNKNOWN_CUSTOMER_ID;
      
      if (isset($_SESSION["customerId"]))
      {
         $customerFilter = intval($_SESSION["customerId"]);
      }
      
      return ($customerFilter);
   }
}

?>
