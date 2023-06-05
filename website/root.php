<?php

define("ROOT", __DIR__);

// Subdomain
// Uncomment this to enable a local server or to spoof a subdomain for testing.
// Otherwise, subdomain will be inferred from first token in the URL.
//$SUBDOMAIN = "flexscreentest";

// Subdomain of the display registry
$DISPLAY_REGISTRY = "displayregistry";

// Folder for images.
$IMAGES_DIR = "/images";

// Folder for display firmware.
$DISPLAY_FIRMWARE_DIR = ROOT."/firmware/display";

// HTTP/HTTPS prefix
//$HTTP = "http";  // Select for local testing.
$HTTP = "https";   // Select for production.

?>