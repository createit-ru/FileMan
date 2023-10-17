<?php

use FileMan\FileMan;
use MODX\Revolution\modX;
use MODX\Revolution\Error\modError;
use MODX\Revolution\Services\ContainerException;
use MODX\Revolution\Services\NotFoundException;

$fid = isset($_REQUEST['fid']) ? $_REQUEST['fid'] : '';

define('MODX_API_MODE', true);

// Load MODX
if (file_exists(dirname(__FILE__, 4) . '/index.php')) {
    require_once dirname(__FILE__, 4) . '/index.php';
} else {
    require_once dirname(__FILE__, 5) . '/index.php';
}


/** @var modX $modx */
$modx->services->add('error', new modError($modx));
$modx->error = $modx->services->get('error');
$modx->getRequest();
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');
$modx->error->message = null;

// Get properties
$properties = array();

/** @var FileMan $fileMan */
define('MODX_ACTION_MODE', true);
try {
    $fileMan = $modx->services->get('FileMan');
} catch (ContainerException | NotFoundException $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, "[FileMan] Can't get FileMan service.");
    return false;
}

$fileMan->download($fid);