<?php

use MODX\Revolution\modAccessPolicy;
use MODX\Revolution\modAccessPolicyTemplate;
use xPDO\Transport\xPDOTransport;
use xPDO\xPDO;


/** @var xPDO\Transport\xPDOTransport $transport */
if ($transport->xpdo) {
    $modx = $transport->xpdo;
    /** @var array $options */
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            // Assign policy to template
            $policy = $modx->getObject(modAccessPolicy::class, array('name' => 'FileManPolicy'));
            if ($policy) {
                $template = $modx->getObject(modAccessPolicyTemplate::class, ['name' => 'FileManPolicyTemplate']);
                if ($template) {
                    $policy->set('template', $template->get('id'));
                    $policy->save();
                } else {
                    $modx->log(
                        xPDO::LOG_LEVEL_ERROR,
                        '[FileMan] Could not find FileManPolicyTemplate Access Policy Template!'
                    );
                }
            } else {
                $modx->log(xPDO::LOG_LEVEL_ERROR, '[FileMan] Could not find FileManPolicyTemplate Access Policy!');
            }
            break;
    }
}
return true;
