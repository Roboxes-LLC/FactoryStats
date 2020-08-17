function exportCsv()
{
   document.getElementById("action-input").value = "download";
   document.getElementById("filter-form").submit();
   
   // Clear the action.
   document.getElementById("action-input").value = "";
}
