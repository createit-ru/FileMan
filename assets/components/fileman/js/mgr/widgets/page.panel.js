FileMan.panel.Page = function (config) {
    config = config || {};
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',

        hideMode: 'offsets',
        items: [
            {
                xtype: 'fileman-grid-files',
                cls: 'main-wrapper',
                record: config.record
            }]
    });
    FileMan.panel.Page.superclass.constructor.call(this, config);
};
Ext.extend(FileMan.panel.Page, MODx.Panel);
Ext.reg('fileman-panel-page', FileMan.panel.Page);