<?php

use MODX\Revolution\modExtraManagerController;

/**
 * The home manager controller for FileMan.
 *
 */
class FileManHomeManagerController extends modExtraManagerController
{

    /** @var FileMan\FileMan $FileMan */
    public $FileMan;


    /**
     *
     */
    public function initialize()
    {
        $this->FileMan = $this->modx->services->get('FileMan');
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return ['fileman:default'];
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return $this->modx->hasPermission('fileman');
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('fileman');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->FileMan->config['cssUrl'] . 'mgr/main.css');
        $this->addJavascript($this->FileMan->config['jsUrl'] . 'mgr/fileman.js');
        $this->addJavascript($this->FileMan->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->FileMan->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->FileMan->config['jsUrl'] . 'mgr/widgets/files.grid.js');
        $this->addJavascript($this->FileMan->config['jsUrl'] . 'mgr/widgets/files.window.js');
        $this->addJavascript($this->FileMan->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->FileMan->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addHtml('<script type="text/javascript">
        FileMan.config = ' . json_encode($this->FileMan->config) . ';
        FileMan.config.connector_url = "' . $this->FileMan->config['connectorUrl'] . '";
        Ext.onReady(function() {MODx.load({ xtype: "fileman-page-home"});});
        </script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $this->content .= '<div id="fileman-panel-home-div"></div>';
        return '';
    }
}
