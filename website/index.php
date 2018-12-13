<?php
require_once 'common/time.php';

Time::init();
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

<button onclick="incrementCount(); update();">Increment</button>

<br/>
<br/>

Count:<br/>
<div id="count-div">
</div>

<br/>

Time since last count:<br/>
<div id="elapsed-time-div">Boner!
</div>

<br/>

Average count time:<br/>
<div id="average-count-time-div">
</div>

<br/>

Hourly count:<br/>
<div id="hourly-count-div">
</div>

<script src="flexscreen.js"></script>
<script>
   // Start a five-second timer to update the count/hourly count div.
   setInterval(function(){update();}, 5000);

   // Start a one-second timer to update the elapsed-time-div.
   setInterval(function(){updateElapsedTime();}, 1000);
   update();
</script>

</body>

</html>