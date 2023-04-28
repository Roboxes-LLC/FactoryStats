<?php

require_once 'database.php';

class BreakDescription
{
   const UNKNOWN_DESCRIPTION_ID = 0;
   
   const UNKNOWN_CODE = "";
   
   public $breakDescriptionId = BreakDescription::UNKNOWN_DESCRIPTION_ID;
   public $code;
   public $description;
   
   public static function load($breakDescriptionId)
   {
      $breakDescription = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getBreakDescription($breakDescriptionId);
         
         if ($result && ($row = $result[0]))
         {
            $breakDescription = new BreakDescription();

            $breakDescription->initialize($row);
         }
      }
      
      return ($breakDescription);
   }
   
   public static function getBreakDescriptionFromCode($breakCode)
   {
      $breakDescription = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getBreakDescriptionFromCode($breakCode);
         
         if ($result && ($row = $result[0]))
         {
            $breakDescription = new BreakDescription();
            
            $breakDescription->initialize($row);
         }
      }         
      
      return ($breakDescription);
   }
   
   public static function getBreakDescriptionOptions($selectedDescriptionId)
   {
      $html = ""; 

      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getBreakDescriptions();
         
         foreach ($result as $row)
         {
            $descriptionId = intval($row["breakDescriptionId"]);
            $description = $row["description"];
            $selected = ($descriptionId == $selectedDescriptionId) ? "selected" : "";
            
            $html .= "<option value=\"$descriptionId\" $selected>$description</option>";
         }
      }
      
      return ($html);
   }
   
   public function initialize($row)
   {
      $this->breakDescriptionId = intval($row['breakDescriptionId']);
      $this->code = $row['code'];
      $this->description = $row['description'];
   }
}

/*
if (isset($_GET["breakDescriptionId"]))
{
   $breakDescriptionId = $_GET["breakDescriptionId"];
   $breakDescription = BreakDescription::load($breakDescriptionId);
   
   if ($breakDescription)
   {
      echo "breakDescriptionId: " . $breakDescription->$breakDescriptionId . "<br/>";
      echo "code: " .               $breakDescription->code .                "<br/>";
      echo "description: " .        $breakDescription->description .         "<br/>";
   }
   else
   {
      echo "No break description found.";
   }
}
*/

?>