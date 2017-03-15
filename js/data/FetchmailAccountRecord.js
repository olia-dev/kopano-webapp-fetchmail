Ext.namespace('Zarafa.plugins.fetchmail.data');

Zarafa.plugins.fetchmail.data.FetchmailAccountRecordFields = [
	{name: 'entryid', type: 'string'},                                                         
	{name: 'kopano_uid', type: 'string'},
	{name: 'kopano_mail', type: 'string'},
	{name: 'src_server', type: 'string'},
	{name: 'src_port', type: 'string'},
	{name: 'src_protocol', type: 'string'},
	{name: 'src_polling_type', type: 'string'},
	{name: 'src_user', type: 'string'},
	{name: 'src_password', type: 'string'},
	{name: 'polling_freq', type: 'string'},
	{name: 'last_polling', type: 'date', dateFormat: 'timestamp'},
	{name: 'last_update', type: 'date', dateFormat: 'timestamp'},
	{name: 'last_status_code', type: 'string'},
	{name: 'last_log_message', type: 'string'}
];

Zarafa.plugins.fetchmail.data.FetchmailAccountRecord = Ext.extend(Zarafa.core.data.IPMRecord, {});
Zarafa.core.data.RecordCustomObjectType.addProperty('ZARAFA_FETCHMAIL');
Zarafa.core.data.RecordFactory.addFieldToCustomType(Zarafa.core.data.RecordCustomObjectType.ZARAFA_FETCHMAIL, Zarafa.plugins.fetchmail.data.FetchmailAccountRecordFields);

Zarafa.core.data.RecordFactory.addListenerToCustomType(Zarafa.core.data.RecordCustomObjectType.ZARAFA_FETCHMAIL, 'createphantom', function(record)
{
	// Phantom records must always be marked as opened (they contain the full set of data)
	record.afterOpen();
});

Zarafa.core.data.RecordFactory.setBaseClassToCustomType(Zarafa.core.data.RecordCustomObjectType.ZARAFA_FETCHMAIL, Zarafa.plugins.fetchmail.data.FetchmailAccountRecord);
