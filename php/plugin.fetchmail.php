<?php
/**
 * Fetchmail plugin.
 *
 * Allows the user to add E-mail accounts to be polled via Fetchmail.
 *
 * Author: Oliver Asselmann <olia@ktah.net>
 */

class Pluginfetchmail extends Plugin {

	public function __construct() {}

	/**
	 * Function initializes the Plugin and registers all hooks
	 */
	function init() {
		$this->registerHook('server.core.settings.init.before');
	}

	/**
	 * Function is executed when a hook is triggered by the PluginManager
	 *
	 * @param string $eventID the id of the triggered hook
	 * @param mixed $data object(s) related to the hook
	 */
	function execute($eventID, &$data) {
		switch($eventID) {
			case 'server.core.settings.init.before' :
				$this->injectPluginSettings($data);
				break;
		}
	}

	/**
	 * Called when the core Settings class is initialized and ready to accept fetchmail default
	 * @param Array $data Reference to the data of the triggered hook
	 */
	function injectPluginSettings(&$data) {
		$data['settingsObj']->addSysAdminDefaults(Array(
			'zarafa' => Array(
				'v1' => Array(
					'plugins' => Array(
						'fetchmail' => Array(
							'enable'            => PLUGIN_FETCHMAIL_USER_DEFAULT_ENABLE,
						)

					)
				)
			)
		));
	}
}
?>
