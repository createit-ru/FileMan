<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\Processors\ModelProcessor;

class Reset extends ModelProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman'];
    public $permission = 'fileman_save';

    /**
	 * @return array|string
	 */
	public function process() {
		$ids = $this->modx->fromJSON($this->getProperty('ids'));

		if (empty($ids))
			return $this->failure($this->modx->lexicon('fileman_item_err_ns'));

		foreach ($ids as $id) {
			/** @var File $object */
			if (!$object = $this->modx->getObject($this->classKey, $id))
				return $this->failure($this->modx->lexicon('fileman_item_err_nf'));

			$object->set('download', 0);
			$object->save();
		}

		return $this->success();
	}

}
