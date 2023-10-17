<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx = $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/FileMan/';
    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/fileman')) {
            $cache->deleteTree(
                $dev . 'assets/components/fileman/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/fileman/', $dev . 'assets/components/fileman');
        }
        if (!is_link($dev . 'core/components/fileman')) {
            $cache->deleteTree(
                $dev . 'core/components/fileman/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/fileman/', $dev . 'core/components/fileman');
        }
    }
}

return true;
