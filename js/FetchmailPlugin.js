Ext.namespace('Zarafa.plugins.fetchmail');

/**
 * @class Zarafa.plugins.fetchmail.FetchmailPlugin
 * @extends Zarafa.core.Plugin
 *
 * Plugin allows the user to configure one or more accounts to be polled via fetchmail inside of Kopano Webapp
 */
Zarafa.plugins.fetchmail.FetchmailPlugin = Ext.extend(Zarafa.core.Plugin, {

	
	/**
     * @constructor
     * @param {Object} config Configuration object
     *
     */
    constructor : function (config)
    {
            config = config || {};
            Zarafa.plugins.fetchmail.FetchmailPlugin.superclass.constructor.call(this, config);
    },
    
	/**
	 * Called after constructor.
	 * Registers insertion points.
	 * @protected
	 */
	initPlugin : function()
	{
		// Register categories for the settings
		this.registerInsertionPoint('context.settings.categories', this.createSettingsCategory, this);
		
		// register my modify/create dialog
		Zarafa.core.data.SharedComponentType.addProperty('plugin.fetchmail.dialogs.accountdialogcontentpanel');
		
		Zarafa.plugins.fetchmail.FetchmailPlugin.superclass.initPlugin.apply(this, arguments);
	},
	
	/**
	 * Bid for the type of shared component and the given record.
	 * @param {Zarafa.core.data.SharedComponentType} type Type of component a context can bid for.
	 * @param {Ext.data.Record} record Optionally passed record.
	 * @returns {Number}
	 */
	bidSharedComponent : function (type, record)
	{
		var bid = -1;
		switch (type) {
			case Zarafa.core.data.SharedComponentType['plugin.fetchmail.dialogs.accountdialogcontentpanel']:
				bid = 1;
				break;
		}
		return bid;
	},

	/**
	 * Will return the reference to the shared component.
	 * Based on the type of component requested a component is returned.
	 * @param {Zarafa.core.data.SharedComponentType} type Type of component a context can bid for.
	 * @param {Zarafa.mail.dialogs.MailCreateContentPanel} owner Optionally passed panel
	 * @return {Ext.Component} Component
	 */
	getSharedComponent : function(type, record) {
		var component;

		switch(type) {
			case Zarafa.core.data.SharedComponentType['plugin.fetchmail.dialogs.accountdialogcontentpanel']:
				component = Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogContentPanel;
				break;
		}

		return component;
	},

	/**
	 * Creates a category in settings for fetchmail
	 * @return {settingsfetchmailcategory}
	 */
	createSettingsCategory : function(insertionName, settingsMainPanel, settingsContext)
	{
		return {
			xtype : 'zarafa.settingsfetchmailcategory',
			settingsContext : settingsContext
		};
	}
});

Zarafa.onReady(function() {
	//the protocol/polling type is saved as a constant in the DB
	Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAP = 1;
	Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAPS = 2;
	Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3 = 5;
	Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3S = 6;
	Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_FETCHALL = 1;
	Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_KEEP = 5;
	
	//the protocol/polling type integer <=> string constants
	Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAP_STRING = "IMAP";
	Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAPS_STRING = "IMAP+SSL";
	Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3_STRING = "POP3";
	Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3S_STRING = "POP3+SSL";
	Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_FETCHALL_STRING = "FETCHALL";
	Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_KEEP_STRING = "KEEP";
	
	//success/error constants
	Zarafa.plugins.fetchmail.FETCHMAIL_ACCOUNT_SAVE_SUCCESS_RESPONSE = 42;
	Zarafa.plugins.fetchmail.FETCHMAIL_ACCOUNT_SAVE_ERROR_PARAMETER_RESPONSE = 43;
	Zarafa.plugins.fetchmail.FETCHMAIL_ACCOUNT_DELETE_SUCCESS_RESPONSE = 142;
	Zarafa.plugins.fetchmail.FETCHMAIL_ACCOUNT_POLL_NOW_SUCCESS_RESPONSE = 1142;
	
	
	container.registerPlugin(new Zarafa.core.PluginMetaData({
		name : 'fetchmail',
		displayName : _('Fetchmail Plugin', 'plugin_fetchmail'),
		about : Zarafa.plugins.fetchmail.ABOUT,
		pluginConstructor : Zarafa.plugins.fetchmail.FetchmailPlugin
	}));
});
