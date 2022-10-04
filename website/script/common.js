function hide(elementId)
{
   document.getElementById(elementId).style.display = "none";
}

function show(elementId, display)
{
   document.getElementById(elementId).style.display = display;
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

// https://www.w3schools.com/js/js_cookies.asp
function setCookie(cname, cvalue, exdays = null) {
  let expires = "";
  if (exdays != null)
  {
     const d = new Date();
     d.setTime(d.getTime() + (exdays*24*60*60*1000));
     expires = "expires=" + d.toUTCString() + ";";
  }
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

// https://www.w3schools.com/js/js_cookies.asp
function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}