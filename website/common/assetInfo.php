<?php

require_once 'barcode.php';
require_once 'stationInfo.php';

class AssetInfo
{
   const UNKNOWN_ASSET_ID = 0;
   
   const UNKNOWN_ORDER = "";
   
   const UNKNOWN_SCHEDULE = 0;
   
   const UNKNOWN_SEQUENCE = 0;
   
   public $assetId;
   public $order;
   public $schedule;
   public $sequence;
   public $stationId;
   public $checkInDateTime;
   
   public function __construct()
   {
      $this->assetId = AssetInfo::UNKNOWN_ASSET_ID;
      $this->order = AssetInfo::UNKNOWN_ORDER;
      $this->schedule = AssetInfo::UNKNOWN_SCHEDULE;
      $this->sequence = AssetInfo::UNKNOWN_SEQUENCE;
      $this->stationId = StationInfo::UNKNOWN_STATION_ID;
      $this->checkInDateTime = null;
   }
   
   public static function load($assetId)
   {
      $assetInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getAsset($assetId);
         
         if ($result && ($row = $result[0]))
         {
            $assetInfo= new AssetInfo();
            
            $assetInfo->initialize($row);
         }
      }
      
      return ($assetInfo);
   }
   
   public static function loadFromBarcode($encodedBarcode)
   {
      $assetInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getAssetFromBarcode($encodedBarcode);
         
         if ($result && ($row = $result[0]))
         {
            $assetInfo = new AssetInfo();
            
            $assetInfo->initialize($row);
         }
      }
      
      return ($assetInfo);
   }
   
   public function checkIn($stationId)
   {
      $success = false;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $success = $database->checkInAsset($this->assetId, $stationId);
      }
      
      return ($success);
   }
   
   public function getBarcode()
   {
      $barcode = new Barcode($this->order, $this->schedule, $this->sequence);
      
      return ($barcode->encode());      
   }
   
   private function initialize($row)
   {
      $this->assetId = intval($row['assetId']);
      $this->order = $row['orderId'];
      $this->schedule = intval($row['schedule']);
      $this->sequence = intval($row['sequence']);
      $this->stationId = intval($row['stationId']);
      
      if ($row['checkInDateTime'])
      {
         $this->checkInDateTime = Time::fromMySqlDate($row['checkInDateTime'], "Y-m-d H:i:s");
      }
   }
}
?>