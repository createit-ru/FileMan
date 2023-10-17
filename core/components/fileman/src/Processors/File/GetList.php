<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\modUser;
use MODX\Revolution\Processors\Model\GetListProcessor;
use xPDO\Om\xPDOQuery;

class GetList extends GetListProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $defaultSortField = 'id';
    public $defaultSortDirection = 'DESC';
    public $permission = 'list';


    /**
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize()
    {
        if(!empty($this->getProperty('resource_id'))) {
            $this->defaultSortField = 'sort_order';
            $this->defaultSortDirection = 'ASC';
        }

        return parent::initialize();
    }

    /**
     * We do a special check of permissions
     * because our objects is not an instances of modAccessibleObject
     *
     * @return boolean|string
     */
    public function beforeQuery()
    {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @param xPDOQuery $c
     *
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $resourceId = (int) $this->getProperty('resource_id');
		$user = trim($this->getProperty('user'));
		$query = trim($this->getProperty('query'));

        $c->select($this->modx->getSelectColumns(File::class, 'File'));

		if ($query){
			$c->where(array(
                'name:LIKE' => "%{$query}%",
                'OR:title:LIKE' => "%{$query}%",
                'OR:description:LIKE' => "%{$query}%",
                'OR:group:LIKE' => "%{$query}%"
            ));
        }

		if ($user || ($resourceId == 0)) {
			$c->select('User.username');
			$c->leftJoin(modUser::class, 'User', 'User.id=File.user_id');
		}

		if ($user)
			$c->where(array('User.username:LIKE' => "%$user%"));

		if ($resourceId > 0)
			$c->where(array('resource_id' => $resourceId));
		else {
			$c->select('Resource.pagetitle');
			$c->leftJoin('modResource', 'Resource', 'Resource.id=File.resource_id');
		}

        return $c;
    }


    /**
     * @param xPDOObject $object
     *
     * @return array
     */
    /*public function prepareRow(xPDOObject $object)
    {
        $array = $object->toArray();
        $array['actions'] = [];

        // Edit
        $array['actions'][] = [
            'cls' => '',
            'icon' => 'icon icon-edit',
            'title' => $this->modx->lexicon('fileman_item_update'),
            //'multiple' => $this->modx->lexicon('fileman_items_update'),
            'action' => 'updateItem',
            'button' => true,
            'menu' => true,
        ];

        if (!$array['active']) {
            $array['actions'][] = [
                'cls' => '',
                'icon' => 'icon icon-power-off action-green',
                'title' => $this->modx->lexicon('fileman_item_enable'),
                'multiple' => $this->modx->lexicon('fileman_items_enable'),
                'action' => 'enableItem',
                'button' => true,
                'menu' => true,
            ];
        } else {
            $array['actions'][] = [
                'cls' => '',
                'icon' => 'icon icon-power-off action-gray',
                'title' => $this->modx->lexicon('fileman_item_disable'),
                'multiple' => $this->modx->lexicon('fileman_items_disable'),
                'action' => 'disableItem',
                'button' => true,
                'menu' => true,
            ];
        }

        // Remove
        $array['actions'][] = [
            'cls' => '',
            'icon' => 'icon icon-trash-o action-red',
            'title' => $this->modx->lexicon('fileman_item_remove'),
            'multiple' => $this->modx->lexicon('fileman_items_remove'),
            'action' => 'removeItem',
            'button' => true,
            'menu' => true,
        ];

        return $array;
    }*/
}
