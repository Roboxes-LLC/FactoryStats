function setUserInfo(employeeNumber, firstName, lastName, username, role, email)
{
   var input = document.getElementById('employee-number-input');
   input.setAttribute('value', employeeNumber);
   
   input = document.getElementById('first-name-input');
   input.setAttribute('value', firstName);
   
   input = document.getElementById('last-name-input');
   input.setAttribute('value', lastName);

   input = document.getElementById('username-input');
   input.setAttribute('value', username);

   input = document.getElementById('role-input');
   input.setAttribute('value', role);
   
   input = document.getElementById('email-input');
   input.setAttribute('value', email);
}

function setAction(action)
{
   var input = document.getElementById('action-input');
   input.setAttribute('value', action);
}