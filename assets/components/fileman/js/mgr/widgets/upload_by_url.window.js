FileMan.window.UploadByUrl = function (config) {
    config = config || {}

    Ext.applyIf(config, {
        title: _('fileman_window_upload_by_url_title'),
        url: FileMan.config['connector_url'],
        cls: 'modx-window fileman-window ' || config['cls'],
        width: 900,
        autoHeight: true,
        allowDrop: false,
        record: {},
        baseParams: {},
        fields: [
            {
                layout: 'column',
                items: [{
                    columnWidth: .7,
                    layout: 'form',
                    defaults: {msgTarget: 'under'},
                    items: [
                        {
                            xtype: 'textfield',
                            id: config.id + '-url',
                            fieldLabel: _('fileman_field_url'),
                            name: 'url',
                            //value: 'http://www.pk-tp.ru/administrator/templates/bluestork/images/logo.png',
                            value: '',
                            anchor: '99%',
                            allowBlank: false,
                            defaultAutoCreate: {
                                tag: 'input',
                                type: 'text',
                                size: '16',
                                autocomplete: 'off'
                            }
                        },{
                            xtype: 'textfield',
                            id: config.id + '-title',
                            fieldLabel: _('fileman_field_title'),
                            name: 'title',
                            value: '',
                            anchor: '99%',
                            allowBlank: true
                        }, {
                            xtype: 'modx-description',
                            cls: 'fileman-window-url-description',
                            html: _('fileman_url_description'),
                            id: config.id + '-description'
                        }, {
                            xtype: 'xcheckbox',
                            boxLabel: _('fileman_url_close_window'),
                            hideLabel: true,
                            name: 'close',
                            id: config.id + '-close'
                        }
                    ]
                }, {
                    columnWidth: .3,
                    layout: 'form',
                    defaults: {msgTarget: 'under'},
                    items: [{
                        xtype: 'displayfield',
                        name: 'image',
                        cls: 'fileman-preview',
                        value: '<img src="../assets/components/fileman/img/preview.png" >',
                        id: config.id + '-preview',
                        anchor: '99%',
                        allowBlank: false,
                        scope: this,
                        renderer: this.renderPreview
                    }]
                }]
            }
        ],
        keys: this.getKeys(config),
        buttons: this.getButtons(config),
        listeners: this.getListeners(config)
    })
    FileMan.window.UploadByUrl.superclass.constructor.call(this, config)

    this.on('hide', function () {
        var w = this
        window.setTimeout(function () {
            w.close()
        }, 200)
    })

    this.on('afterrender', function () {
        var fbDom = Ext.get(config.id)
        fbDom.addListener('keydown', function () {
            this.renderPreview(config)
        }, this);

        fbDom.addListener('keyup', function () {
            this.renderPreview(config)
        }, this);
    })

}
Ext.extend(FileMan.window.UploadByUrl, MODx.Window, {
    renderPreview: function (config) {
        window.setTimeout(function () {
            var newValue = Ext.getCmp(config.id + '-url').getValue();
            var elem = Ext.getCmp(config.id + '-preview');

            var extension = newValue.split('.').pop().toLowerCase();
            var iconsPreview = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
            if(iconsPreview.indexOf(extension) !== -1) {
                elem.setValue('<i class="icon icon-' + extension + '"></i>')
            } else {
                elem.setValue('<img src="' + newValue + '" onerror = "this.src = \'../assets/components/fileman/img/preview.png\'"/>')
            }
        }, 200)
    },



    getButtons: function (config) {
        return [{
            text: config.cancelBtnText || _('cancel'),
            scope: this,
            handler: function () {
                config.closeAction !== 'close'
                    ? this.hide()
                    : this.close()
            }
        }, {
            text: _('upload'),
            cls: 'primary-button',
            scope: this,
            handler: function () {
                var values = this.fp.getForm().getValues();
                var el = this.getEl();
                el.mask(_('loading'), 'x-mask-loading');
                FileMan.typeLoad = 'url';
                Ext.getCmp(this.class_id).uploadByUrl({
                    url: values.url,
                    title: values.title
                }, function (response) {
                    el.unmask();
                })
            }
        }]
    },
    getKeys: function () {
        return [{
            key: Ext.EventObject.ENTER,
            shift: true,
            fn: function () {
                var values = this.fp.getForm().getValues()
                FileMan.typeLoad = 'url';
                FileMan.uploadByUrl({
                    url: values.url,
                    title: values.title
                })
            }, scope: this
        }]
    },
    getListeners: function (config) {
        return {
            success: {
                fn: function () {
                    this.refresh()
                }, scope: this
            }
        }
    }
});
Ext.reg('fileman-window-upload-by-url', FileMan.window.UploadByUrl);