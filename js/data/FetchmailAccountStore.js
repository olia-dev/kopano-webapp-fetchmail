Ext.namespace('Zarafa.plugins.fetchmail.data');

/**
 * @class Zarafa.plugins.fetchmail.data.FetchmailAccountStore
 * @extends Zarafa.core.data.ListModuleStore
 * @xtype fetchmail.accountstore
 * Store specific for Fetchmail Plugin which creates a FetchmailAccountRecord.
 */
Zarafa.plugins.fetchmail.data.FetchmailAccountStore = Ext.extend(Zarafa.core.data.ListModuleStore, {
	/**
	 * @constructor
	 * @param config Configuration object
	 */
	constructor : function(config)
	{
		config = config || {};

		Ext.applyIf(config, {
			autoLoad : true,
			remoteSort: false,
			reader : new Zarafa.plugins.fetchmail.data.JsonAccountReader(),
			writer : new Zarafa.core.data.JsonWriter(),
			proxy  : new Zarafa.core.data.IPMProxy({
				listModuleName: 'pluginfetchmailmodule',
				itemModuleName: 'pluginfetchmailmodule'
			})
		});

		Zarafa.plugins.fetchmail.data.FetchmailAccountStore.superclass.constructor.call(this, config);
	}
});

Ext.reg('fetchmail.accountstore', Zarafa.plugins.fetchmail.data.FetchmailAccountStore);
