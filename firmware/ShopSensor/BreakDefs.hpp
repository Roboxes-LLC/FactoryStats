#pragma once

#include "FactoryStatsDefs.hpp"

static const int NO_BREAK_ID = UNKNOWN_BREAK_ID;

static const String NO_BREAK_CODE = UNKNOWN_BREAK_CODE;

static const String NO_PENDING_BREAK_CODE = "NO_CODE";

static const int BASE_BREAK_BUTTON_ID = 100;

// Note: This value is arbitrarily small due to a literal value
//       found in M5Button.cpp, Button::Button(), line 18.
static const int BREAK_BUTTON_LABEL_LENGTH = 14;
