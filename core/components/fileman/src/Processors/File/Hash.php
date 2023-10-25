<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\Processors\Model\UpdateProcessor;

class Hash extends UpdateProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman:default'];
    public $permission = 'save';

    /** @var File $object */
    public $object;

    /**
     * @return bool
     */
    public function beforeSet()
    {
        $hash = sha1($this->object->getFullPath());

        $this->setProperty('hash', $hash);

        return parent::beforeSet();
    }
}
