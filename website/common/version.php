<?php
$VERSION = "1.70";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>