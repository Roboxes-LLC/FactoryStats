<?php
class Time
{
   static public function init()
   {
      date_default_timezone_set('America/New_York');
   }
   
   static public function now($format)
   {
      $dateTime = new DateTime();
      $dateTime->setTimezone(new DateTimeZone('America/New_York'));
      
      return ($dateTime->format($format));
   }
   
   static public function toMySqlDate($dateString)
   {
      $dateTime = new DateTime($dateString, new DateTimeZone('America/New_York'));
      $dateTime->setTimezone(new DateTimeZone('UTC'));
      
      return ($dateTime->format("Y-m-d H:i:s"));
   }
   
   static public function fromMySqlDate($dateString, $format)
   {
      $dateTime = new DateTime($dateString, new DateTimeZone('UTC'));
      $dateTime->setTimezone(new DateTimeZone('America/New_York'));
      
      return ($dateTime->format($format));
   }
   
   static public function toJavascriptDate($dateString)
   {
      $dateTime = new DateTime($dateString, new DateTimeZone('America/New_York'));
      
      return ($dateTime->format("Y-m-d"));
   }
   
   
   static public function startOfHour($dateTime)
   {
      $startDateTime = new DateTime($dateTime);
      return ($startDateTime->format("Y-m-d H:00:00"));
   }
   
   static public function endOfHour($dateTime)
   {
      $endDateTime = new DateTime($dateTime);
      $endDateTime->add(new DateInterval("PT1H"));  // period, time, 1 hour
      return ($endDateTime->format("Y-m-d H:00:00"));
   }
   
   static public function startOfDay($dateTime)
   {
      $startDateTime = new DateTime($dateTime);
      return ($startDateTime->format("Y-m-d 00:00:00"));
   }
   
   static public function endOfDay($dateTime)
   {
      $startDateTime = new DateTime($dateTime);
      return ($startDateTime->format("Y-m-d 23:00:00"));
   }
   
   static public function incrementHour($dateTime)
   {
      $incrementedDateTime = new DateTime($dateTime);
      
      $incrementedDateTime->add(new DateInterval("PT1H"));  // period, time, 1 hour
      
      return ($incrementedDateTime->format("Y-m-d H:i:s"));
   }
}

/*
$now = Time::now("Y-m-d H:i:s");
$toMySql = Time::toMySqlDate($now);
$fromMySql = Time::fromMySqlDate($toMySql, "Y-m-d H:i:s");
echo "now: $now";
echo "<br/>";
echo "toMySql: $toMySql";
echo "<br/>";
echo "fromMySql: $fromMySql";
*/
?>