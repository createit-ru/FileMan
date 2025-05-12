<?php

use FileMan\FileMan;
use MODX\Revolution\modExtraManagerController;

/**
 * The home manager controller for FileMan.
 *
 */
class FileManHomeManagerController extends modExtraManagerController
{
    public FileMan $fileMan;

    public function initialize(): void
    {
        $this->fileMan = $this->modx->services->get('FileMan');
        parent::initialize();
    }

    public function getLanguageTopics(): array
    {
        return ['fileman:default'];
    }

    public function checkPermissions(): bool
    {
        return $this->modx->hasPermission('fileman');
    }

    public function getPageTitle(): ?string
    {
        return $this->modx->lexicon('fileman');
    }

    public function loadCustomCssJs(): void
    {
        $this->addCss($this->fileMan->config['cssUrl'] . 'mgr/main.css');
        $this->addJavascript($this->fileMan->config['jsUrl'] . 'mgr/fileman.js');
        $this->addJavascript($this->fileMan->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->fileMan->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->fileMan->config['jsUrl'] . 'mgr/widgets/files.grid.js');
        $this->addJavascript($this->fileMan->config['jsUrl'] . 'mgr/widgets/files.window.js');
        $this->addJavascript($this->fileMan->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->fileMan->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addHtml('<script type="text/javascript">
        FileMan.config = ' . json_encode($this->fileMan->config) . ';
        FileMan.config.connector_url = "' . $this->fileMan->config['connectorUrl'] . '";
        Ext.onReady(function() {MODx.load({ xtype: "fileman-page-home"});});
        </script>');
    }

    public function getTemplateFile(): string
    {
        $this->content .= '<div id="fileman-panel-home-div"></div>';
        return '';
    }
}
