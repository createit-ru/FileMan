FileMan.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'fileman-panel-home',
            renderTo: 'fileman-panel-home-div'
        }]
    });
    FileMan.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(FileMan.page.Home, MODx.Component);
Ext.reg('fileman-page-home', FileMan.page.Home);