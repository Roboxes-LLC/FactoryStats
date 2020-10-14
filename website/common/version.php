<?php
$VERSION = "1.05";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>