#pragma once

// Uncomment to build for M5Stick-C or M5Stick-C Plus
#define M5STICKC
//#define M5STICKC_PLUS

#if defined(M5STICKC)
#include <M5StickC.h>
#elif defined(M5STICKC_PLUS)
#include <M5StickCPlus.h>
#endif
