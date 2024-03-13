#pragma once

#include <Robox.h>

// Uncomment these #defines to include only the components required for your project.

// Adapter

//#define BLE_SERVER_ADAPTER  // Requires BLE Arduino library
#define HTTP_CLIENT_ADAPTER
//#define MQTT_CLIENT_ADAPTER  // Requires pubsubclient Ardiuno library
#define SERIAL_ADAPTER
//#define TCP_CLIENT_ADAPTER
//#define TCP_SERVER_ADAPTER
//#define UDP_ADAPTER
//#define WEB_SERVER_ADAPTER
//#define WEB_SOCKET_ADAPTER  // Requires Esp8266-Websocket/Esp32-Websocket Arduino library

// Behavior

// Components
//#define ESCAPE_BEHAVIOR
//#define SCOUT_BEHAVIOR
//#define SERVO_PAN_BEHAVIOR  // Requires ServoComponent

// Component

#define BUTTON
//#define BUZZER
//#define DISTANCE_SENSOR  // Requires NewPing Ardiuno library
#define DOOR
//#define FACTORY_RESET_BUTTON
#define HALL_SENSOR
//#define HEALTH_MONITOR
//#define I2C_COMPONENT
//#define LED
//#define MOTOR
//#define MOTOR_I2C
//#define MOTOR_PAIR
//#define MOTOR_PWM
//#define OTA_UPDATER
//#define REGISTRAR
//#define SCANNER
//#define SERVO
//#define SERVO_I2C  // Requires Servo
//#define SERVO_PWM  // Requires Servo/Esp32_Servo Arduino library

// Messaging
#define RESTFUL_PROTOCOL

// SMS

//#define SMS

// WebServer

#define WEB_SERVER  // Requires ESP8266WebServer/WebServer Arduino library

