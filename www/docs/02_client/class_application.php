<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "application audits";
$pg = new docPage($menu_entry);

?>

<h1>Application Audits</h1>

<p>An application audit is performed by running</p>

<pre class="cmd">
# s-audit.sh app [-p]
</pre>

<p>The line between &quot;<a href="class_tool.php">tools</a>&quot; and
&quot;applications&quot; is blurred, but as a rough guide, things which run
as a daemon, normally as services, are defined as <em>applications</em>,
whilst programs are invoked on the command line, generally by users, are
regarded as <em>tools</em>.</p>

<p>If an application is installed in multiple locations, all instances will
be reported.</p>

<p><tt>s-audit.sh</tt> will try to find out whether or not each application
it finds is currently running, and will report accordingly. If it finds
multiple instances of an application, at least one of which is running,
<tt>s-audit.sh</tt> is not currently able to tell which instances are
running and which are not.  This may change in a future release.  </p>

<h2>Options</h2>

<p>The following option is supported.</p>

<dl>
	<dt>-p</dt>
	<dd>Gives the full paths of any relevant binaries found</dd>
</dl>


<h2>Checks</h2>

<p>These checks are performed in an application audit.</p>

<dl>
	<dt>apache</dt>
	<dd>This reports the version of <a
	href="http://httpd.apache.org">Apache</a>, and says whether or not the
	HTTP daemon is running. It then runs the following tests, none of which
	may be run independently.</dd>

	<dd><dl>

		<dt>apache_mods</dt>
		<dd>Lists all apache DSOs, and, if multiple versions of Apache have
		been found, the version of Apache which owns them. Apache 2.2
		reports the proper names of the modules (e.g.
		&quot;rewrite_module&quot; but older versions print the filename of
		the module (e.g.  &quot;mod_rewrite&quot; For Apache versions prior
		to 2.2, <tt>pldd(1)</tt> is run on the <tt>httpd</tt> process to get
		the module list. If Apache is not running, if <tt>pldd</tt> is not
		available, or if the user does not have the required privileges for
		that operation, <tt>s-audit.sh</tt> parses the Apache configuration
		file. These three methods are decreasingly accurate.</dd>

		<dt>php_mod</dt>
		<dd>This test gets the version of any <a
		href="http://www.php.net">PHP</a> Apache modules found, and says to
		which version of Apache each belongs.</dd>

	</dl></dd>

	<dd>Apache 1.3, 2.0 and 2.2 have been fully tested.</dd>

	<dt>nginx</dt>
	<dd>Currently this test only gets the version of <a
	href="http://nginx.net">nginx</a>, and reports whether or not it is
	running. This support will be extended to match that provided for
	Apache. Only tested on 0.7.x.</dd>

	<dt>coldfusion</dt>
	<dd>Reports the version of any <a
	href="http://www.adobe.com/products/coldfusion/">Coldfusion</a> server
	and whether it is running.</dd>

	<dt>tomcat</dt>
	<dd>If a JVM is available, this test will use it to get the version of
	any installed <a href="http://tomcat.apache.org/">Apache Tomcat</a>, and
	report on the running status. Only Tomcat 5 and later accurately
	reports its version. All version 3 and 4 instances will be reported as
	3.x and 4.x respectively. If it has root privileges, <tt>s-audit.sh</tt>
	can work out definitively whether a Tomcat instance is running. If it is
	not root and it sees any java process, it will report Tomcat is
	&quot;possibly running&quot; Tested with Tomcat versions 3 - 6
	inclusive.</dd>

	<dt>iplanet_web</dt>
	<dd>Gets the version of <a
	href="http://www.sun.com/software/products/web_srvr/index.xml">Sun Web
	Server</a>, (also known as SunONE Web Server, iPlanet, Netscape
	Enterprise web server), then reports whether or not the admin server is
	running, along with the number of HTTP daemons.  Known to work for
	versions 3, 6 and 7.</dd>

	<dd>iPlanet is usually installed in its own directory. The
	<tt>IPL_DIRS</tt> variable lists likely ones, but it may be necessary to
	add your own directories to that list.</dd>

	<dt>mysql_s</dt>
	<dd>Gets the version of <a href="http://www.mysql.com">MySQL</a> by
	looking for <tt>mysqld</tt>.  Also reports if <tt>mysqld</tt> is running
	or not.</dd>

	<dt>ora_s</dt>
	<dd>Get the version of the <a
	href="http://www.oracle.com/us/products/database/index.html">Oracle
	database</a>. This makes the assumption that the oratab is installed in
	<tt>/var/opt/oracle</tt>.</dd>

	<dt>svnserve</dt>
	<dd>First this looks for <a
	href="http://subversion.tigris.org/">Subversion</a>'s <tt>svnserve</tt>
	process, gets the version, and looks if it is running. Then it examines
	any Apache installations, looking for the SVN modules.</dd>

	<dt>sendmail</dt>
	<dd>Gets the version of sendmail. This test can take a couple of seconds
	to run on machines without reverse DNS entries.</dd>

	<dt>exim</dt>
	<dd>Gets the version of the <a href="http://www.exim.org">exim</a> MTA,
	and reports whether it is running as a daemon. Tested with 3.x and
	4.x.</dd>

	<dt>cronolog</dt>
	<dd>Gets the version of <a href="http://cronolog.org/">cronolog</a>, and
	reports if it is running as a daemon.</dd>

	<dt>ldm</dt>
	<dd>Get the version of the <a
	href="http://www.sun.com/servers/coolthreads/ldoms/get.jsp">Logical
	Domain software</a>, (also known as Oracle VM Server for SPARC) on a
	box. Omitted in local zones.</dd>

	<dt>mailman</dt>
	<dd>Gets the <a
	href="http://www.gnu.org/software/mailman/index.html">mailman</a>
	version out of Mailman's <tt>version</tt> script and, if
	<tt>qrunner</tt> appears in the process table, reports mailman as
	running.</dd>

	<dt>nb_s</dt>
	<dd>Gets the version of a Veritas/Symantec <a
	href="http://www.symantec.com/business/netbackup">NetBackup</a> server
	by querying the <tt>version</tt> file. If <tt>bprd</tt> is running,
	assumes the server is up. Only tested with version 6.</dd>

	<dt>nb_c</dt>
	<dd>Gets the version of Veritas/Symantec NetBackup client software. Only
	tested with version 6. Not run in a local zone.</dd>

	<dt>splunk</dt>
	<dd>Get the version of <a href="http://www.splunk.com">Splunk</a> and
	report if it is running.</dd>

	<dt>sshd</dt>
	<dd>Prints the version and variety of an SSH server. Knows about <a
	href="http://www.openssh.com/portable.html">OpenSSH</a> and SunSSH.
	Reports if the daemon is running.</dd>

	<dt>named</dt>
	<dd>Gets the version of <a
	href="http://www.isc.org/software/bind">BIND</a> and reports if it is
	running. Known to work with BIND 8 and 9.</dd>

	<dt>ssp</dt>
	<dd>Looks for E10k SSP software. Gets the version by querying the
	package info, and reports as running if the <tt>scotty</tt> binary is in
	the process table.</dd>

	<dt>symon</dt>
	<dd>Get the version of Sun's <a
	href="http://www.sun.com/servers/symon.html">SyMon</a>.</dd>

	<dt>samba</dt>
	<dd>Simply get the version and the running state of <a
	href="http://samba.org/">Samba</a>. Shares are audited in the <a
	href="class_fs.php">filesystem audit class</a>.</dd>

	<dt>vcs</dt>
	<dd>Get the version of <a
	href="http://www.symantec.com/business/cluster-server">Veritas Cluster
	Server</a>. If <tt>hashadow</tt> is running, assume the whole of VCS is
	running. VCS functionality will be extended in future versions.</dd>

	<dt>vxfs</dt>
	<dd>Get the version of <a
	href="http://www.symantec.com/business/storage-foundation">Veritas File
	System</a> (now part of Veritas Storage Foundation). This is done by
	querying the <tt>modinfo</tt> output of the loaded kernel module. So, if
	the module is not loaded, this will not be reported. Omitted in local
	zones, and requires root privileges.</dd>

	<dt>vxvm</dt>
	<dd>Get the version of <a
	href="http://www.symantec.com/business/storage-foundation">Veritas
	Volume Manager</a> (now part of Veritas Storage Foundation). As with
	VxFS, this is done by querying the loaded kernel module. Omitted in
	local zones, and requires root privileges.</dd>

	<dt>SMC</dt>
	<dd>Reports the version of Sun Web Console, Sun Java Web Console, or
	SMC.</dd>

	<dt>x</dt>
	<dd>If X server software is found on the box, this test reports on the
	variety (XSun or <a href="http://www.x.org/wiki/">Xorg</a>) and, for
	Xorg, the version. (XSun does not report a version.) Also states if the
	X server is running.</dd>

</dl>


<?php

$pg->close_page();

?>

