<?php
$VERSION = "1.09b";

function versionQuery()
{
   global $VERSION;
   return ("?version=$VERSION");
}
?>