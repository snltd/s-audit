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
	<dt>os_dist</dt>
	<dd>Tries to work out the &quot;distribution&quot; of the operating
	environment. Normally this is Solaris, but nowadays may be OpenSolaris,
	<a href="http://www.nexenta.org">Nexenta</a>, <a
	href="http://www.belenix.org">BeleniX</a>, or others. Displays as
	&quot;distribution&quot;.</dd>

	<dt>os_ver</dt>
	<dd>Displays the SunOS version of the operating system. For Solaris
	releases, also displays the marketing release number, for instance
	&quot;Solaris 2.6&quot;. Displays as &quot;version&quot;.</dd>

	<dt>os_rel</dt>
	<dd>Prints the release of the operating environment. For recent Solaris
	releases this is the month/year release date; for older Solarises it
	will try to get the WoS number. Other distributions will vary. Displays
	as &quot;release&quot;.</dd>

	<dt>kernel</dt>
	<dd>Prints the kernel revision, either the xxxxxx-yy number for 5.10 and
	older, or the "xxx" build number for 5.11.</dd>

	<dt>boot env</dt>
	<dd>Lists any boot environments, if the host supports them. The name of
	the environment is given first, followed by the mountpoint and any
	active flags.</dd>

	<dt>hostid</dt>
	<dd>Prints the hostid.</dd>

	<dt>local_zone</dt>
	<dd>Lists the local zones on the box, giving the brand and state of each
	one, and the zonepath, or zone root. Only run in global zones.</dd>

	<dt>LDOM</dt>
	<dd>Lists the guest domains on the box, reporting the domain name,
	state, number of assigned VCPUs, amount of assigned physical RAM, and
	the console port of each. Omitted in local zones.</dd>

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
	type of packages, for instance SYSV or IPS.</dd>

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

