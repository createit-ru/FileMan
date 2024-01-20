<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\Processors\Model\UpdateProcessor;

class Update extends UpdateProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman'];
    public $permission = 'fileman_save';

    /** @var File $object */
    public $object;

    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return bool|string
     */
    public function beforeSave()
    {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @return bool
     */
    public function beforeSet()
    {

        $resourceId = (int)$this->getProperty('resource_id');

        if (!$resourceId) {
            $this->modx->error->addField('resource_id', $this->modx->lexicon('notset'));
        }

        $private = ($this->getProperty('private')) ? true : false;

        // Allow filename change only in private mode. May be changed further
        $name = trim($this->getProperty('name'));
        $name = $this->object->sanitizeName($name);
        if (empty($name)) {
            $this->modx->error->addField('name', $this->modx->lexicon('fileman_file_err_name'));
        }

        // If file is open we should rename file, otherwize just change field value
        if (!$this->object->get('private')) {
            $this->unsetProperty('name');

            // Rename if name changed
            if ($name != $this->object->get('name')) {
                if (!$this->object->rename($name)) {
                    $this->modx->error->addField('name', $this->modx->lexicon('fileman_file_err_nr'));
                }
            }
        }

        if (!$this->object->setPrivate($private)) {
            $this->modx->error->addField('name', $this->modx->lexicon('fileman_file_err_nr'));
        }

        return parent::beforeSet();
    }
}
