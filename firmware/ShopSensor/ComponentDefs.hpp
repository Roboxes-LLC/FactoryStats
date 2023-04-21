#pragma once

#include "DisplayM5Tough.hpp"
#include "M5Defs.hpp"

// Component names
const String LIMIT_SWITCH = "limitSwitch";
const String BUTTON_A = "buttonA";
const String BUTTON_B = "buttonB";
#ifdef M5TOUGH
const String INCREMENT_BUTTON = DisplayM5Tough::ButtonId[DisplayM5Tough::dbINCREMENT];
const String DECREMENT_BUTTON = DisplayM5Tough::ButtonId[DisplayM5Tough::dbDECREMENT];
const String PAUSE_BUTTON = DisplayM5Tough::ButtonId[DisplayM5Tough::dbPAUSE];
const String ROTATE_BUTTON = DisplayM5Tough::ButtonId[DisplayM5Tough::dbROTATE];
#else
const String INCREMENT_BUTTON = "increment";
const String DECREMENT_BUTTON = "decrement";
const String PAUSE_BUTTON = "pause";
#endif
