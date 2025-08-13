<?php

class Permission
{
   const UNKNOWN             = 0;
   const FIRST               = 1;
   const WORKSTATION_SUMMARY = Permission::FIRST;
   const WORKSTATION         = 2;
   const PRODUCTION_HISTORY  = 3;
   const CUSTOMER_CONFIG     = 4;   
   const USER_CONFIG         = 5;   
   const STATION_CONFIG      = 6;
   const BUTTON_CONFIG       = 7;
   const DISPLAY_CONFIG      = 8;
   const BREAK_CONFIG        = 9;
   const UPDATE_COUNT        = 10;
   const PRESENTATION_CONFIG = 11;
   const SENSOR_CONFIG       = 11;
   const CYCLE_TIME          = 17;
   const LAST                = 18;
   
   const NO_PERMISSIONS = 0x0000;
   const ALL_PERMISSIONS = 0xFFFF;
   
   public $permissionId;
   
   public $permissionName;
   
   public $bits;
   
   public static function getPermissions()
   {
      if (Permission::$permissions == null)
      {
         Permission::$permissions =
            array(Permission::WORKSTATION_SUMMARY => new Permission(Permission::WORKSTATION_SUMMARY, "Workstation Summary"),
                  Permission::WORKSTATION =>         new Permission(Permission::WORKSTATION,         "Workstation"),
                  Permission::PRODUCTION_HISTORY =>  new Permission(Permission::PRODUCTION_HISTORY,  "Production History"),
                  Permission::CUSTOMER_CONFIG =>     new Permission(Permission::CUSTOMER_CONFIG,     "Customer Config"),
                  Permission::USER_CONFIG =>         new Permission(Permission::USER_CONFIG,         "User Config"),
                  Permission::STATION_CONFIG =>      new Permission(Permission::STATION_CONFIG,      "Station Config"),
                  Permission::BUTTON_CONFIG =>       new Permission(Permission::BUTTON_CONFIG,       "Hardware Button Config"),
                  Permission::DISPLAY_CONFIG =>      new Permission(Permission::DISPLAY_CONFIG,      "Display Config"),
                  Permission::BREAK_CONFIG =>        new Permission(Permission::BREAK_CONFIG,        "Break Config"),
                  Permission::UPDATE_COUNT =>        new Permission(Permission::UPDATE_COUNT,        "Can update product counts"),
                  Permission::PRESENTATION_CONFIG => new Permission(Permission::PRESENTATION_CONFIG, "Can create presentations"),
                  Permission::SENSOR_CONFIG =>       new Permission(Permission::SENSOR_CONFIG,       "Sensor Config"),
                  Permission::CYCLE_TIME =>          new Permission(Permission::CYCLE_TIME,          "Cycle Time")
            );
      }
      
      return (Permission::$permissions);
   }
   
   public static function getPermission($permissionId)
   {
      $permission = new Permission(Permission::UNKNOWN, "");
      
      if (($permissionId >= Permission::FIRST) && ($permissionId < Permission::LAST))
      {
         $permission = Permission::getPermissions()[$permissionId];
      }
      
      return ($permission);
   }
   
   public function isSetIn($mask)
   {
      return (($this->bits & $mask) > 0);
   }
   
   public static function getBits(...$permissionIds)
   {
      $bits = Permission::NO_PERMISSIONS;
      
      foreach ($permissionIds as $permissionId)
      {
         $bits |=  Permission::getPermission($permissionId)->bits;
      }
      
      return ($bits);
   }
   
   private static $permissions = null;
   
   private function __construct($permissionId, $permissionName)
   {
      $this->permissionId = $permissionId;
      $this->permissionName = $permissionName;
      
      if ($permissionId > Permission::UNKNOWN)
      {
         $this->bits = (1 << ($permissionId - Permission::FIRST));
      }
      else
      {
         $this->bits = 0;
      }
   }
}

?>