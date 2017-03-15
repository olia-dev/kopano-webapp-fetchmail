Ext.namespace('Zarafa.plugins.fetchmail.data');

/**
 * @class Zarafa.plugins.fetchmail.data.ResponseHandler
 * @extends Zarafa.core.data.AbstractResponseHandler
 *
 * Fetchmail plugin specific response handler.
 */
Zarafa.plugins.fetchmail.data.FetchmailResponseHandler = Ext.extend(Zarafa.core.data.AbstractResponseHandler, {

	/**
	 * @cfg {Function} successCallback The function which
	 * will be called after success request.
	 */
	successCallback : null,
	
	/**
	 * @param {Object} response Object contained the response data.
	 */
	doAccountsave : function(response) {
		this.successCallback(response);
	},
	
	/**
	 * @param {Object} response Object contained the response data.
	 */
	doAccountdelete : function(response) {
		this.successCallback(response);
	},	
	
	/**
	 * @param {Object} response Object contained the response data.
	 */
	doAccountpollnow : function(response) {
		this.successCallback(response);
	}	
	
	



});

Ext.reg('fetchmail.responsehandler', Zarafa.plugins.fetchmail.data.FetchmailResponseHandler);
