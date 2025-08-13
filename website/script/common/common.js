/*
function hide(elementId)
{
   document.getElementById(elementId).style.display = "none";
}

function show(elementId, display)
{
   document.getElementById(elementId).style.display = display;
}

function isVisible(elementId)
{
   return (document.getElementById(elementId).style.display != "none");
}
*/

function hide(elementId)
{
   document.getElementById(elementId).classList.add("hidden");
}

function show(elementId)
{
   document.getElementById(elementId).classList.remove("hidden");
}

function isVisible(elementId)
{
   return (!document.getElementById(elementId).classList.contains("hidden"));
}

function toggleVisible(elementId)
{
   if (isVisible(elementId))
   {
      hide(elementId);
   }
   else
   {
      show(elementId);
   }
}

function set(elementId, value)
{
   document.getElementById(elementId).value = value;
}

function clear(elementId)
{
   document.getElementById(elementId).value = null;
}

function enable(elementId)
{
   document.getElementById(elementId).disabled = false;
}

function disable(elementId)
{
   document.getElementById(elementId).disabled = true;
}

function isEnabled(elementId)
{
   return (document.getElementById(elementId).disabled == false);
}

function parseBool(value)
{
   return ((value === true) || (value.toLowerCase() === "true"));
}

function formatCurrency(value)
{
   return value.toLocaleString('en-US', {style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 5});
}

function ajaxRequest(requestUrl, callback)
{
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {        
         var response = {success: false};
          
         try
         {
            response = JSON.parse(this.responseText);
         }
         catch (exception)
         {
            if (exception.name == "SyntaxError")
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }
            else
            {
               throw(exception);
            }
         }
         
         if (callback != null)
         {
            callback(response);
         }
      }
   };
   xhttp.open("GET", requestUrl, true);
   xhttp.send(); 
}

function submitForm(formId, requestUrl, callback)
{
   var form = document.querySelector("#" + formId);
   
   var xhttp = new XMLHttpRequest();

   // Bind the form data.
   var formData = new FormData(form);

   // Define what happens on successful data submission.
   xhttp.addEventListener("load", function(event) {
      
      var response = {success: false};
      
      try
      {
         response = JSON.parse(event.target.responseText);
      }
      catch (exception)
      {
         if (exception.name == "SyntaxError")
         {
            response.error = "Bad server response";
            console.log("JSON parse error: \n" + this.responseText);
         }
         else
         {
            response.error = "Unknown server error";
            throw(exception);
         }
      }
      
      if (callback != null)
      {
         callback(response);
      }
   });

   // Define what happens on successful data submission.
   xhttp.addEventListener("error", function(event) {
     alert('Oops! Something went wrong.');
   });

   xhttp.open("POST", requestUrl, true);

   // The data sent is what the user provided in the form
   xhttp.send(formData);      
}

function setSession(key, value)
{
   requestUrl = "/app/page/session/?request=set&key=" + key + "&value=" + value;
   
   ajaxRequest(requestUrl, function(response) {
      if (response.success == false)
      {
         console.log(`API call to update $_SESSION failed. ${json.error}`);
      }
   });
}

// *****************************************************************************
// Tabulator formatters

var MAX_LABEL_LENGTH = 32;

function longLabelFormatter(cell, formatterParams, onRendered)
{
   let label = cell.getValue();
   if (label != null)
   {
      label = (label.length <= MAX_LABEL_LENGTH) ? label : label.substring(0, MAX_LABEL_LENGTH) + "...";
   }
   return (label);
};

function longLabelTooltip(cell)
{
   let label = cell.getValue();
   if (label != null)
   {
      label = (label.length <= MAX_LABEL_LENGTH) ? "" :  label;
   }
   return (label);
}

function currencyFormatter(cell, formatterParams, onRendered)
{
   let currency = cell.getValue();
   if (currency != null)
   {
      currency = formatCurrency(currency);
   }
   return (currency);
}

function roundToDecimalPlaces(value, decimalPlaces)
{
   let multiplier = Math.pow(10, decimalPlaces);
   return(Math.round((value + Number.EPSILON) * multiplier) / multiplier);
}

function isDefined(variable)
{
   return (typeof variable !== 'undefined');
}
