function setUserId(userId)
{
   var input = document.getElementById('user-id-input');
   input.setAttribute('value', userId);
}

function setUserInfo(userId, employeeNumber, firstName, lastName, username, password, role, email, authToken)
{
   var input = document.getElementById('user-id-input');
   input.setAttribute('value', userId);

   input = document.getElementById('employee-number-input');
   input.setAttribute('value', employeeNumber);
   
   input = document.getElementById('first-name-input');
   input.setAttribute('value', firstName);
   
   input = document.getElementById('last-name-input');
   input.setAttribute('value', lastName);

   input = document.getElementById('username-input');
   input.setAttribute('value', username);
   
   input = document.getElementById('password-input');
   input.setAttribute('value', password);

   input = document.getElementById('role-input');
   input.value = role;
   
   input = document.getElementById('email-input');
   input.setAttribute('value', email);
   
   input = document.getElementById('auth-token-input');
   input.setAttribute('value', authToken);
}

function setAction(action)
{
   var input = document.getElementById('action-input');
   input.setAttribute('value', action);
}