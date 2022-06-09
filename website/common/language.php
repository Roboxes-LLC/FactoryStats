<?php

require_once 'params.php';
require_once 'root.php';

abstract class LanguageType
{
   const UNKNOWN = 0; 
   const FIRST = 1;
   const ENGLISH = LanguageType::FIRST;
   const SPANISH = 2;
   const BASIC = 3;
   const LAST = 4;
   const COUNT = LanguageType::LAST - LanguageType::FIRST;
   const DEFAULT = LanguageType::ENGLISH;
   
   public static $values = array(LanguageType::ENGLISH, LanguageType::SPANISH, LANGUAGE::BASIC);
   
   public static function getLabel($languageType)
   {
      $labels = array("", "English", "Spanish", "Basic");
      
      return ($labels[$languageType]);
   }
   
   public static function getFile($languageType)
   {
      return ("lang_" . strtolower(LanguageType::getLabel($languageType)) . ".json");
   }
}

class Language
{
   const UNDEFINED = "<undefined>";
   
   private static $dictionaries = array();
   
   public static function setLanguage($languageType)
   {
      $_SESSION["language"] = $languageType;
   }
   
   public static function getLanguage()
   {
      if (!isset($_SESSION["language"]))
      {
         $_SESSION["language"] = LanguageType::DEFAULT;
      }
      
      return ($_SESSION["language"]);
   }
   
   public static function get($key)
   {
      $value = Language::UNDEFINED;
      
      $languageType = Language::getLanguage();
      
      $dictionary = Language::getDictionary($languageType);
      
      if ($dictionary && isset($dictionary[$key]))
      {
         $value = $dictionary[$key];  
      }
         
      return ($value);
   }
   
   public static function export($languageType, $filename)
   {
      if (file_exists($filename))
      {
         unlink($filename);
      }
      
      $dictionary = Language::getDictionary($languageType);
      
      foreach ($dictionary as $key => $value)
      {
         $exportString = $value . "\r\n";
         file_put_contents($filename, $exportString, FILE_APPEND | LOCK_EX); 
      }
   }
   
   public static function import($languageType, $filename)
   {
      $fromDictionary = Language::getDictionary(LanguageType::DEFAULT);
      $toDictionary = array();
      
      $lines = file($filename);
      
      $index = 0;
      foreach ($fromDictionary as $key => $value)
      {
         $line = str_replace(array("\r", "\n"), '', $lines[$index++]);
         $toDictionary[$key] = $line;
      }
      
      $destFile = "language/" . LanguageType::getFile($languageType);
      
      file_put_contents($destFile, json_encode($toDictionary)); 
   }
   
   private static function getDictionary($languageType)
   {
      global $ROOT;  // TODO: Make use of
      
      if (!isset(Language::$dictionaries[$languageType]))
      {
         // Language file.
         $file = "language/" . LanguageType::getFile($languageType);
         
         // Read the JSON file.
         $json = file_get_contents($file);
         
         // Store dictionary.
         Language::$dictionaries[$languageType] = json_decode($json, true);
      }
      
      return (Language::$dictionaries[$languageType]);
   }
}

// Global function for nicer syntax.
function LANG($key)
{
   return (Language::get($key));
}

/*
session_start();
Language::setLanguage(LanguageType::SPANISH);
echo LANG("_USERNAME") . ", " . LANG("_PASSWORD");
*/

//Language::export(LanguageType::ENGLISH, "spanish.txt");
//Language::import(LanguageType::SPANISH, "spanish.txt");