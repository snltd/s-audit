<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "history";
$pg = new docPage($menu_entry);

?>

<h1>s-audit history</h1>

<p>A potted history of the way s-audit has evolved.</p>

<h2>Client</h2>

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
	syslog to the becta_scripts.log file. Added <tt>timeout_job()</tt>
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

<h2>Interface</h2>

<p>I never kept such detailed information about the way the interface
evolved, perhaps because I never imagined anyone else would have to maintain
it. The following is hacked together from subversion logs.</p>

<dl>

	<dt>1.0</dt>
	<dd>A very crude chunk of PHP which parsed the files created by the
	nameless v1.0 script, making a single grid of hardware and O/S
	information.</dd>

	<dt>r1 (13/10/08)</dt>
	<dd>Now understands zones in states other than "running"

	<dt>r20 (23/10/08)</dt>
	<dd>Amendments to allow two classes of server: live and obsolete. This
	is done by using two directories instead of the previous, all
	encompassing <tt>AUDIT_DIR</tt>. All <tt>auditor/</tt> files updated to
	access new constants New file to view "obsolete" servers.  Adapted to
	fit location of audit file.</dd>


	<dt>r25 (12/11/08)</dt>
	<dd>New "manage servers" page.</dd>

	<dt>r30 (24/11/08)</dt>
	<dd>Fixed so all pages produce valid HTML. Improved handling of patches
	and packages.  Now aware of Solaris 10 update 6.</dd>

	<dt>r65 (18/12/08)</dt>
	<dd>Handles NIC speed and duplex info from v1.9 of the client.  Added
	uptime support on platform page.  Strip domain name from NFS share
	hostnames.</dd>

	<dt>r172 (17/05/07)</dt>
	<dd>Added cron job display to security page.  Altered to understand the
	way dladm reports VLANs in update 7



Improved handling of VLANned interfaces

	<dt>r261 | robertf | 2009-07-15 14:38:16 +0100 (Wed, 15 Jul 2009) | 3 lines
------------------------------------------------------------------------


New IP listing page

	<dt>r286 | robertf | 2009-08-17 16:53:43 +0100 (Mon, 17 Aug 2009) | 3 lines
------------------------------------------------------------------------


For IP listing page

	<dt>r287 | robertf | 2009-08-17 16:54:05 +0100 (Mon, 17 Aug 2009) | 3 lines
------------------------------------------------------------------------


Explanation

	<dt>r288 | robertf | 2009-08-22 15:56:06 +0100 (Sat, 22 Aug 2009) | 3 lines
------------------------------------------------------------------------


	<dt>r289 | robertf | 2009-08-22 15:57:05 +0100 (Sat, 22 Aug 2009) | 1 line
------------------------------------------------------------------------


Moved to new location in SVN repository

	<dt>r295 | robertf | 2009-09-22 13:52:22 +0100 (Tue, 22 Sep 2009) | 3 lines
------------------------------------------------------------------------


Smarter at working out the most recent installed version of software

	<dt>r296 | robertf | 2009-09-22 14:01:09 +0100 (Tue, 22 Sep 2009) | 3 lines
------------------------------------------------------------------------


Adapted to use new, smaller, broken-up, class files

	<dt>r307 | robertf | 2009-09-24 15:45:52 +0100 (Thu, 24 Sep 2009) | 3 lines
------------------------------------------------------------------------


Split big audit files into smaller, page-specific ones.

	<dt>r308 | robertf | 2009-09-24 15:52:56 +0100 (Thu, 24 Sep 2009) | 3 lines
------------------------------------------------------------------------


Moved all functions out to dedicated class files. Got ip_listing working as I want it. (more or less)

	<dt>r310 | robertf | 2009-09-24 23:25:48 +0100 (Thu, 24 Sep 2009) | 3 lines
------------------------------------------------------------------------


Imported functions from auditor/ files

	<dt>r311 | robertf | 2009-09-24 23:26:07 +0100 (Thu, 24 Sep 2009) | 3 lines
------------------------------------------------------------------------


	<dt>Removed references to stephen and stanley

	<dt>r313 | robertf | 2009-09-25 00:28:19 +0100 (Fri, 25 Sep 2009) | 3 lines
------------------------------------------------------------------------


Handles missing nmap file comfortably. Narrower columns

	<dt>r314 | robertf | 2009-09-25 00:31:38 +0100 (Fri, 25 Sep 2009) | 3 lines
------------------------------------------------------------------------

Proper path to nmap audit file

	<dt>r315 | robertf | 2009-09-25 00:31:56 +0100 (Fri, 25 Sep 2009) | 2 lines
------------------------------------------------------------------------


Initial import

	<dt>r316 | robertf | 2009-09-25 00:35:50 +0100 (Fri, 25 Sep 2009) | 3 lines
------------------------------------------------------------------------


Added key

	<dt>r317 | robertf | 2009-09-27 17:44:25 +0100 (Sun, 27 Sep 2009) | 3 lines
------------------------------------------------------------------------


Added info to genererate IP listing key

	<dt>r318 | robertf | 2009-09-27 17:46:58 +0100 (Sun, 27 Sep 2009) | 3 lines
------------------------------------------------------------------------


Splip "software" page into "application" and "tools"

	<dt>r324 | robertf | 2009-10-09 16:35:38 +0100 (Fri, 09 Oct 2009) | 3 lines
------------------------------------------------------------------------


Split application audit into software and tools

	<dt>r325 | robertf | 2009-10-09 16:36:05 +0100 (Fri, 09 Oct 2009) | 3 lines
------------------------------------------------------------------------


for details
Big modifications for new IP listing page. Improved annotation. See files

	<dt>r334 | robertf | 2009-10-20 13:59:40 +0100 (Tue, 20 Oct 2009) | 4 lines
------------------------------------------------------------------------


Adapted to use new auditKey() class

	<dt>r335 | robertf | 2009-10-20 18:25:49 +0100 (Tue, 20 Oct 2009) | 3 lines
------------------------------------------------------------------------


New common keys for platform/obsolete and application/tool audits

	<dt>r336 | robertf | 2009-10-20 18:26:23 +0100 (Tue, 20 Oct 2009) | 3 lines
------------------------------------------------------------------------


	<dt>Removed redundant styles, commented everything, rationalized a little

	<dt>r337 | robertf | 2009-10-20 18:26:51 +0100 (Tue, 20 Oct 2009) | 3 lines
------------------------------------------------------------------------


Added ability for other classes to retrieve nic colours

	<dt>r338 | robertf | 2009-10-20 18:27:26 +0100 (Tue, 20 Oct 2009) | 3 lines
------------------------------------------------------------------------


Changed CSS references to match new stylesheet

	<dt>r339 | robertf | 2009-10-20 18:28:14 +0100 (Tue, 20 Oct 2009) | 3 lines
------------------------------------------------------------------------


class to simplify generation of keys
Added option to hide server count when displaying grids. Added auditKey

	<dt>r340 | robertf | 2009-10-20 18:29:04 +0100 (Tue, 20 Oct 2009) | 4 lines
------------------------------------------------------------------------


Improved titling of pages

	<dt>r341 | robertf | 2009-10-20 18:29:20 +0100 (Tue, 20 Oct 2009) | 3 lines
------------------------------------------------------------------------


Improved keys and titles

	<dt>r342 | robertf | 2009-10-20 18:49:43 +0100 (Tue, 20 Oct 2009) | 3 lines
------------------------------------------------------------------------

more annotation. Bold known server names

	<dt>r343 | robertf | 2009-10-20 18:50:00 +0100 (Tue, 20 Oct 2009) | 2 lines
------------------------------------------------------------------------

Handles any whitespace separation in reserved file

	<dt>r344 | robertf | 2009-10-20 19:01:31 +0100 (Tue, 20 Oct 2009) | 2 lines
------------------------------------------------------------------------


faster.)
get_latest() function, so it actually works. (And should also be quite a bit
Changed eregs to pregs. more comments, clearer var names. Rewrote

	<dt>r345 | robertf | 2009-10-21 10:15:51 +0100 (Wed, 21 Oct 2009) | 5 lines
------------------------------------------------------------------------


Chagned eregs to pregs. More annotation. Added safe_compare function

	<dt>r346 | robertf | 2009-10-21 14:50:33 +0100 (Wed, 21 Oct 2009) | 3 lines
------------------------------------------------------------------------


Expanded key

	<dt>r347 | robertf | 2009-10-21 17:06:36 +0100 (Wed, 21 Oct 2009) | 3 lines
------------------------------------------------------------------------


Added styles for server view page

	<dt>r348 | robertf | 2009-10-21 17:07:01 +0100 (Wed, 21 Oct 2009) | 3 lines
------------------------------------------------------------------------


functions file
added in compare_cpu function, moved data collection class from general

	<dt>r349 | robertf | 2009-10-21 17:08:06 +0100 (Wed, 21 Oct 2009) | 4 lines
------------------------------------------------------------------------


Moved data collection classes to dedicated class files

	<dt>r350 | robertf | 2009-10-21 17:08:39 +0100 (Wed, 21 Oct 2009) | 3 lines
------------------------------------------------------------------------


Moved in data collection class

	<dt>r351 | robertf | 2009-10-21 17:08:59 +0100 (Wed, 21 Oct 2009) | 3 lines
------------------------------------------------------------------------


Initial import

	<dt>r352 | robertf | 2009-10-21 18:48:58 +0100 (Wed, 21 Oct 2009) | 3 lines
------------------------------------------------------------------------


Moved data collection classes into class files

	<dt>r353 | robertf | 2009-10-21 18:49:19 +0100 (Wed, 21 Oct 2009) | 3 lines
------------------------------------------------------------------------


Initial import

	<dt>r354 | robertf | 2009-10-21 18:49:48 +0100 (Wed, 21 Oct 2009) | 3 lines
------------------------------------------------------------------------


Split "platform" into "hardware" and "O/S". Shortened title bar identifiers

	<dt>r365 | robertf | 2009-12-09 11:37:54 +0000 (Wed, 09 Dec 2009) | 3 lines
------------------------------------------------------------------------


Added table rows for LDMs

	<dt>r366 | robertf | 2009-12-09 12:29:45 +0000 (Wed, 09 Dec 2009) | 3 lines
------------------------------------------------------------------------


functions to handle zones and ldoms. Fixed broken compare function
Adapted to work with hardware/os instead of platform. Added special

	<dt>r367 | robertf | 2009-12-09 16:44:02 +0000 (Wed, 09 Dec 2009) | 4 lines
------------------------------------------------------------------------


Better annotation.

	<dt>r368 | robertf | 2009-12-09 16:50:35 +0000 (Wed, 09 Dec 2009) | 3 lines
------------------------------------------------------------------------


Bugfix LDMs, re-instate wrapping in normal cells.

	<dt>r369 | robertf | 2009-12-09 16:51:10 +0000 (Wed, 09 Dec 2009) | 3 lines
------------------------------------------------------------------------


Changed LDOM colour

	<dt>r371 | robertf | 2009-12-10 14:12:49 +0000 (Thu, 10 Dec 2009) | 3 lines
------------------------------------------------------------------------

Fixed to work with new hardware/os audits rather than platform. Better key.

	<dt>r372 | robertf | 2009-12-10 14:13:14 +0000 (Thu, 10 Dec 2009) | 2 lines
------------------------------------------------------------------------


Better key.

	<dt>r373 | robertf | 2009-12-10 14:13:34 +0000 (Thu, 10 Dec 2009) | 3 lines
------------------------------------------------------------------------


Source tidying.

	<dt>r374 | robertf | 2009-12-10 14:13:59 +0000 (Thu, 10 Dec 2009) | 3 lines
------------------------------------------------------------------------


Initigal release. Split off from old platform audit key.

	<dt>r375 | robertf | 2009-12-10 14:17:59 +0000 (Thu, 10 Dec 2009) | 3 lines
------------------------------------------------------------------------


Added global/local/ldm

	<dt>r376 | robertf | 2009-12-10 14:25:16 +0000 (Thu, 10 Dec 2009) | 3 lines
------------------------------------------------------------------------


Special wrapper for shared modules.

	<dt>r377 | robertf | 2009-12-10 14:39:14 +0000 (Thu, 10 Dec 2009) | 3 lines
------------------------------------------------------------------------


"other". Expanded global_key for LDMs.
Centred server count. Added support for Logical Domains, primary and

	<dt>r378 | robertf | 2009-12-10 14:40:48 +0000 (Thu, 10 Dec 2009) | 4 lines
------------------------------------------------------------------------


auditor -- it's WAY easier to understand as well.
show_hardware().  Totally rewrote show_NIC() to handle new output from
	<dt>references to "hardware". Added support for predefined machine names in
Initial release, split off from platform_classes.php Changed all "platform"

	<dt>r379 | robertf | 2009-12-10 14:43:17 +0000 (Thu, 10 Dec 2009) | 6 lines
------------------------------------------------------------------------


	<dt>Removed zone_type hidden field, as now obsolete.

	<dt>r380 | robertf | 2009-12-10 14:43:52 +0000 (Thu, 10 Dec 2009) | 3 lines
------------------------------------------------------------------------


	<dt>references to "os". No other changes.
Initial relase, split off from old platform class. Changed all "platform"

	<dt>r381 | robertf | 2009-12-10 14:44:35 +0000 (Thu, 10 Dec 2009) | 4 lines
------------------------------------------------------------------------


of "platform".
annotation. Changed eregs to pregs. Works with new "hardware" class instead
v.1.1. More robust - handles all kinds of missing data politely. Better

	<dt>r382 | robertf | 2009-12-10 14:45:46 +0000 (Thu, 10 Dec 2009) | 5 lines
------------------------------------------------------------------------


	<dt>Removed obsolete files

	<dt>r383 | robertf | 2009-12-10 15:48:43 +0000 (Thu, 10 Dec 2009) | 3 lines
------------------------------------------------------------------------


Bugfix. Wasn't closing table.

	<dt>r385 | robertf | 2009-12-11 12:30:57 +0000 (Fri, 11 Dec 2009) | 3 lines
------------------------------------------------------------------------


Use show_os_version from hardware class

	<dt>r389 | robertf | 2009-12-18 12:17:40 +0000 (Fri, 18 Dec 2009) | 3 lines
------------------------------------------------------------------------


	<dt>Removed hidden_fields. Don't need it any more.

	<dt>r390 | robertf | 2009-12-18 16:34:33 +0000 (Fri, 18 Dec 2009) | 3 lines
------------------------------------------------------------------------


Silent fail on missing file.

	<dt>r391 | robertf | 2009-12-18 16:35:01 +0000 (Fri, 18 Dec 2009) | 3 lines
------------------------------------------------------------------------


added get_parent_property()

	<dt>r392 | robertf | 2009-12-18 16:35:32 +0000 (Fri, 18 Dec 2009) | 3 lines
------------------------------------------------------------------------



added show_fc_enc(), show_tape_drive(), full knowledge of Solaris update dates

	<dt>r393 | robertf | 2009-12-18 16:36:24 +0000 (Fri, 18 Dec 2009) | 4 lines
------------------------------------------------------------------------


Added show_local_zone(), show_root_fs(), smarter uptime handling, highlights zones with different kernels to global

	<dt>r394 | robertf | 2009-12-18 16:37:35 +0000 (Fri, 18 Dec 2009) | 3 lines
------------------------------------------------------------------------


	<dt>Removed debugging code

	<dt>r396 | robertf | 2009-12-18 16:44:09 +0000 (Fri, 18 Dec 2009) | 3 lines
------------------------------------------------------------------------


Bold physical NICs

	<dt>r411 | robertf | 2009-12-30 23:29:52 +0000 (Wed, 30 Dec 2009) | 3 lines
------------------------------------------------------------------------


Changed for change in constant name

	<dt>r414 | robertf | 2010-01-01 16:05:09 +0000 (Fri, 01 Jan 2010) | 3 lines
------------------------------------------------------------------------


added key for coloured zone status

	<dt>r423 | robertf | 2010-01-12 12:42:19 +0000 (Tue, 12 Jan 2010) | 3 lines
------------------------------------------------------------------------


made domain name apply to DNS and NFS

	<dt>r424 | robertf | 2010-01-12 12:42:45 +0000 (Tue, 12 Jan 2010) | 3 lines
------------------------------------------------------------------------


zones
out-of-date versions. Show filesystem types. Prettily present LDOMs like
Colour code local zones on state. Show zpools with colour coding for

	<dt>r425 | robertf | 2010-01-12 12:43:52 +0000 (Tue, 12 Jan 2010) | 5 lines
------------------------------------------------------------------------


changed eregs to pregs

	<dt>r426 | robertf | 2010-01-12 12:44:09 +0000 (Tue, 12 Jan 2010) | 3 lines
------------------------------------------------------------------------


Changed eregs to pregs. Fold long lines shorter than before.

	<dt>r427 | robertf | 2010-01-12 12:44:53 +0000 (Tue, 12 Jan 2010) | 3 lines
------------------------------------------------------------------------


New FS audit page.

	<dt>r434 | robertf | 2010-01-28 12:22:15 +0000 (Thu, 28 Jan 2010) | 3 lines
------------------------------------------------------------------------


	<dt>Removed hardware key.

	<dt>r435 | robertf | 2010-01-28 12:27:52 +0000 (Thu, 28 Jan 2010) | 3 lines
------------------------------------------------------------------------


New FS class.

	<dt>r436 | robertf | 2010-01-28 12:33:33 +0000 (Thu, 28 Jan 2010) | 3 lines
------------------------------------------------------------------------


Fixed nfs mounts on hosted services.

	<dt>r437 | robertf | 2010-01-28 12:42:11 +0000 (Thu, 28 Jan 2010) | 3 lines
------------------------------------------------------------------------


Colour 192.168.1 pink

	<dt>r443 | robertf | 2010-02-01 00:17:58 +0000 (Mon, 01 Feb 2010) | 3 lines
------------------------------------------------------------------------


Better presentation of apps.

	<dt>r447 | robertf | 2010-02-04 11:17:50 +0000 (Thu, 04 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added support for new "virtualization" field in hardware audit.

	<dt>r451 | robertf | 2010-02-08 17:28:48 +0000 (Mon, 08 Feb 2010) | 3 lines
------------------------------------------------------------------------


Special Sun CC function. Reorganized source.

	<dt>r454 | robertf | 2010-02-08 23:42:29 +0000 (Mon, 08 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added classes for filesystem types

	<dt>r456 | robertf | 2010-02-10 01:19:32 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


new show_capacity

	<dt>r457 | robertf | 2010-02-10 01:19:49 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


Updated keys

	<dt>r458 | robertf | 2010-02-10 01:20:06 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


v.1.2 Use CSS classes rather than inline style

	<dt>r459 | robertf | 2010-02-10 15:25:58 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


sheet. Create new inline_cols:: class for inline styles.
Moved colours class to separate file so it can be used in dynamic style

	<dt>r460 | robertf | 2010-02-10 15:26:52 +0000 (Wed, 10 Feb 2010) | 4 lines
------------------------------------------------------------------------


New dynamic stylesheet generates <td> styles from colours::cols array.

	<dt>r461 | robertf | 2010-02-10 15:27:22 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


to work with dynamic style sheet
	<dt>Replaced get_parent_property() with more generic get_parent_prop(). Altered

	<dt>r462 | robertf | 2010-02-10 15:37:04 +0000 (Wed, 10 Feb 2010) | 4 lines
------------------------------------------------------------------------


Added get_zone_prop(), and altered get_parent_prop() to use it,

	<dt>r463 | robertf | 2010-02-10 17:38:50 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


Changed to fit new conf and key paths

	<dt>r464 | robertf | 2010-02-10 17:43:53 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


	<dt>Relocated.

	<dt>r465 | robertf | 2010-02-10 17:44:07 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


	<dt>Relocated

	<dt>r466 | robertf | 2010-02-10 17:44:15 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added multicellsmall, for listings where space is tight.

	<dt>r467 | robertf | 2010-02-10 17:44:53 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


	<dt>Relocated.

	<dt>r468 | robertf | 2010-02-10 17:45:42 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


	<dt>Relocated.

	<dt>r469 | robertf | 2010-02-10 17:46:03 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added multiCellSmall class

	<dt>r470 | robertf | 2010-02-10 17:46:48 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


Use new multiCellSmall class to put separators in NIC column.

	<dt>r471 | robertf | 2010-02-10 17:47:39 +0000 (Wed, 10 Feb 2010) | 3 lines
------------------------------------------------------------------------


to use it.
versions, and whose version strings are manipulated. Adapted show_sun_cc()
Created new show_parsed_sw() function to display software with multiple

	<dt>r472 | robertf | 2010-02-10 17:49:58 +0000 (Wed, 10 Feb 2010) | 5 lines
------------------------------------------------------------------------


zones and LDOMs.
possible, new inline style functions elsewhere. Better printing of local
printint of O/S version. Adapted to use generated style sheet where
versions. mk_ver_arch() and get_latest_kerns() needed for this. Better
Removed superfluous show_zpool() and show_fs() functions. Highlight latest/old kernel

	<dt>r473 | robertf | 2010-02-10 17:52:23 +0000 (Wed, 10 Feb 2010) | 7 lines
------------------------------------------------------------------------


	<dt>Adapted to use generated stylesheet and new inline_col class.

	<dt>r474 | robertf | 2010-02-11 09:48:42 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


Dynamically create stylesheet info for filesystem types

	<dt>r476 | robertf | 2010-02-11 11:26:23 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


FS types are now dynamically generated, so removed from here.

	<dt>r477 | robertf | 2010-02-11 11:27:11 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


and more efficient.
Properly use new colouring system. Generally much smaller, cleaner
show_export(). No longer use externals to count NFS mounts.
Pretty much rewritten. Totally new versions of show_fs() and

	<dt>r479 | robertf | 2010-02-11 15:10:46 +0000 (Thu, 11 Feb 2010) | 6 lines
------------------------------------------------------------------------


Added shared filesystem types.

	<dt>r480 | robertf | 2010-02-11 15:11:09 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


Moved show_parsed_sw() to show_parsed_list()

	<dt>r481 | robertf | 2010-02-11 15:11:53 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


Uses new colouring system.

	<dt>r482 | robertf | 2010-02-11 15:12:14 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added indent class.

	<dt>r483 | robertf | 2010-02-11 15:12:34 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


Extended key.

	<dt>r484 | robertf | 2010-02-11 15:29:20 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


Altered key to fit new colouring system

	<dt>r485 | robertf | 2010-02-11 15:29:45 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


Fixed unclosed tag.

	<dt>r486 | robertf | 2010-02-11 15:34:47 +0000 (Thu, 11 Feb 2010) | 3 lines
------------------------------------------------------------------------


Colour loaded/mounted CDs. Colour latest/not latest OBPs.

	<dt>r487 | robertf | 2010-02-12 22:46:46 +0000 (Fri, 12 Feb 2010) | 3 lines
------------------------------------------------------------------------


Use new inline_col class

	<dt>r488 | robertf | 2010-02-12 22:49:53 +0000 (Fri, 12 Feb 2010) | 3 lines
------------------------------------------------------------------------


Source tidying.

	<dt>r489 | robertf | 2010-02-12 22:50:00 +0000 (Fri, 12 Feb 2010) | 3 lines
------------------------------------------------------------------------


Always use a table for show_parsed_list() for uniformity. Added more splitting characters to fold_line()

	<dt>r490 | robertf | 2010-02-13 00:21:27 +0000 (Sat, 13 Feb 2010) | 3 lines
------------------------------------------------------------------------


v1.1 See header

	<dt>r491 | robertf | 2010-02-13 00:23:18 +0000 (Sat, 13 Feb 2010) | 3 lines
------------------------------------------------------------------------


Expanded key

	<dt>r492 | robertf | 2010-02-13 00:33:14 +0000 (Sat, 13 Feb 2010) | 3 lines
------------------------------------------------------------------------


Add lalign

	<dt>r493 | robertf | 2010-02-13 00:36:39 +0000 (Sat, 13 Feb 2010) | 3 lines
------------------------------------------------------------------------


ALOM version highlighting

	<dt>r494 | robertf | 2010-02-13 00:42:27 +0000 (Sat, 13 Feb 2010) | 3 lines
------------------------------------------------------------------------


Calmer colours.

	<dt>r495 | robertf | 2010-02-14 23:40:29 +0000 (Sun, 14 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added colours for webserver and database types.

	<dt>r497 | robertf | 2010-02-15 15:20:34 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


Typo

	<dt>r498 | robertf | 2010-02-15 15:27:04 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added DB and Webserver colours

	<dt>r500 | robertf | 2010-02-15 16:07:17 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


v2.0. See header for info.

	<dt>r501 | robertf | 2010-02-15 16:07:31 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added tape drive and fibre enclosure classes. Reinstated old blue and pink

	<dt>r506 | robertf | 2010-02-15 17:56:27 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


Add db, ws, and storage colours to stylesheet.

	<dt>r507 | robertf | 2010-02-15 17:56:54 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


New show_storage() function.

	<dt>r508 | robertf | 2010-02-15 17:57:14 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


Handle undefined document root

	<dt>r509 | robertf | 2010-02-15 18:00:26 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


Updated key.

	<dt>r510 | robertf | 2010-02-15 18:08:40 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added show_dtlogin()

	<dt>r511 | robertf | 2010-02-15 18:20:58 +0000 (Mon, 15 Feb 2010) | 3 lines
------------------------------------------------------------------------


Bugfixes. Some function names needed changing.

	<dt>r512 | robertf | 2010-02-16 10:45:15 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Tidied up, and stopped storage classes being left-aligned

	<dt>r513 | robertf | 2010-02-16 11:38:11 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Hacked together rudimentary server view.

	<dt>r514 | robertf | 2010-02-16 12:57:36 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Reinstated. 

	<dt>r515 | robertf | 2010-02-16 14:15:55 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Reinstate colours class

	<dt>r516 | robertf | 2010-02-16 14:33:28 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Initial release. Data moved from security class

	<dt>r517 | robertf | 2010-02-16 15:15:39 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Adapted to work with new class layout

	<dt>r518 | robertf | 2010-02-16 15:44:15 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


adapted for new file and class layout

	<dt>r519 | robertf | 2010-02-16 16:03:35 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Working, in a very rudimentary way

	<dt>r520 | robertf | 2010-02-16 16:04:31 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


New class and file layout, but no significant code changes.

	<dt>r521 | robertf | 2010-02-16 16:05:10 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Missing require()

	<dt>r522 | robertf | 2010-02-16 16:10:10 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Fixed to work with new class layout and new colour methods.

	<dt>r523 | robertf | 2010-02-16 16:16:35 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added all Solaris 8/9/10 updates

	<dt>r524 | robertf | 2010-02-16 22:42:45 +0000 (Tue, 16 Feb 2010) | 3 lines
------------------------------------------------------------------------


First properly working version. Classes farmed out to display_classes.php

	<dt>r527 | robertf | 2010-02-25 15:54:47 +0000 (Thu, 25 Feb 2010) | 3 lines
------------------------------------------------------------------------


Added classes for single server view.

	<dt>r528 | robertf | 2010-02-25 15:55:03 +0000 (Thu, 25 Feb 2010) | 3 lines
------------------------------------------------------------------------


Fixes for correct HTML compliance.

	<dt>r529 | robertf | 2010-02-25 16:13:45 +0000 (Thu, 25 Feb 2010) | 3 lines
------------------------------------------------------------------------


Don't try to colour shares on single server audit.

	<dt>r530 | robertf | 2010-02-26 11:28:28 +0000 (Fri, 26 Feb 2010) | 3 lines
------------------------------------------------------------------------


Sort first on server hostname, then sort each server's zone, so zones and servers don't get mixed up. Be quiet on unknown speed interfaces.

	<dt>r534 | robertf | 2010-02-28 17:27:01 +0000 (Sun, 28 Feb 2010) | 3 lines
------------------------------------------------------------------------

Added noborder multicells. Removed redundant classes.

	<dt>r538 | robertf | 2010-03-01 21:11:25 +0000 (Mon, 01 Mar 2010) | 2 lines
------------------------------------------------------------------------


Source tidying.

	<dt>r539 | robertf | 2010-03-01 21:11:47 +0000 (Mon, 01 Mar 2010) | 3 lines
------------------------------------------------------------------------


Automatically close keys.

	<dt>r540 | robertf | 2010-03-01 21:12:05 +0000 (Mon, 01 Mar 2010) | 3 lines
------------------------------------------------------------------------


Expand show_parsed_list to do borderless tables, and make show_nic() use it. New show_uptime(). Source tidying. Improved show_kernel()

	<dt>r541 | robertf | 2010-03-01 21:13:20 +0000 (Mon, 01 Mar 2010) | 3 lines
------------------------------------------------------------------------


Very small font for multicellsmall

	<dt>r542 | robertf | 2010-03-01 21:16:36 +0000 (Mon, 01 Mar 2010) | 3 lines
------------------------------------------------------------------------


Better global/local checking

	<dt>r544 | robertf | 2010-03-01 22:35:10 +0000 (Mon, 01 Mar 2010) | 3 lines
------------------------------------------------------------------------


Added lofs colour

	<dt>r547 | robertf | 2010-03-04 15:40:18 +0000 (Thu, 04 Mar 2010) | 3 lines
------------------------------------------------------------------------


Added specific ALOM colour

	<dt>r548 | robertf | 2010-03-05 19:10:46 +0000 (Fri, 05 Mar 2010) | 3 lines
------------------------------------------------------------------------


Style for MAC addresses

	<dt>r552 | robertf | 2010-03-15 10:28:15 +0000 (Mon, 15 Mar 2010) | 3 lines
------------------------------------------------------------------------


Better unit conversions. Better FS audit handling. Make MAC stuff same as NIC stuff where possible

	<dt>r553 | robertf | 2010-03-15 10:29:26 +0000 (Mon, 15 Mar 2010) | 3 lines
------------------------------------------------------------------------


size calculation.
Bugfixed ALOM IP guessing, colouring of loaded/mounted CDs, and Mb/Gb/Tb

	<dt>r556 | robertf | 2010-03-15 18:22:25 +0000 (Mon, 15 Mar 2010) | 4 lines
------------------------------------------------------------------------


Bugfix for faulty NFS mountpoints.

	<dt>r557 | robertf | 2010-03-16 10:55:56 +0000 (Tue, 16 Mar 2010) | 3 lines
------------------------------------------------------------------------


Fixed filesystem stuff for new auditor output format

	<dt>r559 | robertf | 2010-03-16 14:59:22 +0000 (Tue, 16 Mar 2010) | 3 lines
------------------------------------------------------------------------


Bugfix on lom hostnames

	<dt>r560 | robertf | 2010-03-16 14:59:41 +0000 (Tue, 16 Mar 2010) | 3 lines
------------------------------------------------------------------------


Added vdisk fs 

	<dt>r561 | robertf | 2010-03-16 17:13:26 +0000 (Tue, 16 Mar 2010) | 3 lines
------------------------------------------------------------------------


show_capacity()
Changed from_b() to use simple ifs instead of trying to be clever. New

	<dt>r562 | robertf | 2010-03-16 17:50:36 +0000 (Tue, 16 Mar 2010) | 4 lines
------------------------------------------------------------------------


More efficient screening of unwanted filesystems

	<dt>r563 | robertf | 2010-03-16 18:32:20 +0000 (Tue, 16 Mar 2010) | 3 lines
------------------------------------------------------------------------


Replaced final ereg()s with preg()s or strpos()es

	<dt>r564 | robertf | 2010-03-16 18:38:28 +0000 (Tue, 16 Mar 2010) | 3 lines
------------------------------------------------------------------------


LDOM aware

	<dt>r566 | robertf | 2010-03-17 09:52:10 +0000 (Wed, 17 Mar 2010) | 3 lines
------------------------------------------------------------------------


VirtualBox aware.

	<dt>r567 | robertf | 2010-03-17 10:13:46 +0000 (Wed, 17 Mar 2010) | 3 lines
------------------------------------------------------------------------


line
Proper reporting of ldoms, vboxes, physicals and locals in "Auditing..."

	<dt>r568 | robertf | 2010-03-17 10:51:03 +0000 (Wed, 17 Mar 2010) | 4 lines
------------------------------------------------------------------------


Clickable site names on hosted services page.

	<dt>r569 | robertf | 2010-03-17 12:00:20 +0000 (Wed, 17 Mar 2010) | 3 lines
------------------------------------------------------------------------


Added link types for resolved/unresolved sites on hosted services page

	<dt>r570 | robertf | 2010-03-17 13:16:28 +0000 (Wed, 17 Mar 2010) | 3 lines
------------------------------------------------------------------------


file
New way of displaying websites. Group together all URIs with a common config

	<dt>r571 | robertf | 2010-03-17 13:17:09 +0000 (Wed, 17 Mar 2010) | 4 lines
------------------------------------------------------------------------

	<dt>r573 (17/03/10)</dt>
	<dd>Limit the number of servers per page. (<tt>PER_PAGE</tt> constant,
	=20.)</dd>

	<dt>r589 (24/03/10)</dt>
	<dd>Amalgamated <tt>show_disk()</tt>, <tt>show_optical()</tt> into
	<tt>show_storage()</tt>. Display swap space. Make UID collision
	highlighting work again. Better uptime presentation.</dd>

	<dt>r619 (08/04/10)
	<dd>Support for hover-over on package names. Package def files.</dd>

------------------------------------------------------------------------

</dl>

<?php

$pg->close_page();

?>

