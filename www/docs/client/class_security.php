<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "security audits";
$pg = new docPage($menu_entry);

?>

<h1>Security audits</h1>

<p>Invoked by</p>

<pre class="cmd">
# s-audit.sh security
</pre>

<p>this audit type looks for things which may compromise the security of the
system. <strong><em>This in no way makes s-audit a security tool, and it should not
be treated as such</em></strong>, but it is a useful way to see if certain
potentially undesirable services, users, or circumstances, exist.</p>

<p>Some tests are not particularly security-related, but fit better here
than in any of the other classes.</p>

<h2>Checks</h2>

<dl>

	<dt>users</dt>
	<dd>Prints a list of all users. The username is paired with the UID.
	<a href="../interface">The PHP interface</a> removes standard users from
	this list.</dd>

	<dt>uid_0</dt>
	<dd>Lists all users other than <tt>root</tt> with a UID of 0.</dd>

	<dt>empty_passwd</dt>
	<dd>Lists all users with an empty field 2 in <tt>/etc/shadow</tt>. i.e.
	with a blank password. Requires root privileges.</dd>

	<dt>authorized_keys</dt>
	<dd>Looks at all local users, trying to find if any of them allow a
	remote user to run commands via an SSH key exchange. It does this by
	looking parsing each user's <tt>$HOME/.ssh/authorized_keys</tt> file.
	Requires root privileges.</dd>

	<dt>ssh_root</dt>
	<dd>Looks to see if SSH as root is enabled in the system's
	<tt>sshd_config</tt>. Currently this only works for a standard Sun SSH
	installation because it expects the SSH daemon configuration file to be
	<tt>/etc/ssh/sshd_config</tt>.</dd>

	<dt>user_attr</dt>
	<dd>Parses <tt>/etc/user_attr</tt> to find all profiles. Profiles common
	to all Solaris installations are filtered out by the <a
	href="../interface">PHP interface</a>.</dd>

	<dt>ports</dt>
	<dd>Gets a list of all open ports, and attempts to work out which
	program is using each one. Output is of the form
	port_number:service:process. The &quot;service&quot;name comes from
	<tt>/etc/services</tt>, and the &quot;process&quot; comes from examining
	processes with <tt>pfiles(1)</tt>. Information is therefore limited on
	old versions of Solaris. Requires root privileges. Because it uses
	<tt>netstat</tt>, it's possible this test can hang.</dd>

	<dt>root_shell</dt>
	<dd>Displays the root shell if it is anything other than
	<tt>/sbin/sh</tt>.</dd>

	<dt>snmp</dt>
	<dd>Says if <tt>snmpdx</tt> is running.</dd>

	<dt>dtlogin</dt>
	<dd>Looks for the presence of a number of desktop login daemons, and
	reports on any that are found. Currently knows about <a
	href="http://www.kde.org">KDE</a>'s <tt>kdm</tt>, GNOME's <tt>gdm</tt>,
	CDE's <tt>dtlogin</tt> and <tt>xdm</tt>.</dd>

	<dt>cron</dt>
	<dd>Displays a list of all cron jobs on the system. Jobs common to all
	Solaris installations are filtered out by the PHP interface. Requires
	root privileges.</dd>

</dl>

<p>All tests are run in both global and local zones.</p>

<?php

$pg->close_page();

?>

