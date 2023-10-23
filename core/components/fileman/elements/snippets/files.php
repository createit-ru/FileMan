<?php

use FileMan\FileMan;
use FileMan\Model\File;

/** @var array $scriptProperties */
/** @var FileMan $fileMan */
$fileMan = $modx->services->get('FileMan');

// Get script options
$tpl = $modx->getOption('tpl', $scriptProperties, 'tpl.FileMan.Files');

$sortby = $modx->getOption('sortBy', $scriptProperties, 'sort_order');
$sortdir = $modx->getOption('sortDir', $scriptProperties, 'ASC');
$limit = $modx->getOption('limit', $scriptProperties, 0);
$offset = $modx->getOption('offset', $scriptProperties, 0);
$totalVar = $modx->getOption('totalVar', $scriptProperties, 'total');

$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);

$ids = $modx->getOption('ids', $scriptProperties, '');
$resource = $modx->getOption('resource', $scriptProperties, 0);
$showGroups = $modx->getOption('showGroups', $scriptProperties, 1);
$makeUrl = $modx->getOption('makeUrl', $scriptProperties, true);
$private = $modx->getOption('private', $scriptProperties, false);
$includeTimeStamp = $modx->getOption('includeTimeStamp', $scriptProperties, false);


// 
$mediaSource = $modx->getOption('fileman_mediasource', null, 1);
$ms = $modx->getObject('sources.modMediaSource', array('id' => $mediaSource));
$ms->initialize();
$public_url = $ms->getBaseUrl();
$private_url = $modx->getOption('fileman_assets_url', null, $modx->getOption('assets_url')) . 'components/fileman/';
$private_url .= 'download.php?fid=';

// Build query
$c = $modx->newQuery(File::class);

// resource
$c->where([
    'resource_id' => ($resource > 0) ? $resource : $modx->resource->get('id')
]);

// ids
$ids = explode(',', $ids);
$ids = array_filter(array_map('trim', $ids));
if(!empty($ids)) {
    $ids = array_map('intval', $ids);
    $c->where(['id:IN' => $ids]);
}

// offset & limit
if (!empty($limit)) {
    $total = $modx->getCount(File::class, $c);
    $modx->setPlaceholder($totalVar, $total);
    $c->limit($limit, $offset);
}

// sort
$c->sortby($sortby, $sortdir);

$items = $modx->getIterator(File::class, $c);

$outputData = [];

/** @var File $item */
foreach ($items as $item) {
    $item->source = $ms;

    $itemArr = $item->toArray();

    if ($makeUrl) {
        if ($itemArr['private'] || $private) {
            $itemArr['url'] = $private_url . $itemArr['fid'];
        }
        else {
            $itemArr['url'] = $public_url . $itemArr['path'] . $itemArr['internal_name'];
        }
    }

    if ($includeTimeStamp) {
        $itemArr['timestamp'] = filectime($item->getFullPath());
    }

    $outputData[] = $itemArr;
}

// Output
$output = $fileMan->getChunk($tpl, ['files' => $outputData]);

if (!empty($toPlaceholder)) {
    // If using a placeholder, output nothing and set output to specified placeholder
    $modx->setPlaceholder($toPlaceholder, $output);
    return '';
}

return $output;