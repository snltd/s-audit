<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "installation guide";
$pg = new docPage($menu_entry);

?>

<h1>Installing s-audit</h1>

<h2><tt>s-audit-x.y.tar.gz</tt></h2>

<p>Once you have downloaded the s-audit archive, unpack it:</p>

<pre class="cmd">$ gtar -zxf s-audit-x.y.tar.gz</pre>

<p>It will create a directory called <tt>s-audit-x.y</tt>. <tt>cd</tt> into
that directory.</p>

<p>You will see the following directory structure.</p>

<dl>

	<dt><tt>client/</tt></dt>
	<dd>This contains the s-audit client, an executable shell script called
	<tt>s-audit.sh</tt>.</dd>

	<dt><tt>extras/</tt></dt>
	<dd>This directory holds scripts which can be used to augment the data
	presented by the web interface.</dd>

	<dt><tt>www/</tt></dt>
	<dd>This directory contains s-audit's web interface.</dd>
</dl>

<h2>Installing the Client</h2>

<p>The client is a single shell script, <tt>s-audit.sh</tt>, which you run
on a machine you wish to audit. Just copy it to a machine and run it,
preferably as root. It can produce human-readable output, or files ready to
be parsed by s-audit's web interface.</p>

<p>You may wish to audit machines on a regular basis.
<a href="client_install.php">The client installation page</a> explains how to
do this.</p>

<h3>Prerequisites and Dependencies</h3>

<p>The client will run on any Solaris machine from version 2.6 to various
OpenSolaris/Solaris 11 based distributions. It has no dependencies outside
the core Solaris install.</p>


<h2>Installing the Interface</h2>

<h3>Prerequisites and Dependencies</h3>

<p>The interface needs a <a href="http://php.net">PHP</a> enabled web
server. PHP version 5.2 or later is required.  Following the design
methodology behind the s-audit client, the interface is written simply, and
requires no non-core PHP functionality.</p>

<p>Theoretically the s-audit interface can run on any web server with a
suitable installation of PHP, but it is only officially tested with <a
href="http://httpd.apache.org">Apache</a> 2.2.</p>

<h3>Installing</h3>

<p>Create an Apache instance or virtual host and copy the contents of the
<tt>www/</tt> directory to the document root. File ownerships and
permissions do not matter so long as your web server user can read
everything.</p>

<h3>Configuring</h3>

<p>File paths in the following paragraphs are relative to your Apache server
document root.</p>

<h4><tt>site_config.php</tt></h4>

<p>In the <tt>_conf</tt> directory, rename <tt>site_config.php.def</tt> to
<tt>site_config.php</tt> and open it up in your favourite editor.</p>

<pre class="cmd">
$ cd _conf
$ mv site_config.php.def site_config.php
$ vim site_config.php
</pre>

<p>You may wish to change any or all of the following definitions.</p>

<dl>

	<dt><tt>SITE_NAME</tt></dt>
	<dd>This string is displayed in the title and footer bar of audit pages.
	It can say anything you like, but you will probably want to set it to
	the name of your organization.</dd>

	<dt><tt>STRIP_DOMAIN</tt></dt>
	<dd>When viewing  <a href="../03_interface/class_fs.php">filesystem</a>
	or <a href="../03_interface/class_fs.php">IP listing</a> audits, the
	value of this constant will be stripped off fully qualified hostnames.
	You don't have to set it, but it can make things more readable.</dd>

	<dt><tt>ALOM_SFX</tt></dt>
	<dd>If you have a naming convention whereby you name your ALOMs/system
	controllers by adding a suffix to the hostname of the machine they
	control, put that suffix in here. It is used when the interface tries to
	&quot;guess&quot; ALOM IP addresses. (Please refer to the
	<a href="../03_interface/class_platform.php">platform audit page</a> for
	more details. This constant does not have to be defined.</dd>

	<dt><tt>SHOW_SERVER_INFO</tt></dt>
	<dd>If this constant is defined, then the footer bar on audit pages will
	display the version of PHP being used by the interface, and the machine
	on which it is running.</dd>

	<dt><tt>OMIT_PORT_THRESHOLD</tt></dt>
	<dd>The <a href="../03_interface/class_net.php">network audit page</a> lists
	all open ports on a machine. If this constant is defined, then ports
	higher than the given number will not be displayed. It is useful for
	screening out transient ports used for NFS and so-on.</dd>

	<dt><tt>OMIT_STANDARD_USERS</tt></dt>
	<dd>If this is defined, then users which are installed by default will
	not be shown in the &quot;users&quot; column of the <a
	href="../03_interface/security.php">security audit page</a></dd>

	<dt><tt>OMIT_STANDARD_CRON</tt></dt>
	<dd>If this is defined, then cron jobs which are installed by default
	will not be shown in the &quot;users&quot; column of the <a
	href="../03_interface/security.php">security audit page</a></dd>

	<dt><tt>OMIT_STANDARD_ATTRS</tt></dt>
	<dd>If this is defined, then user roles and profiles which are installed
	by default will not be shown in the &quot;users&quot; column of the <a
	href="../03_interface/security.php">security audit page</a></dd>

	<dt><tt>SS_HOST_COLS</tt></dt>
	<dd>The <a href="../03_interface/single_server.php">single server audit
	page</a> shows a list of servers known to the auditor. This constant
	sets the number of columns in that display. It defaults to six columns,
	and it's unlikely you'd want to change it.</dd>

	<dt><tt>LOWEST_T</tt></dt>
	<dd>Every audit is timestamped, and the interface highlights
	&quot;fresh&quot; or &quot;stale&quot; audits by putting their
	timestamps on a green or red field. If the interface sees an audit
	timestamped in the future, or before the timestamp defined by this
	constant, it puts it on an orange field. This helps you spot machines
	with wildly inaccurate clocks. This value defaults to 01/01/2011, before
	which s-audit was not publicly available, so any audit file appearing to
	originate from before then must be from a machine with a faulty
	clock.</dd>

</dl>

<h4><tt>subnet_cols.php</tt></h4>

<p>If you use colour-coding on your network cables (i.e. different colours
for different subnets), s-audit's interface can tie in with that and make <a
href="../03_interface/class_net.php">network audits</a> clearer.</p>

<p>Even if you don't use colour-coded cables, it's recommend that you
install the default <tt>subnet_cols.php</tt>.</p>

<p>As with the config file, copy the default file and edit it.</p>

<pre class="cmd">
$ cd _conf
$ mv subnet_cols.php.def subnet_cols.php
$ vim subnet_cols.php
</pre>

<p>The file should be self-explanatory. It simply pairs subnet addresses
with HTML hex colours. Some default values are provided.</p>

<h4>Creating Audit Groups</h4>

<p>You can separate your machines into distinct groups. Perhaps one for
production, one for development and one for UAT. The interface will display
the servers in one group at a time.</p>

<p>Each group has its own directory in <tt>/var/snltd/s-audit</tt> (see the
<a href="interface_directories.php">directories</a> page for more
information), and you can create and manage those directories by hand, or
with the <a href="../04_extras/s-audit_group.php"><tt>s-audit_groups.sh</tt>
script</a>.</p>

<p>To create a group, you only need to decide on a name. If you are having
your hosts write audit files to the interface machine automatically, you
will have to make the group directory writable by the user the remote hosts
will connect as. You may also wish to add a description of that group.</p>

<p>As an example, the following command will create a group called
&quot;production&quot;. It will be owned by the <tt>audit</tt> user, and
have a brief description.</p>

<pre class="cmd">
# s-audit_group.sh create -d "Production Servers" -u audit production
</pre>

<p>Now all you need to do is copy machine-parseable audit files to
<tt>/var/snltd/s-audit/production/hosts</tt> and point a browser at your
webserver.</p>

<?php

$pg->close_page();

?>

