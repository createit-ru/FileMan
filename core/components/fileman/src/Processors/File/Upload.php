<?php

namespace FileMan\Processors\File;

use FileMan\Model\File;
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


    public function checkPermissions() {
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
            $nameWithoutExtension = pathinfo($file['name'], PATHINFO_FILENAME);
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if(empty($extension)) {
                $extension = $this->mime2ext($file['type']);
            }

            // Имя файла, для privateMode оно будет случайным
            $internalName = $this->privateMode
                ? File::generateName() . "." . $extension
                : $nameWithoutExtension . "." . $extension;

            // Если установлена настройка upload_translit, то имя файла может измениться, учтем это
            if ((boolean)$this->modx->getOption('upload_translit')) {
                $internalName = $this->modx->filterPathSegment($internalName);
                $internalName = $this->mediaSource->sanitizePath($internalName);
            }

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
                    'title'=> $title,
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
        $sortOrder = (int) $stmt->fetch(PDO::FETCH_COLUMN);
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
        $search = array('{year}', '{month}', '{day}', '{user}', '{resource}');
        $replace = array(date('Y'), date('m'), date('d'), $this->modx->user->get('id'), $this->getProperty('resource_id'));

        return str_replace($search, $replace, $path);
    }

    /**
     * Get extension from mime type
     *
     * @param string $mime
     * @return string
     */
    function mime2ext($mime) {
        $mime = trim($mime);
        if(empty($mime)) {
            return '';
        }
        $mime_map = [
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpeg',
            'image/pjpeg'                                                               => 'jpeg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'application/vnd.oasis.opendocument.text'                                   => 'odt',
            'application/vnd.oasis.opendocument.spreadsheet'                            => 'ods',
            'application/vnd.oasis.opendocument.presentation'                           => 'odp',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        ];

        return isset($mime_map[$mime]) === true ? $mime_map[$mime] : '';
    }
}
