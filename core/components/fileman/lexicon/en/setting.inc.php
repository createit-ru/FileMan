<?php

$_lang['area_fileman_main'] = 'Main';

$_lang['setting_fileman_mediasource'] = 'Media source';
$_lang['setting_fileman_mediasource_desc'] = 'Media source for files.';

$_lang['setting_fileman_path'] = 'Path to files';
$_lang['setting_fileman_path_desc'] = 'Path inside the file source. Must end with "/". Supports {year},{month},{day},{user},{resource},{resourceIdPath} variables. Eg. files/{resource}/{year}/';

$_lang['setting_fileman_templates'] = 'Templates';
$_lang['setting_fileman_templates_desc'] = 'List of templates for which the component works. Listing separated by commas. Works by default for all templates.';

$_lang['setting_fileman_calchash'] = 'Calculate file hash';
$_lang['setting_fileman_calchash_desc'] = 'Calculate SHA1 hash of file when uploading.';

$_lang['setting_fileman_private'] = 'Private mode';
$_lang['setting_fileman_private_desc'] = 'Save files with a random name by default so that you cannot access the file by name. Downloads are counted only for closed files.';

$_lang['setting_fileman_count_downloads'] = 'Count downloads';
$_lang['setting_fileman_count_downloads_desc'] = 'Counting downloads increases the load on the database.';

$_lang['setting_fileman_auto_title'] = 'Auto titles';
$_lang['setting_fileman_auto_title_desc'] = 'Automatically generates a title (file name without extension) when uploading a file.';

$_lang['setting_fileman_grid_fields'] = 'Fields in the grid';
$_lang['setting_fileman_grid_fields_desc'] = 'Fields, separated by commas, that will be displayed in the list of files.';

$_lang['setting_fileman_pdotools'] = 'Use the pdoTools parser and the Fenom template engine';
$_lang['setting_fileman_pdotools_desc'] = 'If pdoTools installed, the fmFiles snippet uses the single chunk specified in the tpl parameter, otherwise the chunks specified in the tplRow, tplGroup, tplWrapper parameters.';
