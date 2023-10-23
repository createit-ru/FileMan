<?php

$_lang['setting_fileman_mediasource'] = 'Медиа источник';
$_lang['setting_fileman_mediasource_desc'] = 'Медиа источник для сохранения файлов';

$_lang['setting_fileman_path'] = 'Путь к файлам';
$_lang['setting_fileman_path_desc'] = 'Путь внутри файлового источника. Должно завершаться на "/". Поддерживает переменные {year},{month},{day},{user},{resource}. Напр. files/{resource}/{year}/';

$_lang['setting_fileman_templates'] = 'Шаблоны';
$_lang['setting_fileman_templates_desc'] = 'Список шаблонов для работы модуля. Перечисление через запятую. По умолчанию работает для всех ресурсов';

$_lang['setting_fileman_calchash'] = 'Считать хэш файла';
$_lang['setting_fileman_calchash_desc'] = 'Вычислять SHA1 хэш файла при загрузке';

$_lang['setting_fileman_private'] = 'Закрытый режим';
$_lang['setting_fileman_private_desc'] = 'Сохранять файлы по умолчанию со случайным именем чтобы нельзя было получить доступ к файлу по названию. Подсчет скачиваний ведется только для закрытых файлов';

$_lang['setting_fileman_count_downloads'] = 'Считать скачивания';
$_lang['setting_fileman_count_downloads_desc'] = 'Подсчёт скачиваний увеличивает нагрузку на БД';

$_lang['setting_fileman_auto_title'] = 'Авто-заголовок';
$_lang['setting_fileman_auto_title_desc'] = 'Автоматически формирует Заголовок (имя файла без расширения) при загрузке файла';

$_lang['setting_fileman_grid_fields'] = 'Поля в списке файлов';
$_lang['setting_fileman_grid_fields_desc'] = 'Поля, через запятую, которые будут выводится в списке файлов';
