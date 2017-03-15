#!/usr/bin/perl
use v5.10.0;
use strict;
$|=1;
use warnings;
use Proc::Daemon;
use Getopt::Long;
use Pod::Usage;
use MIME::Base64;
use Sys::Syslog qw(:standard :macros); 
use Scalar::Util qw(looks_like_number);


###############################
#### Configuration Options ####
###############################

# Username the daemon should run as (default "fetchmail").
my $user = "fetchmail";
# Group the daemon should run as (default "nogroup").
my $group = "nogroup";
# Working directory. Set this to the Home Directory of the $user.
my $work_dir = "/var/lib/fetchmail";
# Logging method. Currently only 'syslog' is implemented.
my $log_method = 'syslog';
# Loglevel (0(emergency), 1(alert), 2(crit), 3(err), 4(warn), 5(notice), 6(info), 7(debug))
my $log_level = 7;
## Only modify the next options, if your kopano-webapp installation is in a non-standard directory.
# Path to the kopano-webapp installation (default: /usr/share/kopano-webapp).
my $base_path = "/usr/share/kopano-webapp";
# Name of the plugins directory (default: plugins)
my $plugin_directory = "plugins";

###############################
####     DO NOT MODIFY     ####
#### ANYTHING BELOW THIS ! ####
###############################


## Check if user wants to run the script as root. Dangerous!
if($user eq 'root' || $group eq 'root'){
	die('Do not run the daemon as root user, configure a different value for $user or $group');
}

my $pid_file = $work_dir . "/kopano_fetchmail_daemon.pid";

## Convert $user to uid.
my $uid = getpwnam($user) || die "Cannot identify user ($user).";
my $gid = getgrnam($group) || die "Cannot identify group ($group).";

## Checking if i'm $user and $group, else try to switch to that $user and $group. Exit if not possible.
if($< != $uid || $( != $gid)  {
	POSIX::setgid($gid) or die "Cannot change gid ($gid)";
	POSIX::setuid($uid) or die "Cannot change uid ($uid).";
	# Also set umask to 066. Daemon does this too, but if run in foreground it gets skipped.
	umask(066);
}

# Test if $work_dir is read/writable
if(-e $work_dir && -d $work_dir)
{
	if(!(-w $work_dir)) {
		die("Working directory ($work_dir) is not writable.");
	}
} else {
	die("Working directory ($work_dir) does not exists, or is not a directory.");
}


## Setting up the daemon object
my $daemon = Proc::Daemon->new(
    pid_file => $pid_file,
    work_dir => $work_dir,
    setuid => $uid,
    setgid => $gid,
    file_umask => 066
);


# Testing if the daemon is already running and get the pid (0 if it's not running).
my $pid = $daemon->Status($pid_file);

# Dont detach and instead run the programm in the foreground.
my $foreground = 0;

GetOptions(
 "foreground" => \$foreground,
 "start" => \&run,
 "status" => \&status,
 "stop" => \&stop,
 "help" => sub { pod2usage(1) }
) or pod2usage(2);


# Log function, writes all output to syslog.
sub l{
	openlog("kopano_fetchmail.pl","pid","LOG_DAEMON");
	if(looks_like_number($log_level)) {
		setlogmask( LOG_UPTO($log_level) );
	} else {
		setlogmask( LOG_UPTO(LOG_DEBUG));
		syslog(LOG_WARNING, "Wrong value for $log_level.");
	}
    my ($log_level, $msg) = @_;
    syslog($log_level,$msg);
}

sub stop {
        if ($pid) {
            if ($daemon->Kill_Daemon($pid_file,15)) {
            	unlink $pid_file;
                l(LOG_NOTICE,"Stopping kopano_fetchmail.pl daemon (pid $pid)");
                exit;
            } else {
                say "Could not find $pid.  Are you sure it's running?";
            }
         } else {
                say "Daemon not running, nothing to stop.";
         }
}

sub status {
    if ($pid) {
        say "Daemon is running with pid $pid.";
    } else {
        say "Daemon is not running.";
    }
}

sub run {
    if (!$pid) {
        if(!$foreground) {
        	$daemon->Init;
        }
        $pid = $daemon->Status($pid_file);
		l(LOG_NOTICE,"Starting kopano_fetchmail.pl daemon (pid $pid)");
        while (1) {
        	my $cmd = "$base_path/$plugin_directory/fetchmail/php/daemon/daemon.fetchmail.api.php --base-path $base_path --plugin-dir $plugin_directory --list";
        	open (my $fetchmail_api, "-|","/usr/bin/php $cmd") or die("Cannot execute $cmd");
        	while (my $line = <$fetchmail_api>) {
        		chomp $line;
        		my %account = split(/[|,]/,$line);
        		# TODO/Idea: Fork the actuall polling into a subprocess? 
        		pollAccount(\%account);
			}
			close $fetchmail_api;
            sleep 10;
        }
    } else {
        print "Already Running with pid $pid\n";
    }
}


# Polls a given account.
sub pollAccount {
	my %account = %{$_[0]};
	
	# Dont use the fetchmail cmdline parameters for safety reasons (should not show up in process table).
	# Instead write a temporary fetchmail config file. 
	my $conf = $work_dir . "/kopano_fetchmail_id_$account{'entryid'}.config";
	open(my $config,">",$conf) or die("Cannot write $conf");
	# Add the standard header stuff. (TODO make postmaster configurable?).
	print $config "set postmaster \"postmaster\"\n";
	print $config "set no bouncemail\n";
	print $config "set no spambounce\n";
	print $config "set no softbounce\n";
	print $config "set idfile kopano_fetchmail_id_$account{'entryid'}.fetchids\n";
	# Build the actuall fetchmail config line.
	my $line = "poll $account{'src_server'} service $account{'src_port'} protocol $account{'src_protocol'} ";
	if($account{'src_protocol'} eq 'POP3'){
		$line .= " uidl ";
	}
	$line .= " timeout 300 user '$account{'src_user'}' password '";
	$line .= decode_base64($account{'src_password'});
	$line .= "' is '$account{'kopano_mail'}' ";
	if($account{'ssl'}) {
		$line .= " ssl ";
	}
	$line .= lc($account{'src_polling_type'});
	print $config $line."\n";
	close $config;
	
	# Start the fetchmail process.
	my $fetchmail_cmd = "fetchmail --fetchmailrc $conf --pidfile $work_dir/kopano_fetchmail_id_$account{'entryid'}.pid $account{'src_server'}";
	my @pipe_output;
	open(my $pipe,"-|","$fetchmail_cmd 2>&1") or die ("Cannot execute $fetchmail_cmd");
	while (my $line = <$pipe>) {
		chomp $line;
		push @pipe_output, $line;
	}
	close $pipe;
	my $return_code = $?/256;
		
	# Delete the temporary config file.
	unlink $conf;
	
	# Write the status_code and log_message into the DB.
	my $log_message = encode_base64(join("\n",@pipe_output),"");
	my $api_cmd = "$base_path/$plugin_directory/fetchmail/php/daemon/daemon.fetchmail.api.php --base-path $base_path --plugin-dir $plugin_directory --update $account{'entryid'} $return_code $log_message";
    open (my $api_pipe, "-|","/usr/bin/php $api_cmd") or die("Cannot execute $api_cmd");
    while (my $line = <$api_pipe>) {
    	chomp $line;
        if($line == 0) {
        	l(LOG_DEBUG,"[$account{'kopano_mail'}] Polled [$account{'src_server'}]");
        }
	}
	close $api_pipe;
            
}


=head1 NAME

kopano_fetchmail.pl -  Script to poll mailserver via fetchmail. Used with the fetchmail plugin for kopano.

=head1 SYNOPSIS

$ kopano_fetchmail.pl [options] <command> 

 Command:
 	--start			Starts the daemon.
 	--stop			Stops the daemon.
 	--status 		Checks if the daemon is running.
 	
 Options:
 	--foreground		[Optional] Runs the script in the foreground, instead of as a daemon. 

=head1 OPTIONS

=over 4

=item --start

Starts the daemon as the configured user.

=item --stop

Stops the daemon if it's running.

=item --status

Checks if the daemon is running and prints the PID.

=item --help

This help information.

=item --foreground

Runs the script in the foreground, instead of as a daemon.
Use with --start command.

=back