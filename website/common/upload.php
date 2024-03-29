<?php

if (!defined('ROOT')) require_once '../root.php';

abstract class UploadStatus
{
   const FIRST         = 0;
   const UPLOADED      = UploadStatus::FIRST;
   const BAD_FILE_TYPE = 1;
   const BAD_FILE_SIZE = 2;
   const FILE_ERROR    = 3;
   const LAST          = 4;
   
   static function toString($uploadStatus)
   {
      $strings = array("UPLOADED", "BAD_FILE_TYPE", "BAD_FILE_SIZE", "FILE_ERROR");
      
      $stringVal = "UNKNOWN";
      
      if (($uploadStatus >= UploadStatus::FIRST) && ($uploadStatus < UploadStatus::LAST))
      {
         $stringVal = $strings[$uploadStatus];
      }
      
      return ($stringVal);
   }
}

class Upload
{
   static function uploadSlideImage($file)
   {
      $returnStatus = UploadStatus::UPLOADED;
      
      $target = CustomerInfo::getSlideImagesUploadFolder() . "/" . basename($file["name"]);
      
      if (!(Upload::validateFileFormat($file, array("jpg", "png", "bmp", "gif"))))
      {
         $returnStatus = UploadStatus::BAD_FILE_TYPE;
      }
      else if (!Upload::validateFileSize($file, 5000000))  // 5MB
      {
         $returnStatus = UploadStatus::BAD_FILE_SIZE;
      }
      else if (!move_uploaded_file($file["tmp_name"], $target))
      {
         $returnStatus = UploadStatus::FILE_ERROR;
      }
      
      return ($returnStatus);
   }
   
   static function validateFileSize($file, $maxSize)
   {
      return ($file["size"] < $maxSize);
   }
   
   static function validateFileFormat($file, $extensions)
   {
      $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
      
      return (in_array($extension, $extensions));
   }
}