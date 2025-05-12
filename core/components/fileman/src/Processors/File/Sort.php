<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\Processors\ModelProcessor;
use xPDO\xPDOException;

class Sort extends ModelProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman'];
    public $permission = 'fileman_save';

    /**
     * @return array|string
     * @throws xPDOException
     */
    public function process()
    {
        $order = $this->modx->fromJSON($this->getProperty('sort_order'));

        if (empty($order))
            return $this->failure($this->modx->lexicon('fileman_item_err_ns'));

        foreach ($order as $id => $value) {
            /** @var File $object */
            if (!$object = $this->modx->getObject($this->classKey, $id))
                return $this->failure($this->modx->lexicon('fileman_item_err_nf'));

            $object->set('sort_order', $value);
            $object->save();
        }

        return $this->success();
    }
}
