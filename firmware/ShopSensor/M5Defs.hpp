#pragma once

// Uncomment to build for M5 devices
//#define M5STICKC
//#define M5STICKC_PLUS
#define M5TOUGH

#if defined(M5STICKC)
#include <M5StickC.h>
#elif defined(M5STICKC_PLUS)
#include <M5StickCPlus.h>
#elif defined(M5TOUGH)
#include <M5Tough.h>
#endif

#ifndef M5TOUGH
struct Point
{
   inline Point() :
      x(0),
      y(0)
   {
   }

   inline Point(const int& x, const int& y) :
      x(x),
      y(y)
   {
   }
   
   int x;
   int y;  
};

struct Zone
{
   inline Zone() :
      x(0),
      y(0),
      w(0),
      h(0)
   {
   }

   inline Zone(const int& x, const int& y, const int& w, const int& h) :
      x(x),
      y(y),
      w(w),
      h(h)
   {
   }
   
   int x;
   int y;  
   int w;
   int h;
};
#endif

enum Rotation
{
   CW_FIRST,
   CW_0 = CW_FIRST,  // 0
   CW_90,   // 1
   CW_180,  // 2
   CW_270,  // 3
   CW_LAST = CW_270,
   CCW_FIRST,
   CCW_0 = CCW_FIRST,  // 4
   CCW_90,   // 5
   CCW_180,  // 6
   CCW_270,  // 7
   CCW_LAST = CCW_270
};
