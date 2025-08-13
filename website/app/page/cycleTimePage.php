<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/app/page/page.php';
require_once ROOT.'/core/common/csvFile.php';

class CycleTimePage extends Page
{
   public function handleRequest($params)
   {
      if ($this->authenticate([]))
      {
         switch ($this->getRequest($params))
         {
            case "fetch_cycle_times":
            {
               if (Page::requireParams($params, ["stationId", "shiftId", "date"]))
               {
                  $stationId = $params->getInt("stationId");
                  $shiftId = $params->getInt("shiftId");
                  $date = $params->get("date");
                  
                  if ($shiftId == ShiftInfo::UNKNOWN_SHIFT_ID)
                  {
                     $this->error("Invalid shift");
                  }
                  else if ($stationId == StationInfo::UNKNOWN_STATION_ID)
                  {
                     $this->error("Invalid station");
                  }
                  else
                  {
                     $shiftTimes = ShiftInfo::load($shiftId)->getShiftTimes($date);
                     
                     $startDateTime = $shiftTimes->startDateTime;
                     $endDateTime = $shiftTimes->endDateTime;
                     
                     $this->result->success = true;
   
                     $this->result->stationId = $stationId;
                     $this->result->shiftId = $shiftId;
                     $this->result->date = Time::getDateTime($params->get("startTime"))->format("m-d-Y");
                     $this->result->startTime = $startDateTime;
                     $this->result->endTime = $endDateTime;
                     
                     // Google Chart axis labels.
                     $this->result->columns = [['type' => 'date',   'label' => "Time"],
                                               ['type' => 'number', 'label'=> "Cycle Time"]];
   
                     // Google chart x-axis min/max.
                     $this->result->range = new stdClass();
                     $this->result->range->min = $startDateTime;
                     $this->result->range->max = $endDateTime;
                     
                     $this->result->rows = CountManager::getCycleTimeChartData($stationId, $shiftId, $startDateTime, $endDateTime, 28800);  // Max time of 8 hours.
                  }
               }
               break;
            }
            
            case "download_csv":
            {
               if (Page::requireParams($params, ["stationId", "shiftId", "date"]))
               {
                  $stationId = $params->getInt("stationId");
                  $shiftId = $params->getInt("shiftId");
                  $date = $params->get("date");
                  
                  if ($shiftId == ShiftInfo::UNKNOWN_SHIFT_ID)
                  {
                     $this->error("Invalid shift");
                  }
                  else if ($stationId == StationInfo::UNKNOWN_STATION_ID)
                  {
                     $this->error("Invalid station");
                  }
                  else
                  {
                     $filename = "CycleTime_" . Time::now("mdY_His") . ".csv";
                     $filepath = ROOT."/temp/".$filename;
                     
                     if ($this->createDataFile($stationId, $shiftId, $date, 28800, $filepath))
                     {
                        /*
                        if (file_exists($filename)) 
                        {
                           // Set appropriate headers for file download
                           header('Content-Description: File Transfer');
                           header('Content-Type: application/octet-stream'); // Generic binary file type
                           header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                           header('Expires: 0');
                           header('Cache-Control: must-revalidate');
                           header('Pragma: public');
                           header('Content-Length: ' . filesize($filename));
                           
                           // Clear output buffer (if any) to prevent issues with headers
                           ob_clean();
                           flush();
                           
                           // Read the file and output its contents to the browser
                           readfile($filename);
                        }
                        else
                        {
                           $this->error("Download error.");
                        }
                        */
                        
                        $this->result->success = true;
                        $this->result->filename = $filename;
                        $this->result->url = "/temp/".$filename;
                     }
                     else
                     {
                        $this->error("File creation error.");
                     }
                  }
               }
               break;
            }                  
            
            default:
            {
               $this->error("Unsupported request: " . $this->getRequest($params));
               break;
            }
         }
      }
      
      echo json_encode($this->result);
   }
   
   private function createDataFile($stationId, $shiftId, $date, $maxCycleTime, $filename)
   {
      $stationName = StationInfo::load($stationId)->label;
      
      $shiftInfo = ShiftInfo::load($shiftId);
      $shiftName = $shiftInfo->shiftName;
      $shiftTimes = $shiftInfo->getShiftTimes($date);
      
      $startDateTime = $shiftTimes->startDateTime;
      $endDateTime = $shiftTimes->endDateTime;
      
      $data = CountManager::getCycleTimeChartData($stationId, $shiftId, $startDateTime, $endDateTime, 28800);  // Max time of 8 hours.
      
      $csvFile = new CsvFile();
      $csvFile->setHeadings(["Timestamp", "Station", "Shift", "Cycle Time"]);
      
      foreach ($data as $dateTime => $cycleTime)
      {
         $csvFile->addRow([$dateTime, $stationName, $shiftName, $cycleTime]);
      }
      
      return (CsvFile::save($csvFile, $filename));
   }
}

?>