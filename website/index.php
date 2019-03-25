<?php
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Flexscreen Counter</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   
</head>

<body>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php include 'common/header.php';?>
   
   <div class="flex-horizontal main splash" style="flex-wrap: wrap; align-items: center;">
   
      <form action="workstationSummary.php">
      <div class="flex-vertical login-div">
         <label>Username</label>
         <input type="text">
         <label>Password</label>
         <input type="password">
         <button type="submit">Login</button>
      </div>
      </form>
      
   </div>
   
</div>

<script src="script/flexscreen.js"></script>
<script>
</script>

</body>

</html>