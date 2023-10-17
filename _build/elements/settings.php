<?php

return [
    'mediasource' => array(
		'xtype' => 'modx-combo-source',
		'value' => 1
	),
	'path' => array(
		'xtype' => 'textfield',
		'value' => 'files/{resource}/'
	),
	'templates' => array(
		'xtype' => 'textfield',
		'value' => ''
	),
	'calchash' => array(
		'xtype' => 'combo-boolean',
		'value' => false
	),
	'private' => array(
		'xtype' => 'combo-boolean',
		'value' => false
	),
	'download' => array(
		'xtype' => 'combo-boolean',
		'value' => true
	),
    'auto_title' => array(
        'xtype' => 'combo-boolean',
        'value' => true
    ),
    'grid_fields' => array(
        'xtype' => 'textfield',
        'value' => 'id,name,title,description,group,private,download',
    ),
];
