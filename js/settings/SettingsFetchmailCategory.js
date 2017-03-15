Ext.namespace('Zarafa.plugins.fetchmail.settings');

/**
 * @class Zarafa.plugins.fetchmail.settings.SettingsFetchmailCategory
 * @extends Zarafa.settings.ui.SettingsCategory
 * @xtype zarafa.settingsfetchmailcategory
 *
 * Creates a new SettingsCategory to show the SettingsFetchmailWidget.
 * 
 */
Zarafa.plugins.fetchmail.settings.SettingsFetchmailCategory = Ext.extend(Zarafa.settings.ui.SettingsCategory, {
	/**
	 * @constructor
	 * @param {Object} config Configuration object
	 */
	constructor : function(config)
	{
		config = config || {};

		Ext.applyIf(config, {
			title : _('Fetchmail', 'plugin_fetchmail'),
			iconCls : 'icon_fetchmail_settings',
			xtype : 'zarafa.settingsfetchmailcategory',
			items : [{
				xtype : 'zarafa.settingsfetchmailwidget',
				settingsContext : config.settingsContext
			},
				container.populateInsertionPoint('context.settings.category.fetchmail', this)
			]
		});

		Zarafa.plugins.fetchmail.settings.SettingsFetchmailCategory.superclass.constructor.call(this, config);
	}
});

Ext.reg('zarafa.settingsfetchmailcategory', Zarafa.plugins.fetchmail.settings.SettingsFetchmailCategory);
