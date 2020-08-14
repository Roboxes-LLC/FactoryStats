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
      $startDateTime->setTimezone(new DateTimeZone('America/New_York'));
      
      return ($startDateTime->format("Y-m-d H:00:00"));
   }
   
   static public function endOfHour($dateTime)
   {
      $endDateTime = new DateTime($dateTime);
      $endDateTime->setTimezone(new DateTimeZone('America/New_York'));
      
      $endDateTime->add(new DateInterval("PT1H"));  // period, time, 1 hour
      return ($endDateTime->format("Y-m-d H:00:00"));
   }
   
   static public function startOfDay($dateTime)
   {
      $startDateTime = new DateTime($dateTime);
      $startDateTime->setTimezone(new DateTimeZone('America/New_York'));
      
      return ($startDateTime->format("Y-m-d 00:00:00"));
   }
   
   static public function midDay($dateTime)
   {
       $midDateTime = new DateTime($dateTime);
       $midDateTime->setTimezone(new DateTimeZone('America/New_York'));
       
       return ($midDateTime->format("Y-m-d 12:00:00"));
   }
   
   static public function endOfDay($dateTime)
   {
      $endDateTime = new DateTime($dateTime);
      $endDateTime->setTimezone(new DateTimeZone('America/New_York'));
      
      return ($endDateTime->format("Y-m-d 23:59:59"));
   }
   
   static public function incrementHour($dateTime)
   {
      $incrementedDateTime = new DateTime($dateTime);
      
      $incrementedDateTime->add(new DateInterval("PT1H"));  // period, time, 1 hour
      
      return ($incrementedDateTime->format("Y-m-d H:i:s"));
   }
   
   static public function incrementDay($dateTime)
   {
      $incrementedDateTime = new DateTime($dateTime);
      
      $incrementedDateTime->add(new DateInterval("P1D"));  // period, 1 day
      
      return ($incrementedDateTime->format("Y-m-d H:i:s"));
   }
   
   static public function differenceSeconds($startTime, $endTime)
   {
      $startDateTime = new DateTime($startTime);
      $endDateTime = new DateTime($endTime);
      
      $diff = $startDateTime->diff($endDateTime);
      
      // Convert to seconds.
      $seconds = (($diff->d * 12 * 60 * 60) + ($diff->h * 60 * 60) + ($diff->i * 60) + $diff->s);
      
      return ($seconds);
   }
   
   static public function between($dateTime, $startDateTime, $endDateTime)
   {
      return ((new DateTime($dateTime) >= new DateTime($startDateTime)) &&
              (new DateTime($dateTime) <= new DateTime($endDateTime)));
   }
   
   static public function isToday($dateTime)
   {
      $now = Time::now("Y-m-d H:i:s");
      
      return (Time::between($dateTime, Time::startOfDay($now), Time::endOfDay($now)));
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
echo "<br/>";
echo "startOfDay: " . Time::startOfDay($now);
echo "<br/>";
echo "endOfDay: " . Time::endOfDay($now);
echo "<br/>";
echo "startOfHour: " . Time::startOfHour($now);
echo "<br/>";
echo "endOfHour: " . Time::endOfHour($now);
*/
?>