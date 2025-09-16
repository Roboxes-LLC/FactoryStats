<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/core/common/router.php';
require_once ROOT.'/app/page/cycleTimePage.php';

// *****************************************************************************
//                                   Begin

session_start();

$router = new Router();
$router->setLogging(false);

$router->add("cycleTime", function($params) {
   (new CycleTimePage())->handleRequest($params);
});
   
$router->route();
?>