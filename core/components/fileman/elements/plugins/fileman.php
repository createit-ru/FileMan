<?php

use \FileMan\FileMan;

/** @var \MODX\Revolution\modX $modx */
switch ($modx->event->name) {
    case 'OnDocFormPrerender':

        // Check access
        if (!$modx->hasPermission('fileman_doclist')) return;

        // Skip form building when resource template is not in permitted list
        $templates = trim($modx->getOption('fileman_templates'));

        if ($templates != '') {
            $templates = array_map('trim', explode(',', $templates));
            $template = $resource->get('template');
            if (!in_array($template, $templatelist)) {
                return;
            }
        }

        /** @var FileMan $fileMan */
        $fileMan = new FileMan($modx);
        $modx->services->add('FileMan', $fileMan);

        $modx->controller->addLexiconTopic('fileman:default');

        $assetsUrl = $fileMan->config['assetsUrl'];
        $modx->controller->addJavascript($assetsUrl . 'js/mgr/fileman.js');
        $modx->controller->addLastJavascript($assetsUrl . 'js/mgr/misc/utils.js');
        $modx->controller->addLastJavascript($assetsUrl . 'js/mgr/misc/combo.js');
        $modx->controller->addLastJavascript($assetsUrl . 'js/mgr/widgets/files.grid.js');
        $modx->controller->addLastJavascript($assetsUrl . 'js/mgr/widgets/files.window.js');
        $modx->controller->addLastJavascript($assetsUrl . 'js/mgr/widgets/upload_by_url.window.js');
        $modx->controller->addLastJavascript($assetsUrl . 'js/mgr/widgets/page.panel.js');

        $modx->controller->addCss($assetsUrl . 'css/mgr/main.css');

        $fileMan->config['resource_id'] = $resource->get('id');

        $modx->controller->addHtml('<script type="text/javascript">FileMan.config = ' . $modx->toJSON($fileMan->config) . ';</script>');

        $modx->controller->addHtml('
			<script type="text/javascript">
				Ext.ComponentMgr.onAvailable("modx-resource-tabs", function() {
					this.on("beforerender", function() {
						this.add({
							xtype: "fileman-panel-page",
							id: "fileman-panel-page",
							title: _("fileman_files")
						});
					});
					/*Ext.apply(this, {
							stateful: true,
							stateId: "modx-resource-tabs-state",
							stateEvents: ["tabchange"],
							getState: function() {return {activeTab:this.items.indexOf(this.getActiveTab())};
						}
					});*/
				});
			</script>');
        break;

        // Remove attached files to resources
    case 'OnEmptyTrash':
        $fileMan = $modx->services->get('FileMan');
        if (!$fileMan) {
            return;
        }

        foreach ($ids as $id) {
            $files = $modx->getIterator(\FileMan\Model\File::class, [
                'resource_id' => $id
            ]);
            foreach ($files as $file) {
                $file->remove();
            }
        }

        break;
}
