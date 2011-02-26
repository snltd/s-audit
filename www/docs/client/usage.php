<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "usage";
$pg = new docPage("s-audit.sh usage");

?>

<h1>Running s-audit.sh</h1>

<p><tt>s-audit.sh</tt> is invoked in the following ways</p>

<pre class="cmd">
# s-audit.sh [-f dir] [-z all|zone] [-qpP] [-D secs] 
  [-R user@host:dir ] [-o test,test,...] [-e file]
  app|fs|hardware|hosted|os|plist|net|security|tool|machine|all
</pre>

<pre class="cmd">
$ s-audit.sh -l
</pre>

<pre class="cmd">
$ s-audit.sh -V
</pre>


<h2>Options</h2>

<dl>
	<dt>-D seconds</dt>
	<dd>Makes <tt>s-audit.sh</tt> pause for the given number of seconds
	before starting.  This is useful if you have a service which
	automatically runs <tt>s-audit.sh</tt> at boot time, as it gives the
	machine a chance to start all its services and &quot;settle down&quot;,
	thereby producing a more accurate audit.</dd>

	<dt>-f [directory]</dt>
	<dd>Write files to an (optionally) supplied local directory. If no
	directory is given, the default is <tt>/var/tmp/s-audit</tt>.  Files are
	named in the format <tt>audit.hostname.<em>class</em></tt> where
	<em>class</em> is the audit class. Without this option output goes to
	standard out.</dd>

	<dt>-l</dt>
	<dd>This is a standalone option which just lists the tests run in each
	class of audit, in both global and local zones. No other action is
	taken.</dd>
	
	<dt>-L facility</dt>
	<dd>Lets the user supply a syslog facility to which <tt>s-audit.sh</tt>
	will write messages.</dd>

	<dd>The argument to this flag must be given in lower-case.</dd>

	<dt>-M</dt>
	<dd>Normally, in a hardware audit, <tt>s-audit.sh</tt> will only report
	MAC addresses for plumbed interfaces. When <tt>-M</tt> is specified, the
	script will plumb every unused inerface in turn, taking the MAC address,
	then unplumbing. This is the only test which modifies the state of the
	machine being audited, so should be used with caution.</dd>

	<dt>-o test,test,...,test</dt>
	<dd>Allows the user to provide a comma-separated list of tests to be
	omitted. For a full list of test names, use the <tt>-l</tt>
	flag.</dd>

	<dt>-p</dt>
	<dd>Makes the output machine-parseable. By default output is
	&quot;prettyfied&quot; for human eyes, but the machine-parseable version
	is generally very easy to understand. This option produces output which
	can be understood by the <a href="../interface">PHP interface</a>.</dd>

	<dt>-P</dt>
	<dd>With this option, rather than just reporting the versions of
	software in the <a href="class_tool.php">tool</a> and <a
	href="class_app.php">application</a> audit classes, <tt>s-audit.sh</tt>
	will also display the paths to the binaries. When <tt>-f</tt> is used,
	this is option is always turned on.</dd>

  	<dt>-q</dt>
	<dd>Suppresses any writing to standard out or standard err. This is
	intended to be used with the <tt>-f</tt> option when <tt>s-audit.sh</tt>
	is run through cron or SMF.</dd>

	<dt>-R user@host:directory</dt>
	<dd>This option is used in conjunction with <tt>-f</tt> to copy files to
	a remote destination, where they will presumably be processed by the <a
	href="../interface">PHP interface</a>. This requires an <tt>scp</tt>
	binary, and you must suppply a remote username, remote hostname, and the
	directory on that host. You will also most likely have to perform
	suitable SSH key exchanges to fully automate the process.</dd>

	<dt>-e file</dt>
	<dd>Allows the user to supply the location of an <a
	href="extras_file.php">extras file</a>, which can be used to enhance an
	audit with information that <tt>s-audit.sh</tt> could not normally
	deduce.</dd>

	<dt>-V</dt>
	<dd>Prints the version of the program and exits.</dd>

	<dt>-z zone</dt>
	<dd>Tells <tt>s-audit.sh</tt> to audit the named zone. A single zone
	name may be supplied, or <tt>all</tt> may be used as a shorthand for all
	running zones. The output from <tt>-z all</tt> can be confusing, and it
	is primarily intended to be run with the <tt>-f</tt> flag. Can only be
	used in the global zone.</dd>

</dl>

<h2>Classes</h2>

<p>For options other than <tt>-l</tt> and <tt>-V</tt>, an audit class must
be provided. The classes are:</p>

<dl>
	<dt><a href="class_application.php">app, application</a></dt>
	<dd>Looks for application software. Normally that means system software,
	such as VxVm, or programs which run as a userland daemon, like
	Apache.</dd>

	<dt><a href="class_fs.php">fs, filesystem</a></dt>
	<dd>Looks at the storage on the box, above the physical level.
	Filesystems, ZFS pools, NFS mounts and so-on.</dd>
	
	<dt><a href="class_platform.php">platform</a></dt>
	<dd>Looks at the configutation of the physical or virtual machine in
	which the host O/S is running.</dd>
	
	<dt><a href="class_hosted.php">hosted</a></dt>
	<dd>Examines a number of &quot;hosted services&quot; like websites and
	databases. </dd>
	
	<dt><a href="class_net.php">net</a></dt>
	<dd>Looks at the machine's network configuration.</dd>
	
	<dt><a href="class_os.php">os</a></dt>
	<dd>Looks at Solaris itself.</dd>
	
	<dt><a href="class_plist.php">patch, patches, plist</a></dt>
	<dd>Lists patches and packages.</dd>
	
	<dt><a href="class_security.php">security</a></dt>
	<dd>Looks for O/S configurations (sometimes distantly) related to
	security. Things like non-standard users, SSH authorized keys, open
	ports, and cron jobs.</dd>
	
	<dt><a href="class_tools.php">tool, tools</a></dt>
	<dd>Looks for userland tools and scripting languages. </dd>
	
	<dt>all</dt>
	<dd>Performs all of the above audits in the current zone, or if
	<tt>-z</tt> is used, in the given zone.</dd>
	
	<dt>machine</dt>
	<dd>This can only be used from the global zone. It runs all audit
	classes in all running zones.</dd>

</dl>

<h1>Output and Logging</h1>

<p>If the <tt>-q</tt> option is given, then nothing will be written to
standard out or standard err, even error messages from the programs being
audited.</p>

<p>If you intend to generate regular audits by running <tt>s-audit.sh</tt>
through a scheduler, you will generally use <tt>-q</tt>, but you may also
wish to supply a syslog facility with the <tt>-L</tt> option. If syslog is
being used, <tt>s-audit.sh</tt> will record the start and finish of every
audit it performs, along with any error messages. Start and finish messages
are written at <tt>info</tt> level, error messages at <tt>err</tt>.</p>

<p>To capture the <tt>s-audit.sh</tt> output, put a line like this in your
<tt>/etc/syslog.conf</tt>. Remember that <tt>syslog.conf</tt> fields must be
tab-separated, you'll have to touch the destination file, and restart the
syslog daemon. This example assumes you run <tt>s-audit.sh</tt> with
<tt>-L local7</tt> and wish to record all starts, stops, and errors.</p>

<pre>
local7.info                 /var/log/s-audit.log
</pre>

<p>Alternatively, to only record error messages:</p>

<pre>
local7.err                 /var/log/s-audit.log
</pre>

<?php

$pg->close_page();

?>

