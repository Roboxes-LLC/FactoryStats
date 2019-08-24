<?php

require_once 'database.php';

class BreakDescription
{
   const UNKNOWN_DESCRIPTION_ID = 0;
   
   public $breakDescriptionId = BreakDescription::UNKNOWN_DESCRIPTION_ID;
   public $code;
   public $description;
   
   public static function load($breakDescriptionId)
   {
      $breakDescription = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getBreakDescription($breakDescriptionId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $breakDescription = new BreakDescription();
            
            $breakDescription->breakDescriptionId = intval($row['breakDescriptionId']);
            $breakDescription->code = $row['code'];
            $breakDescription->description = $row['description'];
         }
      }
      
      return ($breakDescription);
   }
   
   public static function getBreakDescriptionOptions($selectedDescriptionId)
   {
      $html = ""; 

      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getBreakDescriptions();
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $descriptionId = intval($row["breakDescriptionId"]);
            $description = $row["description"];
            $selected = ($descriptionId == $selectedDescriptionId) ? "selected" : "";
            
            $html .= "<option value=\"$descriptionId\" $selected>$description</option>";
         }
      }
      
      return ($html);
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