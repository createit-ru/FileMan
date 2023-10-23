<?php

return [
    'mediasource' => array(
		'xtype' => 'modx-combo-source',
		'value' => 1,
		'area' => 'fileman_main',
	),
	'path' => array(
		'xtype' => 'textfield',
		'value' => 'files/{resource}/',
		'area' => 'fileman_main',
	),
	'templates' => array(
		'xtype' => 'textfield',
		'value' => '',
		'area' => 'fileman_main',
	),
	'calchash' => array(
		'xtype' => 'combo-boolean',
		'value' => false,
		'area' => 'fileman_main',
	),
	'private' => array(
		'xtype' => 'combo-boolean',
		'value' => false,
		'area' => 'fileman_main',
	),
	'download' => array(
		'xtype' => 'combo-boolean',
		'value' => true,
		'area' => 'fileman_main',
	),
    'auto_title' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
		'area' => 'fileman_main',
    ),
    'grid_fields' => array(
        'xtype' => 'textfield',
        'value' => 'id,name,title,description,group,private,download',
		'area' => 'fileman_main',
    ),
];
