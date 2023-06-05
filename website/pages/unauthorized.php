<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/version.php';

?>

<!DOCTYPE html>

<html>

   <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      
      <link rel="stylesheet" type="text/css" href="../css/flex.css<?php echo versionQuery();?>"/>
      <link rel="stylesheet" type="text/css" href="../css/pages.css<?php echo versionQuery();?>"/>
   </head>
   
   <body class="flex-vertical" style="align-items: stretch;  justify-content: center;">
      <div class="instructions">
         This display could not be authorized with the supplied credentials.
         <br>
         <br>
         Contact your Factory Stats system administrator for login instructions.  
      </div>    
   </body>
   
</html>
