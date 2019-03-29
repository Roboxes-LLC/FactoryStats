// When the user clicks on <span> (x), close the modal
var closeButtons = document.getElementsByClassName("close");
for (let closeButton of closeButtons)
{
   closeButton.onclick = function() {
      hideModal(closeButton.parentElement.parentElement.id);
   }
}

// When the user clicks anywhere outside a modal, close it.
window.onclick = function(event) {
   if (event.target.classList.contains('modal'))
   {
      hideModal(event.target.id);
   }
}

function isModalVisible()
{
   var isVisible = false;
   
   var modals = document.getElementsByClassName("modal");
   
   for (let modal of modals)
   {
      isVisible |= (modal.style.display == "block");
   }
   
   return (isVisible);
}

function showModal(id)
{
   console.log("showModal: " + id);
   document.getElementById(id).style.display = "block";
}

function hideModal(id)
{
   console.log("hideModal: " + id);
   document.getElementById(id).style.display = "none";
}