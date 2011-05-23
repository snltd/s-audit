<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "history";
$pg = new docPage($menu_entry);

?>

<h1><tt>s-audit.sh</tt> history</h1>

<p>A potted history of the way s-audit has evolved.</p>

<p>The v1.x and 2.x information is culled from the old headers of the
previous scripts.</p>

<dl>

	<dt>v0.0</dt>
	<dd>Beginning a large virtualization project in 2007, I put together a
	nasty little shell script which gathered rudimentary information on the
	environment I had to rationalize. I had virtually no documentation, and
	needed something to explore the servers for me. I remembered being part
	of a team working on a large patching project some years before, and
	spending weeks gathering and tabulating information about the estate.
	Here, I decided to send a script in to do the dirty work.</dd>

	<dt>v1.0 (20/08/08)</dt>
	<dd>Completely rewritten as a proper program which looked at some
	aspects of the hardware and O/S, NIC configuration, and worked out
	details of Apache virtual hosting.</dd>

	<dt>v1.1 (09/09/08)</dt>
	<dd>Changed Apache site finder so it can handle line breaks. It's also a
	heck of a lot more readable, though slightly slower.</dd>

	<dt>v1.2 (10/09/08)</dt>
	<dd>Fixed Apache site finder. Now uses a temp file, but is much more
	robust.</dd>

	<dt>v1.3 (11/09/08)</dt>
	<dd>Now no longer cares if Apache is running or not. Looks for sites
	anyway.</dd>

	<dt>v1.4 (03/10/08)</dt>
	<dd>Uses <tt>showrev</tt> instead of <tt>patchadd</tt>.</dd>

	<dt>v1.5 (18/10/08)</dt>
	<dd>Understands and handles zones which aren't running. It can't audit
	them of course, but it reports their existence. Also now properly
	supports the version of dladm in Solaris 11, which could well end up in
	Solaris 10. Couple of bugfixes. Tidied up usage message.</dd>

	<dt>v1.6 (19/10/08)</dt>
	<dd>Changed <tt>get_authorized_keys()</tt> so that we don't now try to
	automount non-existent home directories and hassle splunk with
	messages.</dd>

	<dt>v1.7 (04/11/08)</dt>
	<dd>Cleaned up some functions for the sake of efficiency, and changed
	zone audit so <tt>zlogin</tt> only runs if the script has been
	successfully copied, in attempt to fix random failures. Now audits Sun
	Explorer installations.</dd>

	<dt>v1.8 (05/11/08)</dt>
	<dd>Removed &quot;is script already running&quot; check, because
	<em>that</em> is what was causing the random failure. May reimplement in
	a different way in the future. Added <tt>umask</tt> command when writing
	files, so they hit the remote server with group write.</dd>

	<dt>v1.8a (24/11/08)</dt>
	<dd>Added <tt>/usr/local/bind/sbin</tt> to <tt>PATH</tt> so it can find
	our new home-grown BIND installation.</dd>

	<dt>v1.9</dt>
	<dd>Added uptime reporting, and ability to tag NIC information with the
	speed and duplex state of all cabled ports.</dd>

	<dt>v1.9a</dt>
	<dd>Bugfixes: be more selective with the use of <tt>dladm</tt> (in local
	zones and on Solaris &lt; 10); progress outputting; cron detection and
	output silencing.</dd>

	<dt>v1.10 (10/01/09)</dt>
	<dd>Added <tt>-V</tt> option to report version of script. Bugfixes for
	noisy dladm failure on Solaris 10. Audits its own version.  Works
	properly on Solaris 9 again.</dd>

	<dt>v1.11</dt>
	<dd>Sun have changed <tt>dladm</tt> with the introduction of Crossbow.
	Fixes to make that work on x86. Also found network reporting didn't work
	properly on any SPARC Nevada releases, so fixed that too. Moved
	<tt>DLADM</tt> virtual command chunk so it only gets run once for each
	zone, and then only when necessary.</dd>

	<dt>v1.12 (16/03/09)</dt>
	<dd>Added ability to audit crontabs. Ignores standard Solaris jobs</dd>

	<dt>v1.13 (16/06/09)</dt>
	<dd>Lockfile to stop multiple instances piling up. If the lock file
	exists when the script is run through cron, a message goes out through
	syslog to a log file in <tt>/var/log</tt>. Added <tt>timeout_job()</tt>
	function to stop <tt>sneep</tt> (and possibly other things in the
	future) from hanging forever.</dd>

	<dt>v1.14 (30/06/09)</dt>
	<dd>Changed working directories to match site standards.</dd>

	<dt>v1.15 (22/07/09)</dt>
	<dd>Got rid of the half-written &quot;cron&quot; audit. Fancied up
	output. Works with Solaris 8 and 9. Yeah, I know....</dd>

	<dt>v1.16 (22/09/09)</dt>
	<dd>Split &quot;software&quot; class into &quot;app&quot; and
	&quot;tool&quot; for big and small pieces of software. Now recognizes
	FC-attached storage, SCSI tape drives, Java, Tomcat, GCC, Sun CC,
	Python, Mailman. Now, by default, does not report on &quot;not
	available&quot; software. The old behaviour, which reports each check
	regardless of results, can be accessed with the <tt>-v</tt> flag. Works
	with Solaris 2.6. Better scanning of loaded Apache modules. A few minor
	optimizations. Warns if it can't create a lockfile. Finally sorted out
	correct printing of output separator bars. It was *so* simple.</dd>

	<dt>v1.17 (22/09/09)</dt>
	<dd>Recognizes and reports on <tt>sccli</tt>, Veritas (Cluster,
	Filesystem, and Volume Manager).</dd>

	<dt>v1.18 (12/09)</dt>
	<dd>Split &quot;platform&quot; audit into &quot;hardware&quot; which
	does physical hardware-y things, and &quot;os&quot; which looks at
	virtualization and stuff. Recognizes more virtualizations. Knows if it's
	running in a VirtualBox or, with a little less certainty, an LDOM.
	Rewrote <tt>get_disk()</tt> to be faster and smarter, and changed basic
	and rubbish <tt>get_dvd()</tt> for <tt>get_optical()</tt>, which counts
	the number of optical drives on the system. Made get_network aware of
	DHCP and LDOM virtual switches, and given it smarter VLAN and exclusive
	IP recognition.  Reports on hosted zones and LDOMs. Reports on root FS
	type. Improved job timeout. Rounds disk sizes to nearest whole unit.
	Prints multiple FC enclosures and tape drives as &quot;n x&quot; rather
	than listing each duplicate. Improved readability of Apache audit, made
	PHP module audit webserver-agnostic, added command-line PHP. Added alias
	audit names. Run <tt>prtpicl</tt> through <tt>timeout_jobs()</tt>.
	Application audit gets Samba version, hosted services audits shares.
	Audits Sun VTS and CAT.  Tells you what languages the C compilers
	support. Audits filesystems, printing mountpoint, device/dataset, ZFS
	version number, ZFS compression. Audits Zpools, displaying version info
	and size.</dd>

	<dt>v2.0 (19/01/10)</dt>
	<dd>Big internal changes. Far more efficient and clear, lots more
	commenting.</dd>

	<dt>v2.1 (09/02/10)</dt>
	<dd>Merged samba and NFS share audits to generalized &quot;exports&quot;
	Added very rudimentary iSCSI support. Merged NFS mount audit with
	general filesystem audit. Improved Apache website and MySQL DB auditing,
	as first step to understanding other engines. Fixed sendmail on broken
	DNS machines. Audits dtlogin daemons.</dd>

	<dt>v2.2 (28/02/10)</dt>
	<dd>Added <tt>-D</tt> option to delay startup by 'n' seconds. This lets
	the machine &quot;settle down&quot; before an automated audit is done.
	It is there for the benefit of the SMF method that audits a machine on
	every reboot. Count disks and politely fail to work out virtualization
	on old x86 systems. New way of checking is_global as the old way of
	assuming no zonename == global zone is broken by Solaris 8 and 9 branded
	zones. Now look to see if <tt>init</tt> has a pid of 1.
	<tt>get_virtualization()</tt> supports Solaris 8 and 9 branded zones.
	Gets MAC address for all, or only plumbed, interfaces. (See
	<tt>DO_PLUMB</tt> variable.) Smarter Tomcat detection. Split off NIC
	detection into <tt>mk_nic_devlist()</tt> so code can be shared with
	<tt>get_nic()</tt> (formerly <tt>get_network()</tt>) and
	<tt>get_mac()</tt>. Finds multiple versions of itself.
	<tt>my_pgrep()</tt> now optionally returns ALL processes matching
	pattern.  LDOM VDISKs are reported as exports.</dd>

	<dt>v2.3 (14/03/10)</dt>
	<dd>Audit swap space along with physical memory. <tt>get_optical()</tt>,
	<tt>get_disk()</tt> and <tt>get_storage()</tt> are still separate
	functions, but now all output data as &quot;storage&quot; Add
	<tt>-q</tt> option to force it to be quiet.  Look for PCI cards, with
	varying degrees of success.</dd>

	<dt>v2.4</dt>
	<dd>Recognizes if it's running in a VMWare machine. New default output
	directory. VirtualBox virtualization reports guest additions version, if
	installed. Properly recognize Apache SVN module.  Gracefully ignore
	faulted zpools. Recognize SBUS attached disks and CDs, and SBUS cards.
	Works properly with Solaris 2.6. Added <tt>-l</tt> option to list test
	in each class. Removed too-clever <tt>kstat</tt> uptime calculator.</dd>

	<dt>v2.5</dt>
	<dd>Added new &quot;all&quot; audit type. First steps towards a full
	understanding of Crossbow. Revision of <tt>get_nic()</tt>. Understand
	vnics. List printers.  Start to recognize non-Sun
	&quot;distributions&quot; with tweaks for Nexenta compatibility.</dd>
</dl>

<?php

$pg->close_page();

?>

