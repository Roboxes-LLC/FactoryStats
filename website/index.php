<?php
?>

<html>

<head>
<style>
table {
   border-collapse: collapse;
}

table, th, td {
   border: 1px solid black;
   text-align: center;
}
</style>
</head>

<body>

Selected station:
<select id="station-input">
  <option value="ALL">All Stations</option>
  <option value="STA1">Station 1</option>
  <option value="STA2">Station 2</option>
  <option value="STA3">Station 3</option>
</select>

<br/>
<br/>

<button onclick="incrementCount()">Increment</button>

<br/>
<br/>

Count:<br/>
<div id="count-div">
</div>

<br/>

Hourly count:<br/>
<div id="hourly-count-div">
</div>

<script src="flexscreen.js"></script>
<script>
   // Start a one-second timer to update the machine status div.
   setInterval(function(){update();}, 1000);
   update();
</script>

</body>

</html>