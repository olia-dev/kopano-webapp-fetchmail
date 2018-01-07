# kopano-webapp-fetchmail

The Plugin allows the user to configure one or more accounts to be polled via fetchmail inside of Kopano WebApp.

It's recommended to read the fetchmail documentation (http://www.fetchmail.info/fetchmail-man.html or `man fetchmail`) to understand how fetchmail delivers emails to the end user. 
With this plugin the daemon retrieves and distributes the emails via a MTA on the host machine. 

# Why use the Plugin?

It unifies your mailbox. Instead of checking multiple different email addresses, they can all be retrieved and delivered to your kopano mailbox.
Also if your MTA is configured to check for Spam (spamassassin) or Viruses (clamav), you can be sure that all your email is checked before delivery.

# Screenshots

Fetchmail Plugin Settings:
![Fetchmail Plugin Settings](/screenshots/fetchmail_settings_overview.png?raw=true "Fetchmail Plugin Settings")
Create a new Account:
![Create new Account](/screenshots/fetchmail_create_new_account_with_error.png?raw=true "Create a new Account")
Account Status in Plugin Settings and Log Message:
![Fetchmail Plugin Settings with Error](/screenshots/fetchmail_settings_overview_with_error.png?raw=true "Fetchmail Plugin Settings with Error")
![Modify Account and Log Message](/screenshots/fetchmaiL_modify_account.png?raw=true "Modify Account and Log Message")

# Requirements

- fetchmail
- libproc-daemon-perl or Proc::Daemon from cpan
- MTA on the host machine (postfix) 
- Database to store account informations (At the moment only 'mysql' is supported)

# How to install

- Install the Requirements.
- Download and unzip the fetchmail-1.0.zip to the 'plugins' folder in your kopano-webapp installation (default: `/usr/share/kopano-webapp/plugins/`).

# Configuration

- Copy the `example.config.php` in the `<kopano-webapp>/plugins/fetchmail` folder to `config.php` and customize the options.
- Create a user to run the fetchmail daemon (default: fetchmail/nogroup) with a home directory.
	- If you used your distros package manger to install `fetchmail` a user (fetchmail/nogroup) should have been created.
	- Manual: `useradd -d /var/lib/fetchmail -g nogroup -m -N -s /bin/false fetchmail`
	- Manual: Set the rights for the home directory to 600.
- Edit the `<kopano-webapp>/plugins/fetchmail/php/daemon/kopano_fetchmail.pl` if you used non-standard options.
- Start the daemon with `perl kopano_fetchmail.pl --start` and create a startup script. (see `kopano_fetchmail.service.example` for a systemd config).

# How to enable

- Go to settings section.
- Go to plugins tab.
- Enable the fetchmail plugin and reload webapp.
- You should find a new entry in the settings section.

# How to disable

- Go to settings section.
- Go to plugins tab.
- Disable the fetchmail plugin and reload webapp.

# Backup

- Database with fetchmail account informations.
- Home directory of the `fetchmail` user (default /var/lib/fetchmail) where a list of already fetched emails (POP3) is saved.

# How to change the AES Key used for Password Encryption of an existing Installation

If you need to change the Key used to encrypt the Passwords stored in the Database for any reason (Security Breach, etc), a function "changeAESKey" is implemented in the class PluginFetchmailModule.
To call this function, you have to create a simple PHP script and execute it. 

An example, of how to call this function:

```php
<?php

$oldkey="changethis!";
$newkey="changethis!";

define('BASE_PATH', "/usr/share/kopano-webapp/");
define('PATH_PLUGIN_DIR' , "plugins" );

require_once (BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/config.php");
require_once (BASE_PATH . PATH_PLUGIN_DIR . "/fetchmail/php/class.pluginfetchmailmodule.php");

$fetchmailModule = new PluginFetchmailModule(null, null);

$fetchmailModule->changeAESKey($oldkey, $newkey);

?>
```

Save the above code to (for example): `/usr/share/kopano-webapp/plugins/fetchmail/changeAESKey.php` (Modify the parameters $oldkey and $newkey).
Afterwards call it via CLI: `php /usr/share/kopano-webapp/plugins/fetchmail/changeAESKey.php`

Remember to change the parameter `PLUGIN_FETCHMAIL_PASSWORDS_AES_KEY` in `/usr/share/kopano-webapp/plugins/fetchmail/config.php` !


# How to uninstall

- Stop the daemon with `perl kopano_fetchmail.pl --stop`
- Remove the `<kopano-webapp>/plugins/fetchmail` folder.
- Drop the table from your Database.
- Delete the `fetchmail` user and home directory.

# Notes

Feedback and Bug Reports are always welcome!

# Project Contributors

Andreas Brodowski (aka dw2412)

# License

The Fetchmail Plugin is available under the GNU AGPLv3 license. See LICENSE file.



