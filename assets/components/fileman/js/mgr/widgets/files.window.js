FileMan.window.UpdateFile = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'fileman-file-window-update';
    }
    Ext.applyIf(config, {
        title: _('update'),
        bwrapCssClass: 'x-window-with-tabs',
        width: 550,
        autoHeight: true,
        url: FileMan.config.connectorUrl,
        action: 'File\\Update',
        fields: this.getFields(config),
        keys: [
            {
                key: Ext.EventObject.ENTER, shift: true, fn: function () {
                    this.submit()
                }, scope: this
            }
        ]
    });

    FileMan.window.UpdateFile.superclass.constructor.call(this, config);
}

Ext.extend(FileMan.window.UpdateFile, MODx.Window, {
    calcHash: function (btn, e, row) {
        btn.hide();
        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'File\\Hash',
                id: this.config.record.object.id
            },
            listeners: {
                success: {
                    fn: function (r) {
                        Ext.getCmp(this.config.id + '-hash').setValue(r.object.hash);
                        Ext.getCmp(this.config.id + '-hash').show();
                    }, scope: this
                },
                fail: {
                    fn: function () {
                        btn.show();
                    }, scope: this
                }
            }
        });
    },

    getFields: function (config) {
        var fieldsTabGeneral = [
            {
                xtype: 'hidden',
                name: 'id',
                id: config.id + '-id'
            },
            {
                xtype: 'textfield',
                fieldLabel: _('fileman_title'),
                name: 'title',
                id: config.id + '-title',
                anchor: '99%',
                allowBlank: true
            },
            {
                xtype: 'textarea',
                fieldLabel: _('fileman_description'),
                name: 'description',
                id: config.id + '-description',
                anchor: '99%',
                height: 120
            },
            {
                xtype: 'textfield',
                fieldLabel: _('fileman_group'),
                name: 'group',
                id: config.id + '-group',
                anchor: '99%',
                allowBlank: true
            },
            {
                xtype: 'textfield',
                fieldLabel: _('fileman_name'),
                name: 'name',
                id: config.id + '-name',
                anchor: '99%',
                allowBlank: false
            },
            {
                xtype: 'xcheckbox',
                id: config.id + '-private',
                boxLabel: _('fileman_private'),
                hideLabel: true,
                name: 'private'
            }
        ];

        var fieldsTabSettings = [
            {
                xtype: 'statictextfield',
                fieldLabel: _('fileman_path'),
                name: 'path',
                id: config.id + '-path',
                anchor: '99%'
            },
            {
                xtype: 'statictextfield',
                fieldLabel: _('fileman_internal_name'),
                name: 'internal_name',
                id: config.id + '-internal_name',
                anchor: '99%'
            },
            {
                xtype: 'statictextfield',
                fieldLabel: _('fileman_extension'),
                name: 'extension',
                id: config.id + '-extension',
                anchor: '99%'
            },
            {
                xtype: 'statictextfield',
                fieldLabel: _('fileman_fid'),
                name: 'fid',
                id: config.id + '-fid',
                anchor: '99%'
            },
            {
                xtype: 'statictextfield',
                fieldLabel: _('fileman_hash'),
                id: config.id + '-hash',
                name: 'hash',
                //hidden: (config.record.object.hash == ''),
                anchor: '99%'
            }
        ];

        if (config.record.object.hash == '') {
            fieldsTabSettings.push([
                {
                    xtype: 'button',
                    text: _('fileman_calculate'),
                    handler: this.calcHash,
                    scope: this
                }
            ]);
        }

        //return fields;

        var result = [];
        if (FileMan.config.resource_id > 0) {
            result.push({ xtype: 'hidden', name: 'resource_id', id: config.id + '-resource_id' });
        }
        else {
            fieldsTabSettings.unshift({
                xtype: 'modx-combo',
                id: config.id + '-resource_id',
                fieldLabel: _('resource'),
                name: 'resource_id',
                hiddenName: 'resource_id',
                url: FileMan.config.connectorUrl,
                baseParams: {
                    action: 'Resource\\Combo'
                },
                fields: ['id', 'pagetitle', 'description'],
                displayField: 'pagetitle',
                anchor: '99%',
                pageSize: 10,
                editable: true,
                typeAhead: true,
                allowBlank: false,
                forceSelection: true,
                tpl: new Ext.XTemplate('<tpl for="."><div class="x-combo-list-item"><span style="font-weight: bold">{pagetitle}</span>',
                    '<tpl if="description"><br/><span style="font-style:italic">{description}</span></tpl>', '</div></tpl>')
            });
        }

        var tabs = [
            {
                title: _('fileman_file_tab_general'),
                layout: 'anchor',
                items: [
                    {
                        layout: 'form',
                        cls: 'modx-panel',
                        items: [fieldsTabGeneral]
                    }
                ]
            },
            {
                title: _('fileman_file_tab_settings'),
                layout: 'anchor',
                items: [
                    {
                        layout: 'form',
                        cls: 'modx-panel',
                        items: [fieldsTabSettings]
                    }
                ]
            }
        ];

        result.push({
            xtype: 'modx-tabs',
            defaults: { border: false, autoHeight: true },
            deferredRender: false,
            border: true,
            hideMode: 'offsets',
            items: [tabs]
        });
        return result;
    }
});
Ext.reg('fileman-file-window-update', FileMan.window.UpdateFile);