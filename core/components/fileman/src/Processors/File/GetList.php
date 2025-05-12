<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use MODX\Revolution\modResource;
use MODX\Revolution\modUser;
use MODX\Revolution\Processors\Model\GetListProcessor;
use MODX\Revolution\Sources\modMediaSource;
use MODX\Revolution\Sources\modMediaSourceInterface;
use xPDO\Om\xPDOObject;
use xPDO\Om\xPDOQuery;

class GetList extends GetListProcessor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $defaultSortField = 'id';
    public $defaultSortDirection = 'DESC';
    public $permission = 'fileman_list';

    /**
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize(): bool
    {
        if (!empty($this->getProperty('resource_id'))) {
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

    public function prepareQueryBeforeCount(xPDOQuery $c): xPDOQuery
    {
        $resourceId = (int)$this->getProperty('resource_id');
        $user = trim($this->getProperty('user'));
        $query = trim($this->getProperty('query'));

        $c->select($this->modx->getSelectColumns(File::class, 'File'));

        if ($query) {
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
            $c->leftJoin('modResource', 'Resource', 'Resource.id=File.resource_id');
            $c->select($this->modx->getSelectColumns(modResource::class, 'Resource', 'resource_', ['pagetitle']));
        }

        return $c;
    }

    /**
     * Prepare the row for iteration
     */
    public function prepareRow(xPDOObject $object): array
    {
        /** @var File $object */
        $array = $object->toArray();
        if (isset($array['resource_pagetitle'])) {
            $array['resource_pagetitle'] = strip_tags($array['resource_pagetitle']);
        }
        $array['path'] = $object->getPath();
        return $array;
    }
}
