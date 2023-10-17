<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */

// Load the classes
$modx->addPackage('FileMan\Model', $namespace['path'] . 'src/', null, 'FileMan\\');

$modx->services->add('FileMan', function ($c) use ($modx) {
    return new FileMan\FileMan($modx);
});
