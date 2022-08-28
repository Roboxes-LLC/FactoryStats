function setGroupId(groupId)
{
   var input = document.getElementById('group-id-input');
   input.setAttribute('value', groupId);
}

function setStationGroup(groupId, name, stationIds)
{
   var input = document.getElementById('group-id-input');
   input.setAttribute('value', groupId);

   input = document.getElementById('name-input');
   input.setAttribute('value', name);

   // Uncheck all station inputs.
   let elements = document.getElementsByClassName("stationCheckbox");
   for (element of elements)
   {
      element.checked = false;
   }

   // Check all included station inputs. 
   for (stationId of stationIds)
   {
      let id = "station-input-" + stationId;
      
      element = document.getElementById(id);
      if (element != null)
      {
         element.checked = true;
      }
   }
}

function setAction(action)
{
   var input = document.getElementById('action-input');
   input.setAttribute('value', action);
}
