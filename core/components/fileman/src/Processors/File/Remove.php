<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\Processors\Processor;

class Remove extends Processor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman'];
    public $permission = 'remove';


    /**
     * @return array|string
     */
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }

        $ids = json_decode($this->getProperty('ids'), true);
        if (empty($ids)) {
            return $this->failure($this->modx->lexicon('fileman_file_err_ns'));
        }

        foreach ($ids as $id) {
            /** @var File $object */
            if (!$object = $this->modx->getObject($this->classKey, $id)) {
                return $this->failure($this->modx->lexicon('fileman_file_err_nf'));
            }

            $object->remove();
        }

        return $this->success();
    }
}
