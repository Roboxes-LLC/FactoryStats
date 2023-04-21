#include <stdio.h>
#include <stdbool.h>
#include <unistd.h>

#include "Arduino.h"
#include "Connection/ConnectionManager.cpp"
#include "Robox.hpp"

#include "BreakManager.hpp"
#include "ConfigPage.hpp"
#include "Diagnostics.hpp"
#include "DisplayM5Tough.hpp"
#include "IncrementButton.hpp"
#include "Power.hpp"
#include "ShopSensor.hpp"

void setup()
{
   Robox::setup();
}

void loop()
{
   Robox::loop();
}
