<?php

require_once '../common/database.php';
require_once '../common/databaseKey.php';
require_once '../common/userInfo.php';

//phpinfo();

global $DATABASE_TYPE, $SERVER, $USER, $PASSWORD, $DATABASE;
global $GLOBAL_SERVER, $GLOBAL_USER, $GLOBAL_PASSWORD, $GLOBAL_DATABASE;

echo "<b>DATABASE_TYPE</b>: " . DatabaseType::getLabel($DATABASE_TYPE) . "<br>";
echo "<b>DATABASE</b>: $DATABASE<br>";
echo "<b>GOBAL_DATABASE</b>: $GLOBAL_DATABASE<br><br>";

$database = FactoryStatsDatabase::getInstance();

$globalDatabase = FactoryStatsGlobalDatabase::getInstance();

if ($database->isConnected())
{
   echo "<b>query()</b><br>";
   $result = $database->query("SELECT * FROM station WHERE name = 'Coating';");
   while ($result && $row = $result->fetch())
   {
      var_dump($row);
   }
   
   echo "<b>getUser</b><br>";
   $result = $globalDatabase->getUser(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getUserByName</b><br>";
   $result = $globalDatabase->getUserByName("jtost");
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getUsers</b><br>";
   $result = $globalDatabase->getUsers();
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getUsers</b><br>";
   $result = $globalDatabase->getUsers();
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getUsersForCustomer</b><br>";
   $result = $globalDatabase->getUsersForCustomer(3);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getUsersByRoles</b><br>";
   $result = $globalDatabase->getUsersByRoles([1, 2]);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   $userInfo = new UserInfo();
   $userInfo->employeeNumber = 101;
   $userInfo->username = "bbaggins";
   $userInfo->passwordHash = "ABCDEFGHIJKLMNOP";
   $userInfo->firstName = "Bilbo";
   $userInfo->lastName = "Baggins";
   $userInfo->roles = 1;
   $userInfo->permissions = 1;       // bitfield
   $userInfo->email = "bbaggins@hotmail.com";
   $userInfo->authToken = "ABCDEFG";
   
   echo "<b>newUser</b><br>";
   if ($globalDatabase->newUser($userInfo))
   {
      $userId = $globalDatabase->lastInsertId();
      $userInfo = UserInfo::load($userId);
      echo "newUser [$userId]<br>";
      var_dump($userInfo);
      
      echo "<b>updateUser</b><br>";
      $userInfo->username = "fbaggins";
      $userInfo->firstName = "Frodo";
      if ($globalDatabase->updateUser($userInfo))
      {
         echo "updateUser [$userId]<br>";
         $userInfo = UserInfo::load($userId);
         var_dump($userInfo);
      }
      
      echo "<b>deleteUser</b><br>";
      if ($globalDatabase->deleteUser($userId))
      {
         echo "deleteUser [$userId] <br>";
      }
   }
   
   echo "<b>getCurrentBreakId</b><br>";
   echo $database->getCurrentBreakId(1, 1) . "<br>";
   
   echo "<b>isOnBreak</b><br>";
   echo $database->isOnBreak(1, 1) ? "true<br>" : "false<br>";
      
   echo "<b>startBreak</b><br>";
   if ($database->startBreak(1, 1, 1, Time::now("Y-m-d H:i:s")))
   {
      $breakId = $database->lastInsertId();
      echo "startBreak: $breakId<br>";
      $breakInfo = BreakInfo::load($breakId);
      var_dump($breakInfo);
   
      echo "<b>endBreak</b><br>";
      if ($database->endBreak(1, 1, Time::now("Y-m-d H:i:s")))
      {
         $breakInfo = BreakInfo::load($breakId);
         var_dump($breakInfo);
      }
   }
   
   echo "<b>getBreakDescription</b><br>";
   $result = $database->getBreakDescription(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getBreakDescriptions</b><br>";
   $result = $database->getBreakDescriptions();
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   $breakDescription = new BreakDescription();
   $breakDescription->code = "SMK";
   $breakDescription->description = "Smoke";
   
   echo "<b>newBreakDescription</b><br>";
   if ($database->newBreakDescription($breakDescription))
   {
      $breakDescriptionId = $database->lastInsertId();
      $breakDescription = BreakDescription::load($breakDescriptionId);
      echo "newBreakDescription [$breakDescriptionId]<br>";
      var_dump($breakDescription);
      
      echo "<b>updateBreakDescription</b><br>";
      $breakDescription->code = "CGR";
      $breakDescription->description = "Cigar";
      if ($database->updateBreakDescription($breakDescription))
      {
         echo "updateBreakDescription [$breakDescriptionId]<br>";
         $breakDescription = BreakDescription::load($breakDescriptionId);
         var_dump($breakDescription);
      }
      
      echo "<b>deleteBreakDescription</b><br>";
      if ($database->deleteBreakDescription($breakDescriptionId))
      {
         echo "deleteBreakDescription [$breakDescriptionId] <br>";
      }
   }
   
   // **************************************************************************
   
   echo "<b>getButton</b><br>";
   $result = $database->getButton(21);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getButtons</b><br>";
   $result = $database->getButtons();
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getButtonsForStations</b><br>";
   $result = $database->getButtonsForStation(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getButtonByUid</b><br>";
   $result = $database->getButtonByUid("FLEXPGH_01");
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>buttonExists</b><br>";
   echo "buttonExists [FLEXPGH_01]: " .  $database->buttonExists("FLEXPGH_01") ? "true" : "false";
   echo "buttonExists [FLEXPGH_99]: " .  $database->buttonExists("FLEXPGH_01") ? "true" : "false";
   echo "<br>";
   
   $buttonInfo = new ButtonInfo();
   $buttonInfo->uid = "FLEXATL_01";
   $buttonInfo->enabled = true;
   $buttonInfo->lastContact = Time::now("Y-m-d H:i:s");
   $buttonInfo->name = "";
   
   echo "<b>newButton</b><br>";
   if ($database->newButton($buttonInfo))
   {
      $buttonId = $database->lastInsertId();
      $buttonInfo = ButtonInfo::load($buttonId);
      echo "newButton [$buttonId]<br>";
      var_dump($buttonInfo);
      
      echo "<b>updateButton</b><br>";
      $buttonInfo->uid = "FLEXALB_01";
      if ($database->updateButton($buttonInfo))
      {
         echo "updateButton [$buttonId]<br>";
         $buttonInfo = ButtonInfo::load($buttonId);
         var_dump($buttonInfo);
      }
      
      echo "<b>deleteButton</b><br>";
      if ($database->deleteButton($buttonId))
      {
         echo "deleteButton [$buttonId] <br>";
      }
   }
   
   // **************************************************************************
   
   echo "<b>getCustomer</b><br>";
   $result = $globalDatabase->getCustomer(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getCustomerFromSubdomain</b><br>";
   $result = $globalDatabase->getCustomerFromSubdomain("flexscreendet");
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getCustomersForUser</b><br>";
   $result = $globalDatabase->getCustomersForUser(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   // **************************************************************************
   
   echo "<b>getDisplay</b><br>";
   $result = $database->getDisplay(13);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getDisplays</b><br>";
   $result = $database->getDisplays();
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getDisplayByUid</b><br>";
   $result = $database->getDisplayByUid("ABC123");
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   $displayInfo = new DisplayInfo();
   $displayInfo->uid = "SEC_CAM_1";
   $displayInfo->enabled = true;
   $displayInfo->lastContact = Time::now("Y-m-d H:i:s");
   $displayInfo->name = "Security Camera";
   
   echo "<b>new</b><br>";
   if ($database->newDisplay($displayInfo))
   {
      $displayId = $database->lastInsertId();
      $displayInfo = DisplayInfo::load($displayId);
      echo "newDisplay [$displayId]<br>";
      var_dump($displayInfo);
      
      echo "<b>updateDisplay</b><br>";
      $displayInfo->uid = "SEC_CAM_2";
      $displayInfo->name = "Aux Security Camera";
      if ($database->updateDisplay($displayInfo))
      {
         echo "updateDisplay [$displayId]<br>";
         $displayInfo = DisplayInfo::load($displayId);
         var_dump($displayInfo);
      }
      
      echo "<b>deleteDisplay</b><br>";
      if ($database->deleteDisplay($displayId))
      {
         echo "deleteDisplay [$displayId] <br>";
      }
   }
   
   // **************************************************************************
   
   echo "<b>getPresentation</b><br>";
   $result = $database->getPresentation(7);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getPresentations</b><br>";
   $result = $database->getPresentations();
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   $presentationInfo = new PresentationInfo();
   $presentationInfo->name = "Production Floor";
   
   echo "<b>new</b><br>";
   if ($database->newPresentation($presentationInfo))
   {
      $presentationId = $database->lastInsertId();
      $presentationInfo = PresentationInfo::load($presentationId);
      echo "newPresentation [$presentationId]<br>";
      var_dump($presentationInfo);
      
      echo "<b>updatePresentation</b><br>";
      $presentationInfo->name = "Managers Office";
      if ($database->updatePresentation($presentationInfo))
      {
         echo "updatePresentation [$presentationId]<br>";
         $presentationInfo = PresentationInfo::load($presentationId);
         var_dump($presentationInfo);
      }
      
      echo "<b>deletePresentation</b><br>";
      if ($database->deletePresentation($presentationId))
      {
         echo "deletePresentation [$presentationId] <br>";
      }
   }
   
   // **************************************************************************
   
   echo "<b>getSlide</b><br>";
   $result = $database->getSlide(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getSlidesForPresentation</b><br>";
   $result = $database->getSlidesForPresentation(7);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   $slideInfo = new SlideInfo();
   $slideInfo->slideType = SlideType::WORKSTATION_SUMMARY_PAGE;
   $slideInfo->duration = 10;
   $slideInfo->enabled = false;
   
   echo "<b>newSlide</b><br>";
   if ($database->newSlide($slideInfo))
   {
      $slideId = $database->lastInsertId();
      $slideInfo = SlideInfo::load($slideId);
      echo "newSlide [$slideId]<br>";
      var_dump($slideInfo);
      
      echo "<b>updateSlide</b><br>";
      $slideInfo->duration = 20;
      if ($database->updateSlide($slideInfo))
      {
         echo "updateSlide [$slideId]<br>";
         $slideInfo = SlideInfo::load($slideId);
         var_dump($slideInfo);
      }
      
      echo "<b>deleteSlide</b><br>";
      if ($database->deleteSlide($slideId))
      {
         echo "deleteSlide [$slideId] <br>";
      }
   }
   
   // **************************************************************************
   
   echo "<b>getStation</b><br>";
   $result = $database->getStation(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getStations</b><br>";
   $result = $database->getStations();
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   $stationInfo = new StationInfo();
   $stationInfo->name = "Welding";
   $stationInfo->label = "WLD";
   
   echo "<b>newStation</b><br>";
   if ($database->newStation($stationInfo))
   {
      $stationId = $database->lastInsertId();
      $stationInfo = StationInfo::load($stationId);
      echo "newStation [$stationId]<br>";
      var_dump($stationInfo);
      
      echo "<b>updateStation</b><br>";
      $stationInfo->name = "Laminating";
      $stationInfo->label = "LAM";
      if ($database->updateStation($stationInfo))
      {
         echo "updateStation [$stationId]<br>";
         $stationInfo = StationInfo::load($stationId);
         var_dump($stationInfo);
      }
      
      echo "<b>deleteStation</b><br>";
      if ($database->deleteStation($stationId))
      {
         echo "deleteStation [$stationId] <br>";
      }
      
      echo "<b>addStation</b><br>";
      $stationInfo->name = "Shipping";
      $stationInfo->label = "SHP";
      if ($database->addStation($stationInfo))
      {
         $stationId = $database->lastInsertId();
         echo "addStation [$stationId]<br>";
         $stationInfo = StationInfo::load($stationId);
         var_dump($stationInfo);
         
         echo "<b>touchStation</b><br>";
         if ($database->touchStation($stationId))
         {
            echo "touchStation [$stationId]<br>";
            $stationInfo = StationInfo::load($stationId);
            var_dump($stationInfo);
         }
         
         $database->deleteStation($stationId);
      }
   }
   
   // **************************************************************************
   
   echo "<b>getCustomer</b><br>";
   $result = $database->getCustomer(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getCustomerFromSubdomain</b><br>";
   $result = $database->getCustomerFromSubdomain("flexscreendet");
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   // **************************************************************************
   
   echo "<b>getCount</b><br>";
   $result = $database->getCount(1, 1, Time::startOfDay(Time::now("Y-m-d H:i:s")), Time::endOfDay(Time::now("Y-m-d H:i:s")));
   echo "getCount [$result]<br>";
   
   echo "<b>updateCount</b><br>";
   $database->updateCount(1, 1, 1);
   $result = $database->getCount(1, 1, Time::startOfDay(Time::now("Y-m-d H:i:s")), Time::endOfDay(Time::now("Y-m-d H:i:s")));   
   echo "updateCount [$result]<br>";
   
   echo "<b>getFirstEntry</b><br>";
   $result = $database->getFirstEntry(1, 1,Time::startOfDay(Time::now("Y-m-d H:i:s")), Time::endOfDay(Time::now("Y-m-d H:i:s")));
   var_dump($result);
   
   echo "<b>getLastEntry</b><br>";
   $result = $database->getLastEntry(1, 1,Time::startOfDay(Time::now("Y-m-d H:i:s")), Time::endOfDay(Time::now("Y-m-d H:i:s")));
   var_dump($result);
   
   echo "<b>getHourlyCounts</b><br>";
   $result = $database->getHourlyCounts(1, 1,Time::startOfDay(Time::now("Y-m-d H:i:s")), Time::endOfDay(Time::now("Y-m-d H:i:s")));
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   // **************************************************************************
   
   echo "<b>getShift</b><br>";
   $result = $database->getShift(1);
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   echo "<b>getShifts</b><br>";
   $result = $database->getShifts();
   foreach ($result as $row)
   {
      var_dump($row);
   }
   
   $shiftInfo = new ShiftInfo();
   $shiftInfo->shiftName = "Second Shift";
   $shiftInfo->startTime = Time::startOfDay(Time::now("H:i"));
   $shiftInfo->endTime = Time::endOfDay(Time::now("H:i"));
   
   echo "<b>newShift</b><br>";
   if ($database->newShift($shiftInfo))
   {
      $shiftId = $database->lastInsertId();
      $shiftInfo = ShiftInfo::load($shiftId);
      echo "newShift [$shiftId]<br>";
      var_dump($shiftInfo);
      
      $shiftInfo->shiftName = "Night Shift";
      if ($database->updateShift($shiftInfo))
      {
         echo "updateShift [$stationId]<br>";
         $shiftInfo = ShiftInfo::load($shiftId);
         var_dump($shiftInfo);
      }
      
      echo "<b>deleteShift</b><br>";
      if ($database->deleteShift($shiftId))
      {
         echo "deleteShift [$shiftId] <br>";
      }
   }
}

// **************************************************************************

echo "<b>getSensor</b><br>";
$result = $database->getSensor(26);
foreach ($result as $row)
{
   var_dump($row);
}

echo "<b>getSensors</b><br>";
$result = $database->getSensors();
foreach ($result as $row)
{
   var_dump($row);
}

echo "<b>getSensorByUid</b><br>";
$result = $database->getSensorByUid("90C5E0");
foreach ($result as $row)
{
   var_dump($row);
}

echo "<b>sensorExists</b><br>";
echo "sensorExists [90C5E0]:" . ($database->sensorExists("90C5E0") ? "true" : "false") . "<br>";

$sensorInfo = new SensorInfo();
$sensorInfo->sensorType = SensorType::COUNTER;
$sensorInfo->name = "Sensee";
$sensorInfo->enabled = true;

echo "<b>newSensor</b><br>";
if ($database->newSensor($sensorInfo))
{
   $sensorId = $database->lastInsertId();
   $sensorInfo = SensorInfo::load($sensorId);
   echo "newSensor [$sensorId]<br>";
   var_dump($sensorInfo);
   
   echo "<b>updateSensor</b><br>";
   $sensorInfo->name = "90C5E0b";
   if ($database->updateSensor($sensorInfo))
   {
      echo "updateSensor [$sensorId]<br>";
      $sensorInfo = SensorInfo::load($sensorId);
      var_dump($sensorInfo);
   }
   
   echo "<b>deleteSensor</b><br>";
   if ($database->deleteSensor($sensorId))
   {
      echo "deleteSensor [$sensorId] <br>";
   }
   
}
   
// **************************************************************************


$globalDatabase = FactoryStatsGlobalDatabase::getInstance();

if ($database->isConnected())
{
   echo "<b>isDisplayRegistered</b><br>";
   $result = $globalDatabase->isDisplayRegistered("DD506F");
   echo "isDisplayRegistered [DD506F]: " . ($result ? "true" : "false") . "<br>";
   
   echo "<b>registerDisplay</b><br>";
   if ($globalDatabase->registerDisplay("PIXMIX"))
   {
      echo "isDisplayRegistered [PIXMIX]: " . ($result ? "true" : "false") . "<br>";
   
      echo "<b>associateDisplayWithSubdomain</b><br>";
      if ($globalDatabase->associateDisplayWithSubdomain("PIXMIX", "flexscreenpgh"))
      {
         echo "<b>getAssociatedSubdomainForDisplay</b><br>";
         $subdomain = $globalDatabase->getAssociatedSubdomainForDisplay("PIXMIX");
         
         echo "getAssociatedSubdomainForDisplay [PIXMIX]: $subdomain<br>";
      }
      
      echo "<b>uregisterDisplay</b><br>";
      if ($globalDatabase->uregisterDisplay("PIXMIX"))
      {
         echo "uregisterDisplay [PIXMIX]: " . ($result ? "true" : "false") . "<br>";
      } 
   }
}

?>