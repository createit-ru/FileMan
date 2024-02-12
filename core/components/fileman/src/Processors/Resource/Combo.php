<?php

namespace FileMan\Processors\Resource;

use MODX\Revolution\modContext;
use MODX\Revolution\modResource;
use MODX\Revolution\Processors\Model\GetListProcessor;
use xPDO\Om\xPDOObject;
use xPDO\Om\xPDOQuery;

class Combo extends GetListProcessor
{
    public $objectType = 'modResource';
    public $classKey = modResource::class;
    public $defaultSortField = 'pagetitle';
    public $defaultSortDirection = 'ASC';
    public $permission = 'search';

    /** @var array $contextKeys */
    public $contextKeys = array();

    /** @var string $charset */
    public $charset = 'UTF-8';

    public function beforeQuery()
    {
        $this->contextKeys = $this->getContextKeys();
        if (empty($this->contextKeys))
            return $this->modx->lexicon('permission_denied');

        return true;
    }

    /**
     * Get a collection of Context keys that the User can access for all the Resources
     * @return array
     */
    public function getContextKeys()
    {
        $contextKeys = array();
        $contexts = $this->modx->getCollection(modContext::class, array('key:!=' => 'mgr'));

        /** @var modContext $context */
        foreach ($contexts as $context) {
            if ($context->checkPolicy('list'))
                $contextKeys[] = $context->get('key');
        }

        return $contextKeys;
    }

    public function beforeIteration(array $list) {
        $this->charset = $this->modx->getOption('modx_charset',null,'UTF-8');
        return $list;
    }

    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $id = $this->getProperty('id');
        $query = $this->getProperty('query');
        $templates = $this->modx->getOption('fileman_templates');

        $where = array('context_key:IN' => $this->contextKeys);

        if ($templates != '')
            $where['template:IN'] = explode(',', $templates);

        if (!empty($id)) {
            $where['id'] = $id;
        }
        if (!empty($query)) {
            $where['pagetitle:LIKE'] = "%$query%";
        }

        $c->select('id,pagetitle');
        $c->where($where);

        return $c;
    }


    public function prepareRow(xPDOObject $object)
    {
        $objectArray = $object->toArray();

        $objectArray['pagetitle'] = html_entity_decode($objectArray['pagetitle'], ENT_COMPAT, $this->charset);
        $objectArray['description'] = html_entity_decode($objectArray['description'], ENT_COMPAT, $this->charset);

        return array(
            'id' => $objectArray['id'],
            'pagetitle' => $objectArray['pagetitle'],
            'description' => $objectArray['description'],
        );
    }
}
