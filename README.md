# kopano-webapp-fetchmail

The Plugin allows the user to configure one or more accounts to be polled via fetchmail inside of Kopano WebApp.

It's recommended to read the fetchmail documentation (http://www.fetchmail.info/fetchmail-man.html or `man fetchmail`) to understand how fetchmail delivers emails to the end user. 
With this plugin the daemon retrieves and distributes the emails via a MTA on the host machine. 

# Why use the Plugin?

It unifies your mailbox. Instead of checking multiple different email addresses, they can all be retrieved and delivered to your kopano mailbox.
Also if your MTA is configured to check for Spam (spamassassin) or Viruses (clamav), you can be sure that all your email is checked before delivery.

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

# How to uninstall

- Stop the daemon with `perl kopano_fetchmail.pl --stop`
- Remove the `<kopano-webapp>/plugins/fetchmail` folder.
- Drop the table from your Database.
- Delete the `fetchmail` user and home directory.

# Notes

Feedback and Bug Reports are always welcome!

# License

The Fetchmail Plugin is available under the GNU AGPLv3 license. See LICENSE file.



