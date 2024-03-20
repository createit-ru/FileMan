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
}
