Ext.namespace('Zarafa.plugins.fetchmail.settings');

/**
 * @class Zarafa.plugins.fetchmail.settings.SettingsFetchmailWidget
 * @extends Zarafa.settings.ui.SettingsWidget
 * @xtype zarafa.settingsfetchmailwidget
 *
 * The widget displayed in the kopano Settings to configure Fetchmail.
 * 
 */
Zarafa.plugins.fetchmail.settings.SettingsFetchmailWidget = Ext.extend(Zarafa.settings.ui.SettingsWidget, {

	/**
	 * @constructor
	 * @param {Object} config Configuration object
	 */
	constructor : function(config)
	{
		config = config || {};

		var store = new Zarafa.plugins.fetchmail.data.FetchmailAccountStore();
		Ext.applyIf(config, {
			title : _('Fetchmail Configuration', 'plugin_fetchmail'),
			items : [{
				xtype : 'container',
				layout: 'fit',
				itemId: 'container',
				items : [{
					xtype : 'grid',
					name : _('Accounts', 'plugin_fetchmail'),
					ref : '../accountGrid',
					height : 400,
					itemId : 'accountGrid', 
					store : store,
					viewConfig : {
						forceFit : true,
						deferEmptyText: false,
						emptyText: '<div class="emptytext">' + _('No accounts configured', 'plugin_fetchmail') + '</div>',
						getRowClass: function(record) {
							//check "man fetchmail" why we only care for status codes greater than 1
							if(record.get('last_status_code') > 1)
								return 'fetchmail-grid-polling-error';
					    } 
					},
					columns : [{
						dataIndex : 'last_status_code',
						header : '&#160;',
						resizable : false,
						sortable : false,
						hideable : false,
						width : 10,
						renderer : function(value, metadata, record, rowIndex, colIndex, store) {
							if(value < 0)
								metadata.css = metadata.css + ' icon_fetchmail_unknown ';
							else if(value > 1)
								metadata.css = metadata.css + ' icon_fetchmail_failure ';
							else
								metadata.css = metadata.css + ' icon_fetchmail_success '; 
							
							metadata.attr = 'ext:qtip="' + (value) + '"';
							
							return '&nbsp;';
						}
					},{
						dataIndex : 'src_server',
						header : _('Mail Server', 'plugin_fetchmail'),
						renderer : Ext.util.Format.htmlEncode
					},{
						dataIndex : 'src_port',
						header : _('Server Port', 'plugin_fetchmail'),
						renderer : Ext.util.Format.htmlEncode,
						width: 70
					},{
						dataIndex : 'src_protocol',
						header : _('Mail Protocol', 'plugin_fetchmail'),
						width: 80,
						renderer : function($value) {
							switch(parseInt($value)) {
								case Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAP:
									return Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAP_STRING;
									break;
								case Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAPS:
									return Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAPS_STRING;
									break;
								case Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3:
									return Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3_STRING;
									break;
								case Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3S:
									return Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3S_STRING;
									break;
								default:
									return _('Unknown Polling Type', 'plugin_fetchmail');
										
							}
						}
					},{
						dataIndex : 'src_polling_type',
						header : _('Polling Type', 'plugin_fetchmail'),
						width: 80,
						renderer : function($value) {
							switch(parseInt($value)) {
								case Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_FETCHALL:
									return Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_FETCHALL_STRING;
									break;
								case Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_KEEP:
									return Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_KEEP_STRING;
									break;
								default:
									return _('Unknown Polling Type', 'plugin_fetchmail');
										
							}
						}
					},{
						dataIndex : 'src_user',
						header : _('Account Login', 'plugin_fetchmail'),
						renderer : Ext.util.Format.htmlEncode
					},{
						dataIndex : 'polling_freq',
						header : _('Polling Frequency', 'plugin_fetchmail'),
						renderer : Ext.util.Format.htmlEncode
					},{
						dataIndex : 'last_polling',
						header : _('Last Polling', 'plugin_fetchmail'),
						renderer : Ext.util.Format.htmlEncode,
						width: 220
					},{
						dataIndex : 'last_log_message',
						header : _('Last Log Message', 'plugin_fetchmail'),
						renderer : Ext.util.Format.htmlEncode
					}],
					buttons : [{
						text : _('New Account', 'plugin_fetchmail'),
						ref : '../../../newAccountBtn',
						handler : this.onNewAccountBtn,
						scope : this
					},{
						text : _('Modify', 'plugin_fetchmail'),
						ref : '../../../modifyBtn',
						handler : this.onModifyBtn,
						scope : this
					},{
						text : _('Remove Account', 'plugin_fetchmail'),
						ref : '../../../removeAccountBtn',
						handler : this.onRemoveAccountBtn,
						scope : this
					},{
						text : _('Poll Now', 'plugin_fetchmail'),
						ref : '../../../pollNowBtn',
						handler : this.onPollNowBtn,
						scope : this
					},{
						text : _('Refresh', 'plugin_fetchmail'),
						ref : '../../../refresh',
						handler : this.onRefresh,
						scope : this
					}],
					listeners   : {
						rowdblclick: this.onRowDblClick,
						scope      : this
					}
				}]
			}]
		});

		Zarafa.plugins.fetchmail.settings.SettingsFetchmailWidget.superclass.constructor.call(this, config);
	},
	
	/**
	 * Function which handles the click event on the "New Account" button, displays
	 * a Dialog for the user to add a new entry.
	 */
	onNewAccountBtn : function()
	{
		//creates an empty FetchmailAccountRecord and then calls the same function to modify.
		this.showModifyCreateDialog(new Zarafa.plugins.fetchmail.data.FetchmailAccountRecord({}));
	},


	/**
	 * Function which handles the click event on the "Modify" button, displays
	 * a Dialog for the user to modify the existing entry.
	 */
	onModifyBtn : function()
	{
		this.showModifyCreateDialog(this.getComponent('container').getComponent('accountGrid').getSelectionModel().getSelected());
	},

	/**
	 * Function which handles the click event on the "Remove Account" button, removes the 
	 * account from the Store.
	 */
	onRemoveAccountBtn : function()
	{
		var rc = this.getComponent('container').getComponent('accountGrid').getSelectionModel().getSelected();
    	container.getRequest().singleRequest(
        		'pluginfetchmailmodule',
    			'delete',
    			rc.data['entryid'],
    			new Zarafa.plugins.fetchmail.data.FetchmailResponseHandler({
    				successCallback : this.onDeleteFetchmailAccountRequest.createDelegate(this)
    				})
    		);
		
		
	},

	/**
	 * Function which handles the click event on the "Poll Now" button, sets the last_polling timestamp to (last_polling - 2*polling_frequency).
	 * After that the background task/cronjob/daemon should poll this account.
	 */
	onPollNowBtn : function()
	{
		var rc = this.getComponent('container').getComponent('accountGrid').getSelectionModel().getSelected();
    	container.getRequest().singleRequest(
        		'pluginfetchmailmodule',
    			'pollnow',
    			rc.data['entryid'],
    			new Zarafa.plugins.fetchmail.data.FetchmailResponseHandler({
    				successCallback : this.onPollNowRequest.createDelegate(this)
    				})
    		);
	},
	
	/**
	 * Function which refreshes the store records from the server.
	 */
	onRefresh : function()
	{
		this.accountGrid.getStore().load();
	},

	/**
	 * Function is called if a row in the grid gets double clicked and opens the modify Dialog.
	 * @param {Ext.grid.GridPanel} grid The Grid on which the user double-clicked
	 * @param {Number} rowIndex The Row number on which was double-clicked.
	 */
	onRowDblClick : function (grid, rowIndex)
	{
		var record = grid.getStore().getAt(rowIndex);
		this.showModifyCreateDialog(record);
	},
	
	/**
	 * Handler for successCallback of the delete FetchmailAccount request.
	 */
	onDeleteFetchmailAccountRequest: function(response) {
		if (response.code === Zarafa.plugins.fetchmail.FETCHMAIL_ACCOUNT_DELETE_SUCCESS_RESPONSE) {
			container.getNotifier().notify('info.saved', _('Fetchmail Message', 'plugin_fetchmail'), _('Account deleted succesfully.', 'plugin_fetchmail'));
			this.onRefresh();
		} else {
			container.getNotifier().notify('error', _('Fetchmail Message', 'plugin_fetchmail'), _(response.error, 'plugin_fetchmail'));
			this.onRefresh();
		}
	},
	
	/**
	 * Handler for successCallback of the pollNow request.
	 */
	onPollNowRequest: function(response) {
		if (response.code === Zarafa.plugins.fetchmail.FETCHMAIL_ACCOUNT_POLL_NOW_SUCCESS_RESPONSE) {
			container.getNotifier().notify('info.saved', _('Fetchmail Message', 'plugin_fetchmail'), _('Account marked for immediate polling.', 'plugin_fetchmail'));
			this.onRefresh();
		} else {
			container.getNotifier().notify('error', _('Fetchmail Message', 'plugin_fetchmail'), _(response.error, 'plugin_fetchmail'));
			this.onRefresh();
		}
	},
	
	/**
	 * Function wich displays an FetchmailAccountDialogContentPanel.
	 * @param {FetchmailAccountRecord} record A record to display
	 */
	showModifyCreateDialog : function(record)
	{
		Zarafa.core.data.UIFactory.openLayerComponent(Zarafa.core.data.SharedComponentType['plugin.fetchmail.dialogs.accountdialogcontentpanel'], undefined, {
			manager : Ext.WindowMgr,
			record : record,
			listeners : {
				'destroy': this.onRefresh,
	 			scope: this
			}
		});		
	}
});

Ext.reg('zarafa.settingsfetchmailwidget', Zarafa.plugins.fetchmail.settings.SettingsFetchmailWidget);
