<?php

namespace FileMan\Processors\File;

use MODX\Revolution\Processors\Model\CreateProcessor;
use FileMan\Model\File;

class Create extends CreateProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman'];
    public $permission = 'create';

    /** @var File $object */
    public $object;


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $resourceId = (int)$this->getProperty('resource_id');

        if (empty($resourceId)) {
            $this->modx->error->addField('resource_id', $this->modx->lexicon('notset'));
        }

        $name = trim($this->getProperty('name'));
        $name = $this->object->sanitizeName($name);
        $this->setProperty('name', $name);
        
        if (empty($name)) {
            $this->modx->error->addField('name', $this->modx->lexicon('fileman_file_err_name'));
        }

        $this->setProperty('fid', $this->object->generateName());

        return parent::beforeSet();
    }
}
