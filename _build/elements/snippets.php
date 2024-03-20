<?php

return [
    'fmFiles' => [
        'file' => 'files',
        'description' => 'FileMan snippet to list files',
        'properties' => [
            'tpl' => array(
                'type' => 'textfield',
                'value' => 'tpl.FileMan.Files',
            ),
            'tplRow' => array(
                'type' => 'textfield',
                'value' => 'tpl.FileMan.Row',
            ),
            'tplGroup' => array(
                'type' => 'textfield',
                'value' => 'tpl.FileMan.Group',
            ),
            'tplWrapper' => array(
                'type' => 'textfield',
                'value' => '',
            ),
            'wrapIfEmpty' => array(
                'type' => 'combo-boolean',
                'value' => false,
            ),
            'sortBy' => array(
                'type' => 'textfield',
                'value' => 'sort_order',
            ),
            'sortDir' => array(
                'type' => 'list',
                'options' => array(
                    array('text' => 'ASC', 'value' => 'ASC'),
                    array('text' => 'DESC', 'value' => 'DESC'),
                ),
                'value' => 'ASC'
            ),
            'limit' => array(
                'type' => 'numberfield',
                'value' => 0,
            ),
            'offset' => array(
                'type' => 'numberfield',
                'value' => 0,
            ),
            'totalVar' => array(
                'type' => 'textfield',
                'value' => 'total',
            ),
            'toPlaceholder' => array(
                'type' => 'textfield',
                'value' => '',
            ),
            'ids' => array(
                'type' => 'textfield',
                'value' => '',
            ),
            'resource' => array(
                'type' => 'numberfield',
                'value' => 0,
            ),
            'showGroups' => array(
                'type' => 'combo-boolean',
                'value' => true,
            ),
            'makeUrl' => array(
                'type' => 'combo-boolean',
                'value' => true,
            ),
            'privateUrl' => array(
                'type' => 'combo-boolean',
                'value' => false,
            ),
            'includeTimeStamp' => array(
                'type' => 'combo-boolean',
                'value' => false,
            ),
        ],
    ],
];
