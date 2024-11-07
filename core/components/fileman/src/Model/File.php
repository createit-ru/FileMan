<?php

namespace FileMan\Model;

use xPDO\xPDO;
use MODX\Revolution\Sources\modMediaSource;

/**
 * Class FileManItem
 *
 * @property string $name
 * @property string $description
 * @property boolean $active
 *
 * @package FileMan\Model
 */
class File extends \xPDO\Om\xPDOSimpleObject
{
    /** @var modMediaSource $source */
    private $source = false;

    /**
     * Get the source, preparing it for usage.
     *
     * @return modMediaSource source
     */
    private function getMediaSource()
    {
        if ($this->source)
            return $this->source;

        //get modMediaSource
        $mediaSourceId = $this->xpdo->getOption('fileman_mediasource', null, 1);

        /** @var modMediaSource $mediaSource */
        $mediaSource = $this->xpdo->getObject(modMediaSource::class, array('id' => $mediaSourceId));
        $mediaSource->initialize();
        $this->source = $mediaSource;

        return $this->source;
    }

    /**
     * Get object URL
     *
     * @return string
     */
    function getUrl()
    {
        $ms = $this->getMediaSource();
        return $ms->getBaseUrl() . $this->getPath();
    }

    /**
     * Get relative file path
     *
     * @return string
     */
    function getPath()
    {
        return $this->get('path') . $this->get('internal_name');
    }

    /**
     * Get full file path in fs
     *
     * @return string
     */
    function getFullPath()
    {
        $ms = $this->getMediaSource();
        return $ms->getBasePath() . $this->getPath();
    }

    /**
     * Get file meta data
     *
     * @return string
     */
    function getMetaData()
    {
        $ms = $this->getMediaSource();
        $path = $this->getPath();
        return $ms->getMetaData($path);
    }

    /**
     * Get file size
     *
     * @return string
     */
    function getSize()
    {
        $meta = $this->getMetaData();
        return $meta['size'];
    }

    /**
     * Rename file
     *
     * @param string $newName
     * @return boolean
     */
    function rename($newName)
    {
        $ms = $this->getMediaSource();

        if ($ms->renameObject($this->get('path') . $this->get('internal_name'), $newName)) {
            $this->set('name', $newName);
            $this->set('internal_name', $newName);
            $this->set('extension', strtolower(pathinfo($newName, PATHINFO_EXTENSION)));
        } else {
            return false;
        }

        return true;
    }

    /**
     * Set privacy mode
     *
     * @param boolean $private
     * @return boolean
     */
    function setPrivate($private)
    {
        if ($this->get('private') == $private) {
            return true;
        }

        $ms = $this->getMediaSource();
        $path = $this->get('path');
        $extension = strtolower(pathinfo($this->get('name'), PATHINFO_EXTENSION));

        // Generate name and check for existence
        $filename = $private ? $this->generateName() . "." . $extension : $this->get('name');

        // Получим список имен файлов в контейнере
        $files = [];
        foreach ($ms->getObjectsInContainer($path) as $fi) {
            $files[] = mb_strtolower($fi['name']);
        };

        // генерируем новое имя файла, если вдруг такое уже есть в текущем контейнере
        // TODO: потенциально бесконечный цикл, нужно исправить
        while (in_array($filename, $files)) {
            if ($private)
                $filename = $this->generateName() . "." . $extension;
            else
                $filename = $this->generateName(4) . '_' . $filename;
        }

        if ($ms->renameObject($this->get('path') . $this->get('internal_name'), $filename)) {
            $this->set('internal_name', $filename);
            $this->set('private', $private);
            $this->save();

            return true;
        } else {
            $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[FileMan] An error occurred while trying to rename the attachment file at: ' . $filename);
        }

        return false;
    }

    /**
     * Remove file and object
     *
     * @param array $ancestors
     * @return bool
     */
    function remove(array $ancestors = array())
    {
        $filename = $this->getPath();
        if (!empty($filename)) {
            $ms = $this->getMediaSource();
            if (!@$ms->removeObject($filename))
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[FileMan] An error occurred while trying to remove the attachment file at: ' . $filename);
        }

        return parent::remove($ancestors);
    }

    /* Generate Filename
     *
     * @param   integer  $length		Length of generated sequence
     * @return  string
     */
    static function generateName($length = 32)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $charactersLength = strlen($characters);

        $newName = '';

        for ($i = 0; $i < $length; $i++)
            $newName .= $characters[rand(0, $charactersLength - 1)];

        return $newName;
    }

    /* Sanitize Filename
     *
     * @param   string  $str		Input file name
     * @return  string
     */
    static function sanitizeName($str)
    {
        $bad = array(
            '../', '<!--', '-->', '<', '>',
            "'", '"', '&', '$', '#',
            '{', '}', '[', ']', '=',
            ';', '?', '%20', '%22',
            '%3c', // <
            '%253c', // <
            '%3e', // >
            '%0e', // >
            '%28', // (
            '%29', // )
            '%2528', // (
            '%26', // &
            '%24', // $
            '%3f', // ?
            '%3b', // ;
            '%3d', // =
            '/', './', '\\'
        );

        return stripslashes(str_replace($bad, '', $str));
    }
}
