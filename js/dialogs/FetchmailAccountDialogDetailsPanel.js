Ext.namespace('Zarafa.plugins.fetchmail.dialogs');

/**
 * @class Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogContentPanel
 * @extends Ext.form.FormPanel
 * @xtype fetchmailplugin.fetchmailaccountdialogdetailspanel
 *
 * FormPanel which is shown inside FetchmailAccountDialogContentPanel.
 * 
 * The Panel displays either an already existing fetchmail account, or an empty form to create a new one. 
 * 
 */
Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogDetailsPanel = Ext.extend(Ext.form.FormPanel, {

    /**
     * @cfg {Zarafa.plugins.fetchmail.data.FetchmailAccountRecord} record The account record which
     * is either being displayed or created.
     */
    record : null,

    /**
     * @constructor
     * @param config Configuration structure
     */
    constructor : function (config) {
        config = config || {};

        Ext.applyIf(config, {
            xtype : 'fetchmailplugin.fetchmailaccountdialogdetailspanel',
            layout : 'form',
            labelAlign : 'top',
            anchor: '100%',            
            defaults : {
            	anchor: '100%'
            },
            items : [{
            	xtype:'textfield',
                fieldLabel : dgettext('plugin_fetchmail', 'EntryID'),
                name : "entryid",
                id : 'entryid',
                hideLabel: true,
                hidden : true,
                readOnly: true            
            }, {
            	xtype:'textfield',
                fieldLabel : dgettext('plugin_fetchmail', 'User mail address'),
                name : "kopano_mail",
                id : 'kopano_mail',
                hideLabel: true,
                hidden : true,
                readOnly: true,
                value : container.getUser().getEmailAddress()
            }, {
            	xtype:'textfield',
                fieldLabel : dgettext('plugin_fetchmail', 'Mail Server'),
                name : "src_server",
                id : 'src_server',
                listeners : {
    				'change': function() {
    					this.resetErrorMessage('src_server');
    				},
    				scope: this
    			}
            }, {
            	//build an hbox to show protocol/port next to each other
            	xtype: 'container',
            	layout: 'hbox',
            	align: 'middle',
            	pack: 'center',
                border: false,
                defaults: {
                	layout: 'form',
                	flex: 0.5,
                	border: false
                },
                //nest the items so that form layout is used for the actual displayfield/combobox
                items: [{ 
                	items: [{ 
                		xtype: 'combo',
                		fieldLabel: dgettext('plugin_fetchmail', 'Mail Protocol'),
                		hiddenName: 'src_protocol',
                		mode : 'local',
                		ref : '../../src_protocol',
                		id : 'src_protocol',
                		store: new Ext.data.SimpleStore({
                			data: [
                			       [Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAP, Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAP_STRING],
                			       [Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAPS, Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAPS_STRING],
                			       [Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3, Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3_STRING],
                			       [Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3S, Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3S_STRING]
                			       ],
                			       id: 0,
                			       fields: ['protocol_id', 'protocol_text']
                		}),
                		valueField: 'protocol_id',
                		displayField: 'protocol_text',
                		triggerAction: 'all',
                		editable: false,
                		listeners : {
                			'change': function() {
                				this.resetErrorMessage('src_protocol');
                				//automatically set a default port value after choosing a protocol
                				switch(this.src_protocol.getValue()) {
                					case  Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAP:
                						this.src_port.setValue('143');
                						break;
                					case Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_IMAPS:
                						this.src_port.setValue('993');
                						break;
                					case Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3:
                						this.src_port.setValue('110');
                						break;
                					case Zarafa.plugins.fetchmail.FETCHMAIL_PROTOCOL_POP3S:
                						this.src_port.setValue('995');
                						break;
                				}
                            },
                			scope: this
                		}
                	}]
                }, {
                	items: [{
	                	xtype: 'textfield',
            			name : 'src_port',
	                   	fieldLabel : dgettext('plugin_fetchmail', 'Server Port'),
	                   	ref : '../../src_port',
	                   	id: 'src_port',
	                    listeners : {
	        				'change': function() {
	        					this.resetErrorMessage('src_port');
	        				},
	        				scope: this
	        			}
                	}]
                }] 
            }, {
            	xtype:'textfield',
                fieldLabel : dgettext('plugin_fetchmail', 'Account Login'),
                name : "src_user",
                id : 'src_user',
                listeners : {
    				'change': function() {
    					this.resetErrorMessage('src_user');
    				},
    				scope: this
    			}
            }, {
            	xtype:'textfield',
                fieldLabel : dgettext('plugin_fetchmail', 'Account Password'),
                name : "src_password",
                id : 'src_password',
                inputType: 'password',
                listeners : {
    				'change': function() {
    					this.resetErrorMessage('src_password');
    				},
    				scope: this
    			}
            }, {
            	xtype:'textfield',
                fieldLabel : dgettext('plugin_fetchmail', 'Polling Frequency (in minutes)'),
                name : "polling_freq",
                id : 'polling_freq',
                listeners : {
    				'change': function() {
    					this.resetErrorMessage('polling_freq');
    				},
    				scope: this
    			}
            }, {
            	xtype: 'combo',
            	fieldLabel: dgettext('plugin_fetchmail', 'Polling Type (Warning: FETCHALL deletes the mails from the Remote Server)'),
            	hiddenName: 'src_polling_type',
            	id : 'src_polling_type',
            	mode : 'local',
            	store: new Ext.data.SimpleStore({
            		data: [
            		    [Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_FETCHALL, Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_FETCHALL_STRING],
            			[Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_KEEP, Zarafa.plugins.fetchmail.FETCHMAIL_POLLING_TYPE_KEEP_STRING]
            		],
            		id: 0,
            		fields: ['polling_type_id', 'polling_type_text']
            	}),
            	valueField: 'polling_type_id',
            	displayField: 'polling_type_text',
            	triggerAction: 'all',
            	editable: false,
                listeners : {
    				'change': function() {
    					this.resetErrorMessage('src_polling_type');
    				},
    				scope: this
    			}
            }, {
            	xtype : 'textarea',
                fieldLabel : dgettext('plugin_fetchmail', 'Last Log Message'),
                autoHeight : true,
                readOnly : true,
                hidden : true,
    			hideMode : 'visibility',
    			grow : true,
                growMax : 100,
                name : "last_log_message",
                ref : 'last_log_message',
                emptyText: _('No Log message found.', 'plugin_fetchmail')
            }, {
    			hideLabel : true,
    			hidden : true,
    			hideMode : 'visibility',
    			xtype : 'displayfield',
    			ref : 'error_notification',
    			name : 'error_notification',
    			cls: 'zarafa-smime-invalid-text',
    			value: _('Something went wrong. Please contact your Administrator.', 'plugin_fetchmail')
            }],
            listeners : {
                afterlayout : this.onAfterLayout,
                scope : this
            }
        });

        Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogDetailsPanel.superclass.constructor.call(this, config);
    },

    /**
     * Function which handles the after layout event of FetchmailAccountDialogDetailsPanel.
     * Loads the information out of a FetchmailAccountRecord. Also hides the last_log_message window if its empty.
     */
    onAfterLayout : function ()
    {
        this.getForm().loadRecord(this.record);
        //creating a new account is strange if a non-editable last_log_message field is shown.
        if(this.last_log_message.getValue() != '')
			this.last_log_message.show();
    },
    
    
	/**
	 * Reset the error message when user changes one of the fields.
	 */
	resetErrorMessage: function(id)
	{
		var cmp = Ext.getCmp(id);
		if(cmp)
			cmp.getEl().removeClass('fetchmail-parameter-false');
		this.error_notification.hide();
		this.error_notification.setValue(_('Something went wrong. Please contact your Administrator.', 'plugin_fetchmail'));
		
	},
    
	/**
	 * Sends the account information to the backend to validate&save.
	 */
    saveFetchmailAccount: function()
	{
    	var data = this.getForm().getFieldValues();
		
    	container.getRequest().singleRequest(
    		'pluginfetchmailmodule',
			'save',
			data,
			new Zarafa.plugins.fetchmail.data.FetchmailResponseHandler({
				successCallback : this.onSaveFetchmailAccountRequest.createDelegate(this)
				})
		);
	},
	
	/**
	 * Handler for successCallback of the saveFetchmailAccount request.
	 */
	onSaveFetchmailAccountRequest: function(response) {
		if (response.code === Zarafa.plugins.fetchmail.FETCHMAIL_ACCOUNT_SAVE_SUCCESS_RESPONSE) {
			container.getNotifier().notify('info.saved', _('Fetchmail Message', 'plugin_fetchmail'), _('Account saved succesfully!', 'plugin_fetchmail'));
			this.dialog.close();
		} else if (response.code === Zarafa.plugins.fetchmail.FETCHMAIL_ACCOUNT_SAVE_ERROR_PARAMETER_RESPONSE){
			this.error_notification.setValue(_(response.message, 'plugin_fetchmail'));
			var cmp = Ext.getCmp(response.parameter);
			if(cmp)
				cmp.getEl().addClass('fetchmail-parameter-false');
			this.error_notification.show();
		} else {
			this.error_notification.setValue(_('Something went wrong. Please contact your Administrator.', 'plugin_fetchmail'));
			this.error_notification.show();
		}
	}
    
});

Ext.reg('fetchmailplugin.fetchmailaccountdialogdetailspanel', Zarafa.plugins.fetchmail.dialogs.FetchmailAccountDialogDetailsPanel);