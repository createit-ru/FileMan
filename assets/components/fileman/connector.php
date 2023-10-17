<?php


/** @var  MODX\Revolution\modX $modx */
/** @var  FileMan\FileMan $fileMan */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

if (file_exists(dirname(__FILE__, 4) . '/config.core.php')) {
    require_once dirname(__FILE__, 4) . '/config.core.php';
} else {
    require_once dirname(__FILE__, 5) . '/config.core.php';
}

if (empty($_REQUEST['action'])) {
    die('Access denied');
}
else {
    $action = $_REQUEST['action'];
}

require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';
$fileMan = $modx->services->get('FileMan');
$modx->lexicon->load('fileman:default');

// handle request
$corePath = $modx->getOption('fileman_core_path', null, $modx->getOption('core_path') . 'components/fileman/');
$path = $modx->getOption(
    'processorsPath',
    $fileMan->config,
    $corePath . 'src/Processors/'
);
$modx->getRequest();

if($action == 'download') {
    $action = 'File\\WebDownload';
}

$requestOptions = [
    'action' => 'FileMan\\Processors\\' . $action,
    'processors_path' => $path,
    'location' => '',
];

$modx->request->handleRequest($requestOptions);