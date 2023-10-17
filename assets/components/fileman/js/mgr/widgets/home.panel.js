FileMan.panel.Home = function (config) {
    config = config || {};
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',
        /*
         stateful: true,
         stateId: 'fileman-panel-home',
         stateEvents: ['tabchange'],
         getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
         */
        hideMode: 'offsets',
        items: [{
            xtype: 'modx-header',
            html: _('fileman')
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            border: true,
            hideMode: 'offsets',
            items: [{
                title: _('fileman_files'),
                layout: 'anchor',
                items: [{
                    html: _('fileman_intro_msg'),
                    cls: 'panel-desc',
                }, {
                    xtype: 'fileman-grid-files',
                    cls: 'main-wrapper',
                }]
            }]
        }]
    });
    FileMan.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(FileMan.panel.Home, MODx.Panel);
Ext.reg('fileman-panel-home', FileMan.panel.Home);
