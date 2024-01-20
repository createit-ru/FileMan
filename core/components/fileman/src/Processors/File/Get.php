<?php

namespace FileMan\Processors\File;

use MODX\Revolution\Processors\Model\GetProcessor;
use FileMan\Model\File;

class Get extends GetProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman:default'];
    public $permission = 'view';


    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return mixed
     */
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }

        return parent::process();
    }
}
