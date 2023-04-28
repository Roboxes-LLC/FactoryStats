#pragma once

#include "Common/StdString.hpp"
#include "STL/List.hpp"

struct BreakDescription
{
   String code;
   String description;

   inline bool operator<(
      const BreakDescription& rhs) const
   {
      return (code < rhs.code);
   }

   inline bool operator==(
      const BreakDescription& rhs)
   {
      return ((code == rhs.code) &&
              (description == rhs.description));
   }
};

typedef List<BreakDescription> BreakDescriptionList;

