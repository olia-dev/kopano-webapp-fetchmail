Ext.namespace('Zarafa.plugins.fetchmail.dialogs');

/**
 * @class Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogContentPanel
 * @extends Zarafa.core.ui.ContentPanel
 * @xtype fetchmailplugin.accountdialogcontentpanel
 * 
 * Dialog to create or modify a fetchmail account.
 * 
 * The Dialog displays an FetchmailAccountDialogDetailsPanel inside an ContentPanel.
 */
Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogContentPanel = Ext.extend(Zarafa.core.ui.ContentPanel, {

    /**
     * @constructor
     * @param config Configuration structure
     */
    constructor: function (config) {
        config = config || {};
        
        var title = config.record.get('src_user');
        if(title == null)
        	title = dgettext('plugin_fetchmail', 'New Account');
        
        Ext.applyIf(config, {

            xtype: 'fetchmailplugin.accountdialogcontentpanel',
            layout    : 'fit',
            modal     : true,
            width     : 500,
            height	  : 500,
            title     : dgettext('plugin_fetchmail', title),
            items     : [{
                xtype: 'fetchmailplugin.fetchmailaccountdialogdetailspanel',
                record : config.record,
                buttonAlign: 'center',
				buttons: [{
					text: _('Save', 'plugin_fetchmail'),
					handler: this.onSaveBtn,
					scope: this
				},{
					text: _('Cancel', 'plugin_fetchmail'),
					handler: this.close,
					scope: this
				}]
            }]
        });

        Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogContentPanel.superclass.constructor.call(this, config);
    },
    
	/**
	 * Save form function that either creates or modifys an existing entry on the server.
	 */
    onSaveBtn: function()
	{
		var form = this.get(0);

		form.saveFetchmailAccount();
	}
    
});

Ext.reg('fetchmailplugin.accountdialogcontentpanel', Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogContentPanel);