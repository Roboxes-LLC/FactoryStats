#pragma once

// Note: Keep in sync with buttonDefs.hpp
enum ButtonPress
{
   bpUNKNOWN = 0,
   bpFIRST = 1,
   bpSINGLE_CLICK = bpFIRST,
   bpDOUBLE_CLICK,
   bpLONG_PRESS,
   bpLAST,
   bpCOUNT = bpLAST - bpFIRST
};
