FileMan.grid.Files = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'fileman-grid-files';
    }
    this.sm = new Ext.grid.CheckboxSelectionModel();
    Ext.applyIf(config, {
        url: FileMan.config.connectorUrl,
        fields: FileMan.config.file_fields,
        columns: this.getColumns(config),
        //grouping: true,
        ddText: _('fileman_ddtext'),
        tbar: this.getTopBar(config),
        sm: this.sm,
        baseParams: {
            action: 'File\\GetList',
            resource_id: FileMan.config.resource_id
        },
        listeners: {
            rowDblClick: function (grid, rowIndex, e) {
                var row = grid.store.getAt(rowIndex);
                this.updateFile(grid, e, row);
            },
            sortchange: this.saveSort
        },
        viewConfig: {
            forceFit: true,
            enableRowBody: true,
            autoFill: true,
            showPreview: true,
            scrollOffset: 0
        },
        paging: true,
        remoteSort: true,
        autoHeight: true
    });

    // Enable D&D only in resource editor
    if (FileMan.config.resource_id)
        Ext.applyIf(config, {
            plugins: [new Ext.ux.dd.GridDragDropRowOrder({
                copy: false,
                scrollable: true,
                targetCfg: {},
                listeners: {
                    'afterrowmove': { fn: this.onAfterRowMove, scope: this }
                }
            })]
        });

    // Restore sort state
    var sortInfo = [];
    if (sortInfo = this.restoreSort()) {
        // Workaround for absence of sortInfo support
        config.baseParams.sort = sortInfo[0];
        config.baseParams.dir = sortInfo[1];

        config.sortBy = sortInfo[0];
        config.sortDir = sortInfo[1];
    }

    FileMan.grid.Files.superclass.constructor.call(this, config);

    // Set sort arrow
    if (sortInfo.length > 0)
        this.store.setDefaultSort(sortInfo[0], sortInfo[1]);

    this.restoreColumn();
    this.colModel.on('hiddenchange', this.saveColumn, this);

    // Clear selection on grid refresh
    this.store.on('load', function () {
        if (this._getSelectedIds().length) {
            this.getSelectionModel().clearSelections();
        }
    }, this);
}
Ext.extend(FileMan.grid.Files, MODx.grid.Grid, {
    windows: {
        UploadByUrl: false
    },

    // File context menu
    getMenu: function (grid, rowIndex) {
        var menu = [
            { handler: grid['updateFile'], text: '<i class="x-menu-item-icon icon icon-edit"></i>' + _('fileman_update') },
            { handler: grid['downloadFile'], text: '<i class="x-menu-item-icon icon icon-download"></i>' + _('file_download') },
            '-',
            { handler: grid['resetFileDownloads'], text: '<i class="x-menu-item-icon icon icon-undo"></i>' + _('fileman_reset_downloads') },
            '-',
            { handler: grid['removeFile'], text: '<i class="x-menu-item-icon icon icon-remove"></i>' + _('remove') }
        ];

        this.addContextMenuItem(menu);
    },

    // Restore sort info to session storage
    restoreSort: function () {
        if (typeof (Storage) !== "undefined") {
            var sortInfo = sessionStorage.getItem('fa_sort' + ((FileMan.config.resource_id > 0) ? '_' + FileMan.config.resource_id : ''));
            return (sortInfo) ? sortInfo.split('|', 2) : false;
        }

        return false;
    },

    // Save sort info to session storage
    saveSort: function (grid, sortInfo) {
        if (typeof (Storage) !== "undefined") {
            sessionStorage.setItem('fa_sort' + ((FileMan.config.resource_id > 0) ? '_' + FileMan.config.resource_id : ''),
                sortInfo.field + "|" + sortInfo.direction);
        }
    },

    // Restore column info from session storage
    restoreColumn: function () {
        if (typeof (Storage) !== "undefined") {
            var colInfo = sessionStorage.getItem('fa_col' + ((FileMan.config.resource_id > 0) ? '_' + FileMan.config.resource_id : ''));
            if (colInfo != null) {
                var cols = colInfo.split(',');
                for (var i = 0; i < cols.length; i++)
                    this.colModel.setHidden(i + 1, cols[i] == '0');
            }
        }
    },

    // Save column visibility to session storage
    saveColumn: function (colModel, colIndex, hidden) {
        if (typeof (Storage) !== "undefined") {
            var count = colModel.getColumnCount(false);
            var cols = [];
            for (var i = 1; i < count; i++) cols.push(colModel.isHidden(i) ? 0 : 1);

            sessionStorage.setItem('fa_col' + ((FileMan.config.resource_id > 0) ? '_' + FileMan.config.resource_id : ''),
                cols.join(','));
        }
    },

    // Edit File
    updateFile: function (btn, e, row) {
        if (typeof (row) != 'undefined') {
            this.menu.record = row.data;
        }
        else if (!this.menu.record) {
            return false;
        }
        var id = this.menu.record.id;

        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'File\\Get',
                resource_id: FileMan.config.resource_id,
                id: id
            },
            listeners: {
                success: {
                    fn: function (r) {
                        var w = MODx.load({
                            xtype: 'fileman-file-window-update',
                            id: Ext.id(),
                            record: r,
                            listeners: {
                                success: {
                                    fn: function () {
                                        this.refresh();
                                    }, scope: this
                                }
                            }
                        });
                        w.reset();
                        w.setValues(r.object);
                        w.show(e.target);
                    }, scope: this
                }
            }
        });
    },

    // Edit file access
    accessFile: function (act, btn, e) {
        var ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }

        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'File\\Access',
                private: (act.name == 'close') ? 1 : 0,
                ids: Ext.util.JSON.encode(ids)
            },
            listeners: {
                success: {
                    fn: function (r) {
                        this.refresh();
                    }, scope: this
                },
                failure: {
                    fn: function (r) {
                    }, scope: this
                }
            }
        });

        return true;
    },

    // Reset download count
    resetFileDownloads: function (act, btn, e) {
        var ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.msg.confirm({
            title: ids.length > 1
                ? _('reset_downloads')
                : _('reset_downloads'),
            text: ids.length > 1
                ? _('fileman_resets_downloads_confirm')
                : _('fileman_reset_downloads_confirm'),
            url: this.config.url,
            params: {
                action: 'File\\Reset',
                ids: Ext.util.JSON.encode(ids)
            },
            listeners: {
                success: {
                    fn: function (r) {
                        this.refresh();
                    }, scope: this
                }
            }
        });
        return true;
    },

    // Sort by handler
    sortByHandler: function (act, btn, e) {
        let field = '';
        switch (act.name) {
            case 'sortby_title':
                field = 'title';
                break;
            case 'sortby_name':
                field = 'name';
                break;
            case 'sortby_group':
                field = 'group';
                break;
            default:
                return false;
        }
        var ids = this._getSelectedIds();

        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'File\\SortBy',
                field: field,
                resource: FileMan.config.resource_id,
                ids: Ext.util.JSON.encode(ids)
            },
            listeners: {
                success: {
                    fn: function (r) {
                        this.refresh();
                    }, scope: this
                },
                failure: {
                    fn: function (r) {
                    }, scope: this
                }
            }
        });

        return true;
    },

    // Remove file
    removeFile: function (act, btn, e) {
        var ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.msg.confirm({
            title: ids.length > 1
                ? _('remove')
                : _('remove'),
            text: ids.length > 1
                ? _('confirm_remove')
                : _('confirm_remove'),
            url: this.config.url,
            params: {
                action: 'File\\Remove',
                ids: Ext.util.JSON.encode(ids)
            },
            listeners: {
                success: {
                    fn: function (r) {
                        this.refresh();
                    }, scope: this
                }
            }
        });
        return true;
    },

    // Download file
    downloadFile: function (act, btn, e) {
        var item = this._getSelected();

        var filePath = item['path'] + item['internal_name'];

        MODx.Ajax.request({
            url: MODx.config.connector_url,
            params: {
                action: 'Browser/File/Download',
                file: filePath,
                wctx: MODx.ctx || '',
                source: MODx.config['fileman_mediasource']
            },
            listeners: {
                'success': {
                    fn: function (r) {
                        if (!Ext.isEmpty(r.object.url)) {
                            location.href = MODx.config.connector_url +
                                '?action=Browser/File/Download&download=1&file=' +
                                filePath + '&HTTP_MODAUTH=' + MODx.siteId +
                                '&source=' + MODx.config['fileman_mediasource'] + '&wctx=' + MODx.ctx;
                        }
                    }, scope: this
                }
            }
        });

        return true;
    },

    // Show uploader dialog
    uploadFiles: function (btn, e) {
        if (!this.uploader) {
            this.uploader = new MODx.util.MultiUploadDialog.Dialog({
                title: _('upload'),
                url: this.config.url,
                base_params: {
                    action: 'File\\Upload',
                    resource_id: FileMan.config.resource_id
                },
                cls: 'modx-upload-window'
            });
            this.uploader.on('hide', this.refresh, this);
            this.uploader.on('close', this.refresh, this);
        }

        // Automatically open picker
        this.uploader.show(btn);
    },

    // Define columns
    getColumns: function (config) {
        const columnsRaw = {
            id: { sortable: true, width: 40 },
            thumb: { sortable: true, width: 100, renderer: FileMan.utils.renderThumb },
            name: { sortable: true, width: 120 },
            title: { sortable: true, width: 250, renderer: FileMan.utils.renderName },
            group: { sortable: true, width: 150 },
            extension: { sortable: true, width: 50 },
            download: { sortable: true, width: 50 },
            private: { sortable: true, width: 50, renderer: FileMan.utils.renderBoolean },
            path: { sortable: true, width: 100 },
        };

        let columns = [this.sm];
        if (FileMan.config.resource_id) {
            columns.push({
                header: _('fileman_sort_order'),
                dataIndex: 'sort_order',
                hidden: false,
                sortable: FileMan.config.resource_id > 0,
                width: 40
            });
        }

        for (let i = 0; i < FileMan.config.files_grid_fields.length; i++) {
            const column = FileMan.config.files_grid_fields[i];
            if (columnsRaw[column]) {
                Ext.applyIf(columnsRaw[column], {
                    header: _('fileman_' + column),
                    dataIndex: column
                });
                columns.push(columnsRaw[column]);
            }
        }

        if (!FileMan.config.resource_id)
            columns.push({
                header: _('resource'),
                dataIndex: 'resource_pagetitle',
                sortable: true,
                xtype: 'templatecolumn',
                tpl: '<a href="?a=resource/update&id={resource_id}" target="_blank">{resource_pagetitle}</a>'
            }, {
                header: _('user'),
                dataIndex: 'username',
                sortable: true
            });

        return columns;
    },

    // Form top bar
    getTopBar: function (config) {
        let fields = [];

        if (FileMan.config.resource_id) {
            fields.push({
                xtype: 'button',
                cls: 'primary-button',
                text: _('fileman_btn_upload'),
                handler: this.uploadFiles,
                scope: this
            }, {
                xtype: 'button',
                cls: '',
                text: _('fileman_btn_upload_by_url'),
                tooltip: _('fileman_btn_upload_by_url_tooltip'),
                scope: this,
                handler: this.showUploadByUrlWindow
            });
        }

        let bulk_actions = [
            {
                name: 'open',
                text: _('fileman_open'),
                handler: this.accessFile,
                scope: this
            },
            {
                name: 'close',
                text: _('fileman_private'),
                handler: this.accessFile,
                scope: this
            },
            '-',
            {
                text: _('fileman_reset_downloads'),
                handler: this.resetFileDownloads,
                scope: this
            }
        ];

        // sort actions
        if (FileMan.config.resource_id) {
            bulk_actions.push(
                '-',
                {
                    name: 'sortby_title',
                    text: _('fileman_sortby_title'),
                    handler: this.sortByHandler,
                    scope: this
                },
                {
                    name: 'sortby_name',
                    text: _('fileman_sortby_name'),
                    handler: this.sortByHandler,
                    scope: this
                },
                {
                    name: 'sortby_group',
                    text: _('fileman_sortby_group'),
                    handler: this.sortByHandler,
                    scope: this
                }
            );
        }

        // remove action
        bulk_actions.push(
            '-',
            {
                text: _('remove'),
                handler: this.removeFile,
                scope: this
            }
        );

        fields.push(
            {
                text: _('bulk_actions'),
                menu: bulk_actions
            },
            '->',
            {
                xtype: 'textfield',
                name: 'user',
                width: 160,
                id: config.id + '-search-user-field',
                emptyText: _('user'),
                listeners: {
                    render: {
                        fn: function (tf) {
                            tf.getEl().addKeyListener(Ext.EventObject.ENTER,
                                function () {
                                    this._doSearch(tf);
                                }, this);
                        }, scope: this
                    }
                }
            },
            {
                xtype: 'textfield',
                name: 'query',
                width: 160,
                id: config.id + '-search-field',
                emptyText: _('search'),
                listeners: {
                    render: {
                        fn: function (tf) {
                            tf.getEl().addKeyListener(Ext.EventObject.ENTER,
                                function () {
                                    this._doSearch(tf);
                                }, this);
                        }, scope: this
                    }
                }
            },
            {
                xtype: 'button',
                id: config.id + '-search-clear',
                text: '<i class="icon icon-times"></i>',
                listeners: {
                    click: { fn: this._clearSearch, scope: this }
                }
            }
        );

        return fields;
    },

    showUploadByUrlWindow: function () {
        if (this.windows.UploadByUrl) {
            this.windows.UploadByUrl.destroy()
        }

        var config = {
            xtype: 'fileman-window-upload-by-url',
            class_id: this.config.id,
            id: this.config.id + '-window-upload-by-url'
        };

        this.windows.UploadByUrl = MODx.load(config);
        this.windows.UploadByUrl.show(Ext.EventObject.target);
    },


    uploadByUrl: function (record, callback) {

        var $this = this;
        if (record.url === undefined) {
            MODx.msg.alert(_('error'), _('msgs_empty_url'));
            return false;
        }

        MODx.Ajax.request({
            url: FileMan.config.connectorUrl,
            params: {
                action: 'File\\Upload',
                resource_id: FileMan.config.resource_id,
                url: record.url,
                title: record.title
            },
            listeners: {
                success: {
                    fn: function (r) {
                        if (r.success) {
                            if (typeof callback === 'function') {
                                callback(r);
                            }
                            // TODO: галочку "не закрывать"
                            if (this.windows.UploadByUrl) {
                                this.windows.UploadByUrl.destroy()
                            }

                            this.refresh();
                        }
                    }
                    , scope: this
                }
                , failure: {
                    fn: function (r) {
                        $this.error = true;
                        if (typeof callback === 'function') {
                            callback(r);
                        }
                        MODx.msg.alert(_('error'), r.message);
                    }
                    , scope: this
                }
            }
        });
        return true;
    },

    // Header button handler
    onClick: function (e) {
        var elem = e.getTarget();
        if (elem.nodeName == 'BUTTON') {
            var row = this.getSelectionModel().getSelected();
            if (typeof (row) != 'undefined') {
                var action = elem.getAttribute('action');
                if (action == 'showMenu') {
                    var ri = this.getStore().find('id', row.id);
                    return this._showMenu(this, ri, e);
                }
                else if (typeof this[action] === 'function') {
                    this.menu.record = row.data;
                    return this[action](this, e);
                }
            }
        }
        return this.processEvent('click', e);
    },

    // Get first selected record
    _getSelected: function () {
        var selected = this.getSelectionModel().getSelections();

        for (var i in selected) {
            if (!selected.hasOwnProperty(i)) continue;
            return selected[i].json;
        }

        return null;
    },

    // Get list of selected ID
    _getSelectedIds: function () {
        var ids = [];
        var selected = this.getSelectionModel().getSelections();

        for (var i in selected) {
            if (!selected.hasOwnProperty(i)) continue;
            ids.push(selected[i]['id']);
        }

        return ids;
    },

    // Perform store update with search query
    _doSearch: function (tf, nv, ov) {
        if (tf.name == 'query')
            this.getStore().baseParams.query = tf.getValue();

        if (tf.name == 'user')
            this.getStore().baseParams.user = tf.getValue();
        this.getBottomToolbar().changePage(1);
        this.refresh();
    },

    // Reset search query
    _clearSearch: function (btn, e) {
        this.getStore().baseParams.query = '';
        this.getStore().baseParams.user = '';
        Ext.getCmp(this.config.id + '-search-user-field').setValue('');
        Ext.getCmp(this.config.id + '-search-field').setValue('');
        this.getBottomToolbar().changePage(1);
        this.refresh();
    },

    // Handle changing file order with dragging
    onAfterRowMove: function (dt, sri, ri, sels) {
        var s = this.getStore();
        var sourceRec = s.getAt(sri);
        var belowRec = s.getAt(ri);
        var total = s.getTotalCount();
        var upd = {};

        sourceRec.set('sort_order', sri);
        sourceRec.commit();
        upd[sourceRec.get('id')] = sri;

        var brec;
        for (var x = (ri - 1); x < total; x++) {
            brec = s.getAt(x);
            if (brec) {
                brec.set('sort_order', x);
                brec.commit();
                upd[brec.get('id')] = x;
            }
        }

        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'File\\Sort',
                sort_order: Ext.util.JSON.encode(upd)
            }
        });

        return true;
    }
});
Ext.reg('fileman-grid-files', FileMan.grid.Files);