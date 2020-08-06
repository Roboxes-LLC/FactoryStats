<?php

abstract class States
{
   const UNKNOWN        = 0;
   const FIRST          = 1;
   const ALABAMA = States::FIRST;
   const ALASKA         = 2;
   const ARIZONA        = 3;
   const ARKANSAS       = 4;
   const CALIFORNIA     = 5;
   const COLORADO       = 6;
   const CONNECTICUT    = 7;
   const DELAWARE       = 8;
   const DISTRICT_OF_COLUMBIA = 9;
   const FLORIDA        = 10;
   const GEORGIA        = 11;
   const HAWAII         = 12;
   const IDAHO          = 13;
   const ILLINOIS       = 14;
   const INDIANA        = 15;
   const IOWA           = 16;
   const KANSAS         = 17;
   const KENTUCKY       = 18;
   const LOUISIANA      = 19;
   const MAINE          = 20;
   const MARYLAND       = 21;
   const MASSACHUSETTS  = 22;
   const MICHIGAN       = 23;
   const MINNESOTA      = 24;
   const MISSISSIPPI    = 25;
   const MISSOURI       = 26;
   const MONTANA        = 27;
   const NEBRASKA       = 28;
   const NEVADA         = 29;
   const NEW_HAMPSHIRE  = 30;
   const NEW_JERSEY     = 31;
   const NEW_MEXICO     = 32;
   const NEW_YORK       = 33;
   const NORTH_CAROLINA = 34;
   const NORTH_DAKOTA   = 35;
   const OHIO           = 36;
   const OKLAHOMA       = 37;
   const OREGON         = 38;
   const PENNSYLVANIA   = 39;
   const RHODE_ISLAND   = 40;
   const SOUTH_CAROLINA = 41;
   const SOUTH_DAKOTA   = 42;
   const TENNESSEE      = 43;
   const TEXAS          = 44;
   const UTAH           = 45;
   const VERMONT        = 46;
   const VIRGINIA       = 47;
   const WASHINGTON     = 48;
   const WEST_VIRGINIA  = 49;
   const WISCONSIN      = 50;
   const WYOMING        = 51;
   const LAST           = 52;
   const COUNT = States::LAST - States::FIRST;
      
   public static function getStateName($stateId)
   {
      $names = array(
         "",
         "Alabama",
         "Alaska",
         "Arizona",
         "Arkansas",
         "California",
         "Colorado",
         "Connecticut",
         "Delaware",
         "District of Columbia",
         "Florida",
         "Georgia",
         "Hawaii",
         "Idaho",
         "Illinois",
         "Indiana",
         "Iowa",
         "Kansas",
         "Kentucky",
         "Louisiana",
         "Maine",
         "Maryland",
         "Massachusetts",
         "Michigan",
         "Minnesota",
         "Mississippi",
         "Missouri",
         "Montana",
         "Nebraska",
         "Nevada",
         "New Hampshire",
         "New Jersey",
         "New Mexico",
         "New York",
         "North Carolina",
         "North Dakota",
         "Ohio",
         "Oklahoma",
         "Oregon",
         "Pennsylvania",
         "Rhode Island",
         "South Carolina",
         "South Dakota",
         "Tennessee",
         "Texas",
         "Utah",
         "Vermont",
         "Virginia",
         "Washington",
         "West Virginia",
         "Wisconsin",
         "Wyoming");
      
      return ($names[$stateId]);
   }
   
   public static function getAbbreviation($stateId)
   {
      $abbreviations = array(
         "",
         "AL",
         "AK",
         "AR",
         "AS",
         "CA",
         "CO",
         "CT",
         "DE",
         "DC",
         "FL",
         "GA",
         "HI",
         "ID",
         "IL",
         "IN",
         "IA",         
         "KS",
         "KY",
         "LA",
         "ME",
         "MD",
         "MA",
         "MI",
         "MN",
         "MS",
         "MO",
         "MT",
         "NE",
         "NV",
         "NH",
         "NJ",
         "NM",
         "NY",         
         "NC",
         "ND",
         "OH",
         "OK",
         "OR",
         "PA",
         "RI",
         "SC",
         "SD",
         "TN",
         "TX",
         "UT",
         "VT",
         "VA",
         "WA",
         "WV",
         "WI",
         "WY");
      
      return ($abbreviations[$stateId]);
   }
}

/*
for ($i = States::FIRST; $i < States::LAST; $i++)
{
   echo States::getStateName($i) . ", " . States::getAbbreviation($i) . "<br>";
}
*/

?>