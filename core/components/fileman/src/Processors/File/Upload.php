<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
use FileMan\Utils\Mime;
use MODX\Revolution\modX;
use MODX\Revolution\Processors\Processor;
use MODX\Revolution\Sources\modMediaSource;
use PDO;

class Upload extends Processor
{
    public $objectType = 'File';
    public $classKey = File::class;
    public $languageTopics = ['fileman'];
    public $permission = 'fileman_create';
    public $permission2 = 'file_upload';

    /** @var modMediaSource $source */
    private $mediaSource;
    /** @var  string $uploadPath */
    private $uploadPath;
    /** @var  boolean $privateMode */
    private $privateMode;
    /** @var  boolean $autoTitle */
    private $autoTitle = true;
    /** @var  boolean $calcHash */
    private $calcHash;


    public function checkPermissions()
    {
        return $this->modx->hasPermission($this->permission) && $this->modx->hasPermission($this->permission2);
    }

    public function getLanguageTopics()
    {
        return $this->languageTopics;
    }

    public function initialize()
    {
        $this->setDefaultProperties(array('resource_id' => 0));

        $this->uploadPath = $this->preparePath($this->modx->getOption('fileman_path'));
        $this->privateMode = $this->modx->getOption('fileman_private');
        $this->autoTitle = $this->modx->getOption('fileman_auto_title', null, true);
        $this->calcHash = $this->modx->getOption('fileman_calchash');

        $this->setProperty('source', $this->modx->getOption('fileman_mediasource', null, 1));

        $this->mediaSource = $this->initializeMediaSource($this->getProperty('source'));

        if (!$this->mediaSource) {
            $this->modx->error->addError($this->modx->lexicon('permission_denied'));
            return false;
        }
        if (!$this->uploadPath) {
            $this->modx->error->addError($this->modx->lexicon('file_folder_err_ns'));
            return false;
        }

        return true;
    }

    /**
     * Получает и инициализирует Media source
     * @param $source integer Media source id
     * @return modMediaSource|boolean
     */
    private function initializeMediaSource($mediaSourceId)
    {
        /** @var modMediaSource $mediaSource */
        $mediaSource = $this->modx->getObject(modMediaSource::class, array('id' => $mediaSourceId));
        $mediaSource->initialize();

        if (empty($mediaSource) || !$mediaSource->getWorkingContext())
            return false;

        $mediaSource->setRequestProperties($this->getProperties());
        $mediaSource->initialize();

        return $mediaSource;
    }


    /**
     * @param $internalPath
     * @return bool|string
     */
    private function mediaSourceCreateContainer($containerName)
    {
        if ($containerName !== "/") {
            if (!$this->mediaSource->createContainer($containerName, '')) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileMan] Can`t create container: ' . $containerName);
                return false;
            }
        }
        return true;
    }

    private function saveTmpFile($url)
    {
        // TODO: добавить обработку ошибок сохранения файла
        //$tmp_dir = MODX_ASSETS_PATH.'components/fileman/tmp/';
        $tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
        $tmpFile = tempnam($tmp_dir, "file_man_");
        file_put_contents($tmpFile, file_get_contents($url));

        $fn = parse_url($url, PHP_URL_PATH);
        $fn = pathinfo($fn, PATHINFO_BASENAME);

        $title = $this->getProperty("title");

        return array(
            'name' => $fn,
            'type' => mime_content_type($tmpFile),
            'temp_file_name' => $tmpFile,
            'flag_remove_temp_file' => true,
            'error' => '0',
            'size' => filesize($tmpFile),
            'title' => $title
        );
    }


    public function process()
    {
        if (!$this->mediaSource->checkPolicy('create')) {
            return $this->failure($this->modx->lexicon('permission_denied'));
        }

        // Создадим контейнер (папку) в источнике
        if ($this->mediaSourceCreateContainer($this->uploadPath) === false) {
            return $this->failure($this->modx->lexicon('fileman_file_err_save'));
        };

        // Массив файлов, которые будем загружать
        $files = [];
        if ($url = $this->getProperty("url")) {
            // Сценарий №1: Загрузка по url, создадим массив с одним элементом
            $files[] = $this->saveTmpFile($url);
        } else {
            // Сценарий №2: мы работаем с POST запросом, содержащим $_FILES
            $files = $_FILES;
        }

        $result = array();
        foreach ($files as $file) {
            // filename
            $nameWithoutExtension = pathinfo($file['name'], PATHINFO_FILENAME);
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (empty($extension)) {
                $extension = Mime::mime2ext($file['type']);
            }

            $internalName = $this->getInternalName($nameWithoutExtension, $extension);

            // Загружаем файл в источник
            $uploadResult = $this->mediaSource->uploadObjectsToContainer(
                $this->uploadPath,
                array(
                    array_merge($file, array('name' => $internalName))
                )
            );

            // Удаляем временный файл после успешной загрузки, если его ранее создавали
            if (isset($file['flag_remove_temp_file'])) {
                unlink($file['temp_file_name']);
            }

            // Обработка ошибок
            if (!$uploadResult) {
                $msg = '';
                $errors = $this->mediaSource->getErrors();
                foreach ($errors as $k => $msg) {
                    $this->modx->error->addField($k, $msg);
                }
                // Вернем текст последней ошибки
                return $this->failure($msg);
            } else {
                // Создадим запись в БД
                $fid = File::generateName();
                $resourceId = $this->getProperty('resource_id');

                $title = isset($file['title']) ? $file['title'] : '';
                if ($this->autoTitle && empty($title)) {
                    $title = pathinfo($file['name'], PATHINFO_FILENAME);
                }

                $fileObject = $this->modx->newObject(File::class, array(
                    'fid' => $fid,
                    'resource_id' => $resourceId,
                    'title' => $title,
                    'name' => $nameWithoutExtension . '.' . $extension,
                    'internal_name' => $internalName,
                    'extension' => $extension,
                    'path' => $this->uploadPath,
                    'private' => $this->privateMode,
                    'user_id' => $this->modx->user->get('id'),
                    'sort_order' => $this->getNextSortOrder($resourceId),
                    'hash' => ($this->calcHash) ? sha1_file($this->uploadPath . $internalName) : ''
                ));

                if (!$fileObject->save())
                    return $this->failure($this->modx->lexicon('fileman_item_err_save'));

                $result[] = $fileObject->toArray();
            }
        }

        return $this->outputArray($result, count($result));
    }

    private function getInternalName($fileNameWithoutExtension, $extension)
    {
        $internalName = $this->privateMode ? File::generateName() : $fileNameWithoutExtension;

        if ((bool)$this->modx->getOption('upload_translit')) {
            $internalName = $this->modx->filterPathSegment($internalName);
            $internalName = $this->mediaSource->sanitizePath($internalName);
        }

        if (!empty($extension)) {
            $internalName .= '.' . $extension;
        }

        return $internalName;
    }


    /**
     * Get the next sort order for the files of the specified resource
     *
     * @param int $resourceId Id ресурса
     * @return int
     */
    private function getNextSortOrder($resourceId)
    {
        $tableName = $this->modx->getTableName(File::class);
        $stmt = $this->modx->query("SELECT MAX(`sort_order`) FROM {$tableName} WHERE `resource_id` = {$resourceId}");
        $sortOrder = (int)$stmt->fetch(PDO::FETCH_COLUMN);
        $stmt->closeCursor();

        return $sortOrder + 1;
    }

    /**
     * Substitutes the variables year, month, day, user, resource into $path
     *
     * @param string $path
     * @return string
     */
    private function preparePath($path)
    {
        $search = array('{year}', '{month}', '{day}', '{user}', '{resource}', '{resourceIdPath}');
        $replace = array(date('Y'), date('m'), date('d'), $this->modx->user->get('id'), $this->getProperty('resource_id'),implode('/', str_split(strval($this->getProperty('resource_id')))) . '/');

        return str_replace($search, $replace, $path);
    }
}
