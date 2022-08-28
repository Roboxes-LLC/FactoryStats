function setUserId(userId)
{
   var input = document.getElementById('user-id-input');
   input.setAttribute('value', userId);
}

function setUserInfo(userId, firstName, lastName, username, role, email, authToken)
{
   var input = document.getElementById('user-id-input');
   input.setAttribute('value', userId);
   
   input = document.getElementById('first-name-input');
   input.setAttribute('value', firstName);
   
   input = document.getElementById('last-name-input');
   input.setAttribute('value', lastName);

   input = document.getElementById('username-input');
   input.setAttribute('value', username);

   input = document.getElementById('role-input');
   input.value = role;
   
   input = document.getElementById('email-input');
   input.setAttribute('value', email);
   
   /*
   input = document.getElementById('auth-token-input');
   input.setAttribute('value', authToken);
   */
}

function setAction(action)
{
   var input = document.getElementById('action-input');
   input.setAttribute('value', action);
}

function onCustomerClicked(element)
{
   var customerId = element.value;
   var userId = element.getAttribute('data-userId');
   var shouldAdd = (element.checked == true);
   
   updateUserCustomer(userId, customerId, shouldAdd);
}

function updateUserCustomer(userId, customerId, shouldAdd)
{
   var action = shouldAdd ? "add" : "remove";
   var requestURL = "api/userCustomer/?userId=" + userId + "&customerId=" + customerId + "&action=" + action;
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         try
         {
            var json = JSON.parse(this.responseText);
            
            if (!json.success)
            {
               console.log(json.error);
            }
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
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}