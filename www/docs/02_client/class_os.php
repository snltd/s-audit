<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "O/S audits";
$pg = new docPage($menu_entry);

?>

<h1>O/S Audits</h1>

<p>Operating system audits are performed by running</p>

<pre class="cmd">
# s-audit.sh os
</pre>

<h2>Checks</h2>

<dl>
	<dt>distribution (os_dist)</dt>
	<dd>Tries to work out the &quot;distribution&quot; of the operating
	environment. Normally this is Solaris, but nowadays may be OpenSolaris,
	<a href="http://www.nexenta.org">Nexenta</a>, <a
	href="http://www.belenix.org">BeleniX</a>, or others. Displays as
	&quot;distribution&quot;.</dd>

	<dt>version (os_ver)</dt>
	<dd>Displays the SunOS version of the operating system. For Solaris
	releases, also displays the marketing release number, for instance
	&quot;Solaris 2.6&quot;. Displays as &quot;version&quot;.</dd>

	<dt>release (os_rel)</dt>
	<dd>Prints the release of the operating environment. For recent Solaris
	releases this is the month/year release date; for older Solarises it
	will try to get the WoS number. Other distributions will vary. Displays
	as &quot;release&quot;.</dd>

	<dt>kernel</dt>
	<dd>Prints the kernel revision, either the xxxxxx-yy number for 5.10 and
	older, or the "xxx" build number for 5.11.</dd>

	<dt>boot env</dt>
	<dd>Lists <tt>beadm</tt>, Live Upgrade, and miniroot failsafe boot
	environments. The type of environment is always given first and followed
	by the name.</dd>
	
	<dd>For <tt>beadm</tt> environments, the mountpoint is given in
	parentheses, followed by the boot flags. An &quot;N&quot; means the
	environment is active at the time of the audit, an &quot;R&quot; means
	the environment will be active after the next reboot.</dd>

	<dd>For Live Upgrade, the state (complete or incomplete) is shown in
	parentheses, followed by the same boot flags as are used for
	<tt>beadm</tt> environments.</dd>

	<dd>Failsafe miniroots are shown only if they exist as a
	<tt>*miniroot*</tt> file in <tt>/boot</tt>, <strong>and</strong> if it
	exists in the grub menu.</dd>

	<dt>hostid</dt>
	<dd>Prints the hostid.</dd>

	<dt>VM</dt>
	<dd>Lists the local zones, logical domains, VirtualBoxes or XEN domUs
	hosted on the box. The VM type is given first, followed by the VM's name
	(as the host sees it, which is not necessarily the hostname of the guest
	O/S itself). VM info follows in parentheses. For local zones this is the
	zone brand and the zone's state; for LDOMs the port
	number the console runs on and the state of the LDOM; for VBoxes
	the O/S type and the domain state; and for XEN domains, the O/S type and
	state of the domain. (Note that all paravirtualized OSes report as
	&quot;Linux&quot; - this is a limit of the XEN framework.) VM resource
	caps are given in [square brackets], CPU first, then memory. If a local
	zone has no resource caps, the brackets are empty. Local zones also
	report the zone path. The test is not run in local zones.</dd>

	<dt>scheduler</dt>
	<dd>If the system's process scheduler class has been altered, this check
	gives the new class.</dd>

	<dt>svc_count</dt>
	<dd>On a machine running SMF, reports the number of services installed,
	the number currently running, and the number currently in a maintenence
	state. Reports as &quot;SMF services&quot;.</dd>

	<dt>uptime</dt>
	<dd>The uptime of the zone.</dd>

	<dt>package_count</dt>
	<dd>Says how many packages are installed in this zone. The number of
	partially installed packages is reported in brackets. Also reports the
	type of packages, for instance SYSV or IPS. SYSV machines also report
	the Solaris software cluster used to build the machine.</dd>

	<dt>patch_count</dt>
	<dd>Says how many patches have been installed in this zone. Irrelevant
	in IPS systems.</dd>

	<dt>publisher</dt>
	<dd>On IPS systems, lists the package publishers being used by the
	system. Lists the repository name, followed by its URL (in parentheses).
	The preferred repository is denoted by &quot;(preferred)&quot;.</dd>

</dl>

<?php

$pg->close_page();

?>

