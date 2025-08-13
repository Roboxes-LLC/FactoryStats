<?php

class Time
{
   public const DEFAULT_TIME_ZONE = "America/New_York";
   
   public const STANDARD_FORMAT = "Y-m-d H:i:s";
   
   static public function init($timeZone)
   {
      Time::$timeZone = $timeZone;
      
      date_default_timezone_set(Time::$timeZone);
   }
   
   static public function now($format = Time::STANDARD_FORMAT)
   {
      $dateTime = new DateTime();
      $dateTime->setTimezone(new DateTimeZone(Time::$timeZone));
      
      return ($dateTime->format($format));
   }
   
   public static function getDateTime($dateTimeString)
   {
      return  (new DateTime($dateTimeString, new DateTimeZone(Time::$timeZone)));
   }
   
   static public function toMySqlDate($dateString, $format = Time::STANDARD_FORMAT)
   {
      $dateTime = new DateTime($dateString, new DateTimeZone(Time::$timeZone));
      $dateTime->setTimezone(new DateTimeZone('UTC'));
      
      return ($dateTime->format($format));
   }
   
   static public function fromMySqlDate($dateString, $format)
   {
      $dateTime = new DateTime($dateString, new DateTimeZone('UTC'));
      $dateTime->setTimezone(new DateTimeZone(Time::$timeZone));
      
      return ($dateTime->format($format));
   }
   
   static public function toJavascriptDate($dateString)
   {
      $dateTime = new DateTime($dateString, new DateTimeZone(Time::$timeZone));
      
      return ($dateTime->format("Y-m-d"));
   }
   
   
   static public function startOfHour($dateTime)
   {
      $startDateTime = new DateTime($dateTime);
      $startDateTime->setTimezone(new DateTimeZone(Time::$timeZone));
      
      return ($startDateTime->format("Y-m-d H:00:00"));
   }
   
   static public function endOfHour($dateTime)
   {
      $endDateTime = new DateTime($dateTime);
      $endDateTime->setTimezone(new DateTimeZone(Time::$timeZone));
      
      $endDateTime->add(new DateInterval("PT1H"));  // period, time, 1 hour
      return ($endDateTime->format("Y-m-d H:00:00"));
   }
   
   static public function startOfDay($dateTime)
   {
      $startDateTime = new DateTime($dateTime, new DateTimeZone(Time::$timeZone));
      
      return ($startDateTime->format("Y-m-d 00:00:00"));
   }
   
   static public function midDay($dateTime)
   {
      $midDateTime = new DateTime($dateTime, new DateTimeZone(Time::$timeZone));
       
      return ($midDateTime->format("Y-m-d 12:00:00"));
   }
   
   static public function endOfDay($dateTime)
   {
      $endDateTime = new DateTime($dateTime, new DateTimeZone(Time::$timeZone));
      
      return ($endDateTime->format("Y-m-d 23:59:59"));
   }
   
   static public function incrementHour($dateTime, $hours = 1)
   {
      $incrementedDateTime = new DateTime($dateTime);
      
      $incrementedDateTime->add(new DateInterval("PT{$hours}H"));  // period, time, 1 hour
      
      return ($incrementedDateTime->format(Time::STANDARD_FORMAT));
   }
   
   static public function incrementDay($dateTime)
   {
      $incrementedDateTime = new DateTime($dateTime);
      
      $incrementedDateTime->add(new DateInterval("P1D"));  // period, 1 day
      
      return ($incrementedDateTime->format(Time::STANDARD_FORMAT));
   }
   
   static public function decrementDay($dateTime)
   {
       $decrementedDateTime = new DateTime($dateTime);
       
       $decrementedDateTime->sub(new DateInterval("P1D"));  // period, 1 day
       
       return ($decrementedDateTime->format(Time::STANDARD_FORMAT));
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
      $now = Time::now();
      
      return (Time::between($dateTime, Time::startOfDay($now), Time::endOfDay($now)));
   }
   
   private static $timeZone = Time::DEFAULT_TIME_ZONE;
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
