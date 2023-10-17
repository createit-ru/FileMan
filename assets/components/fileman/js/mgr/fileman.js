let FileMan = function (config) {
    config = config || {};
    FileMan.superclass.constructor.call(this, config);
};
Ext.extend(FileMan, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('fileman', FileMan);

FileMan = new FileMan();