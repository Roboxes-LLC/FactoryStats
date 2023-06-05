<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/permissions.php';

class Role
{
   const UNKNOWN     = 0;
   const FIRST       = 1;
   const SUPER_USER  = Role::FIRST;
   const ADMIN       = 2;
   const OPERATOR    = 3;
   const LAST        = 4;
   
   public $roleId;
   
   public $roleName;
   
   public $defaultPermissions;
      
   public static function getRoles()
   {
      if (Role::$roles == null)
      {
         Role::$roles = 
            array(new Role(Role::SUPER_USER,  "Super User",  Permission::ALL_PERMISSIONS),
                  new Role(Role::ADMIN,       "Admin",       Permission::ALL_PERMISSIONS),
                  new Role(Role::OPERATOR,    "Operator",    Permission::getBits(Permission::WORKSTATION_SUMMARY, Permission::WORKSTATION, Permission::UPDATE_COUNT)),
            );
      }
      
      return (Role::$roles);
   }
   
   public static function getRole($roleId)
   {
      $role = new Role(Role::UNKNOWN, "", Permission::NO_PERMISSIONS);
      
      if (($roleId >= Role::FIRST) && ($roleId <= Role::LAST))
      {
         $role = Role::getRoles()[$roleId - Role::FIRST];
      }
      
      return ($role);
   }
   
   public function hasPermission($permissionId)
   {
      $permission = Permission::getPermission($permissionId);
      
      return ($permission->isSetIn($this->defaultPermissions));
   }
   
   private static $roles = null;
      
   private function __construct($roleId, $roleName, $defaultPermissions)
   {
      $this->roleId = $roleId;
      $this->roleName = $roleName;
      $this->defaultPermissions = $defaultPermissions;
   }
}

/*
$role = Role::getRole(Role::OPERATOR);

echo $role->roleName . ": <br>";

foreach (Permission::getPermissions() as $permission)
{
   $isSet = $permission->isSetIn($role->defaultPermissions) ? "set" : "";
   echo "{$permission->permissionName}: $isSet<br/>";
}
*/

?>
