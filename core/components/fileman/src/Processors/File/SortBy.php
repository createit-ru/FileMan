<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\modResource;
use MODX\Revolution\Processors\ModelProcessor;

class SortBy extends ModelProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman'];
    public $permission = 'fileman_save';

    /**
     * @return array|string
     */
    public function process()
    {
        // sort field
        $field = $this->getProperty('field');
        if (!in_array($field, ['title', 'name', 'group'])) {
            return $this->failure($this->modx->lexicon('fileman_item_err_ns'));
        }

        // resource
        $resource = $this->modx->getObject(modResource::class, intval($this->getProperty('resource')));
        if (!$resource) {
            return $this->failure($this->modx->lexicon('fileman_item_err_nf'));
        }

        // ids... doesn`t support
        // $ids = $this->modx->fromJSON($this->getProperty('ids'));
        // $ids = array_map('intval', $ids);

        $criteria = $this->modx->newQuery($this->classKey);
        $criteria->where([
            'resource_id' => $resource->get('id'),
        ]);

        
        $sortColumn = $this->modx->getSelectColumns($this->classKey, $this->objectType, '', [$field]);
        $criteria->sortby($sortColumn, 'ASC');

        $sortColumn2 = $this->modx->getSelectColumns($this->classKey, $this->objectType, '', ['id']);
        $criteria->sortby($sortColumn2, 'ASC');

        $files = $this->modx->getIterator($this->classKey, $criteria);

        $index = 0;
        /** @var File $file */
        foreach ($files as $file) {
            $file->set('sort_order', $index);
            $file->save();
            $index++;
        }

        return $this->success();
    }
}
