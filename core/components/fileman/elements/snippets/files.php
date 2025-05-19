<?php

use FileMan\FileMan;
use FileMan\Model\File;
use MODX\Revolution\modX;
use MODX\Revolution\Sources\modMediaSource;

/** @var array $scriptProperties */
/** @var modX $modx */
/** @var FileMan $fileMan */
$fileMan = $modx->services->get('FileMan');

// Fenom tpl
$tpl = $modx->getOption('tpl', $scriptProperties, 'tpl.FileMan.Files');
// MODX tpls
$tplWrapper = $modx->getOption('tplWrapper', $scriptProperties, '');
$tplGroup = $modx->getOption('tplGroup', $scriptProperties, 'tpl.FileMan.Group');
$tplRow = $modx->getOption('tplRow', $scriptProperties, 'tpl.FileMan.Row');
$wrapIfEmpty = $modx->getOption('wrapIfEmpty', $scriptProperties, false);

$usePdoTools = $fileMan->pdoToolsAvailable() && $modx->getOption('fileman_pdotools', $scriptProperties, true);

$sortBy = $modx->getOption('sortBy', $scriptProperties, 'sort_order');
$sortDir = $modx->getOption('sortDir', $scriptProperties, 'ASC');
$limit = $modx->getOption('limit', $scriptProperties, 0);
$offset = $modx->getOption('offset', $scriptProperties, 0);
$totalVar = $modx->getOption('totalVar', $scriptProperties, 'total');

$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);

$ids = $modx->getOption('ids', $scriptProperties, '');
$resource = $modx->getOption('resource', $scriptProperties, 0);
$showUnpublished = $modx->getOption('showUnpublished', $scriptProperties, false);
$showGroups = $modx->getOption('showGroups', $scriptProperties, true);
$makeUrl = $modx->getOption('makeUrl', $scriptProperties, true);
$private = $modx->getOption('private', $scriptProperties, false);
$includeTimeStamp = $modx->getOption('includeTimeStamp', $scriptProperties, false);

$outputSeparator = $modx->getOption('outputSeparator', $scriptProperties, "\n");

$mediaSource = $modx->getOption('fileman_mediasource', null, 1);
/** @var modMediaSource $mediaSource */
$mediaSource = $modx->getObject(modMediaSource::class, array('id' => $mediaSource));
$mediaSource->initialize();
$public_url = $mediaSource->getBaseUrl();
$private_url = $modx->getOption('fileman_assets_url', null, $modx->getOption('assets_url')) . 'components/fileman/';
$private_url .= 'download.php?fid=';

// Build query
$c = $modx->newQuery(File::class);

if (!$showUnpublished) {
    $c->where([
        'published' => true
    ]);
}
// resource
$c->where([
    'resource_id' => ($resource > 0) ? $resource : $modx->resource->get('id')
]);

// ids
$ids = explode(',', $ids);
$ids = array_filter(array_map('trim', $ids));
if (!empty($ids)) {
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
$c->sortby($modx->escape($sortBy), $sortDir);

$items = $modx->getIterator(File::class, $c);

$outputData = [];
$groupOutputData = [];
$curGroup = '';
$itemsCount = iterator_count($items);
$index = 0;

/** @var File $item */
foreach ($items as $item) {
    $item->setMediaSource($mediaSource);
    $itemArr = $item->toArray();

    if ($makeUrl) {
        if ($itemArr['private'] || $private) {
            $itemArr['url'] = $private_url . $itemArr['fid'];
        } else {
            $itemArr['url'] = $public_url . $itemArr['path'] . $itemArr['internal_name'];
        }
    }

    if (!$showGroups) {
        $itemArr['group'] = false;
    }

    if ($includeTimeStamp) {
        $itemArr['timestamp'] = filectime($item->getFullPath());
    }

    if ($usePdoTools) {
        $outputData[] = $itemArr;
    } else {
        // Checking if we need to start a new group...
        if ($curGroup != $itemArr['group']) {
            if (count($groupOutputData) > 0) {
                $outputData[] = $fileMan->getChunk($tplGroup, [
                    'group' => $curGroup,
                    'output' => implode($outputSeparator, $groupOutputData)
                ]);
                $groupOutputData = [];
            }
            $curGroup = $itemArr['group'];
        }
        $groupOutputData[] = $fileMan->getChunk($tplRow, $itemArr);

        $index++;

        // ..or is this the last iteration
        if ($index === $itemsCount) {
            $outputData[] = $fileMan->getChunk($tplGroup, [
                'group' => $curGroup,
                'output' => implode($outputSeparator, $groupOutputData)
            ]);
        }
    }
}

// Output
if ($usePdoTools) {
    $output = $fileMan->getChunk($tpl, ['files' => $outputData]);
} else {
    $output = implode($outputSeparator, $outputData);
    if (!empty($tplWrapper) && $wrapIfEmpty) {
        $output = $fileMan->getChunk($tplWrapper, ['output' => $output]);
    }
}

// If using a toPlaceholder, output nothing and set output to specified placeholder
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder, $output);
    return '';
}

return $output;