<?php

namespace FileMan\Processors\File;

use MODX\Revolution\Processors\Model\GetProcessor;
use FileMan\Model\File;
use MODX\Revolution\Processors\ModelProcessor;

class Hash extends ModelProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman:default'];
    public $permission = 'save';

    /** @var File $object */
    public $object;

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

        $hash = sha1($this->object->getFullPath());

		$this->object->set('hash', $hash);
		$this->object->save();

		return $this->success('', array('hash' => $hash));
    }
}
