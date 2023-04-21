#pragma once

static const String UNKNOWN_BREAK_CODE = "";

static const int UNKNOWN_BREAK_ID = 0;

static const int UNKNOWN_STATION_ID = 0;

static const String UNKNOWN_STATION_LABEL = "";

static const String SENSOR_UPDATE_MESSAGE_ID = "sensor";

static const String BREAK_DESCRIPTIONS_REQUEST_MESSAGE_ID = "breakDescriptions";

inline static String getRequestUrl(
   const String& server,
   const String& apiMessageId)
{
   String url = "";

   if (server != "")
   {
      url = server + "/api/" + apiMessageId + "/";
   }

   return (url);
}
