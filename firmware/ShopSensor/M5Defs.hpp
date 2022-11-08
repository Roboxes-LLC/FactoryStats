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
