<?php

namespace FileMan;

use FileMan\Model\File;
use MODX\Revolution\modX;
use MODX\Revolution\Services\ContainerException;
use MODX\Revolution\Services\NotFoundException;

class FileMan
{
    /** @var modX $modx */
    public $modx;

    /** @var pdoTools $pdoTools */
    private $pdoTools = null;

    /** @var array $config */
    public $config = [];

    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;

        $this->pdoTools = $this->getPdoTools();

        $corePath = MODX_CORE_PATH . 'components/fileman/';
        $assetsUrl = MODX_ASSETS_URL . 'components/fileman/';

        $this->config = array_merge([
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'connectorUrl' => $assetsUrl . 'connector.php',

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'processorsPath' => $corePath . 'src/Processors/',

            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',

        ], $config);

        $this->modx->lexicon->load('fileman:default');

        $this->config = array_merge($this->config, array(
            'file_fields' => $this->getFileFields(),
            'files_grid_fields' => $this->getFileGridFields(),
            'resource_id' => '',
        ));
    }

    /**
     * This method returns the list of File fields.
     * @return array
     * */
    public function getFileFields()
    {
        return array_merge(array_keys($this->modx->getFields(File::class)), array('resource_pagetitle', 'username'));
    }

    /**
     * This method returns the list of fields in the message grid.
     * @return array
     * */
    public function getFileGridFields()
    {
        $grid_fields = $this->modx->getOption('fileman_grid_fields');
        $grid_fields = array_map('trim', explode(',', $grid_fields));
        return array_values(array_intersect($grid_fields, $this->getFileFields()));
    }

    /**
     * Process and return the output from a Chunk by name.
     *
     * @param string $chunk The name of the chunk or @INLINE chunk.
     * @param array $properties An associative array of properties to process the Chunk with, treated as placeholders within the scope of the Element.
     * @param boolean $fastMode If false, all MODX tags in chunk will be processed.
     *
     * @return string The processed output of the Chunk.
     */
    public function getChunk($chunk, array $properties = array())
    {
        if ($this->pdoTools) {
            return $this->pdoTools->getChunk($chunk, $properties, false);
        }

        if (empty($chunk)) {
            return print_r($properties, 1);
        }

        return $this->modx->getChunk($chunk, $properties);
    }

    /**
     * Returns that pdoTools is available
     *
     * @return boolean
     */
    public function pdoToolsAvailable()
    {
        return $this->pdoTools ? true : false;
    }

    /**
     * Loads an instance of pdoTools
     *
     * @return boolean
     */
    public function getPdoTools()
    {
        try {
            $pdoTools = $this->modx->services->get('pdotools');
        } catch (ContainerException | NotFoundException $ex) {
            return null;
        }
        return $pdoTools;
    }

    public function download($fid)
    {
        if (empty($fid)) {
            return $this->modx->sendErrorPage();
        }

        /** @var File $fileObject */
        $fileObject = $this->modx->getObject(File::class, array('fid' => $fid));
        if (empty($fileObject)) {
            return $this->modx->sendErrorPage();
        }

        @session_write_close();

        $perform_count = true;

        // If file is private then redirect else read file directly
        if ($fileObject->get('private')) {

            $meta = $fileObject->getMetaData();

            // Get file info
            $fileName = $fileObject->getFullPath();

            $fileSize = $meta['size'];

            $mtime = filemtime($fileName);

            if (isset($_SERVER['HTTP_RANGE'])) {
                // Get range
                $range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);
                list($start, $end) = explode('-', $range);

                // Check data
                if (empty($start)) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 416 Requested Range Not Satisfiable');
                    return;
                } else {
                    $perform_count = false;
                }

                // Check range
                $start = intval($start);
                $end = intval($end);

                if (($end == 0) || ($end < $start) || ($end >= $fileSize)) $end = $fileSize - 1;

                $remain = $end - $start;

                if ($remain == 0) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 416 Requested Range Not Satisfiable');
                    return;
                }

                header($_SERVER['SERVER_PROTOCOL'] . ' 206 Partial Content');
                header("Content-Range: bytes $start-$end/$fileSize");
            } else {
                $remain = $fileSize;
            }

            // Put headers
            header('Last-Modified: ' . gmdate('r', $mtime));
            header('ETag: ' . sprintf('%x-%x-%x', fileinode($fileName), $fileSize, $mtime));
            header('Accept-Ranges: bytes');
            header('Content-Type: application/force-download');
            header('Content-Length: ' . $remain);
            header('Content-Disposition: attachment; filename="' . $fileObject->get('name') . '"');
            header('Connection: close');

            if ($range) {
                $fh = fopen($fileName, 'rb');
                fseek($fh, $start);

                // Output contents
                $blocksize = 8192;

                while (!feof($fh) && ($remain > 0)) {
                    echo fread($fh, ($remain > $blocksize) ? $blocksize : $remain);
                    flush();

                    $remain -= $blocksize;
                }

                fclose($fh);
            } else {
                readfile($fileName);
            }
        } else {
            // In public mode redirect to file url
            $fileUrl = $fileObject->getUrl();
            header("Location: $fileUrl", true, 302);
        }

        // Count downloads if allowed by config
        if ($perform_count && $this->modx->getOption('fileman_count_downloads', null, true)) {

            $count = $fileObject->get('download');
            $fileObject->set('download', $count + 1);
            $fileObject->save();
        }
    }

    /**
     * Get extension from mime type
     *
     * @param string $mime
     * @return string
     */
    public static function mime2ext($mime)
    {
        $mime = trim($mime);
        if (empty($mime)) {
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
