<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\Processors\Processor;

class Access extends Processor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman'];
    public $permission = 'save';


    /**
     * @return array|string
     */
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }

        $private = ($this->getProperty('private')) ? true : false;

        $ids = $this->modx->fromJSON($this->getProperty('ids'));
        if (empty($ids)) {
            return $this->failure($this->modx->lexicon('fileman_file_err_ns'));
        }

        foreach ($ids as $id) {
            /** @var File $object */
            if (!$object = $this->modx->getObject($this->classKey, $id)) {
                return $this->failure($this->modx->lexicon('fileman_file_err_nf'));
            }

            if (!$object->setPrivate($private))
                return $this->failure($this->modx->lexicon('fileman_file_err_nf'));
        }

        return $this->success();
    }
}
