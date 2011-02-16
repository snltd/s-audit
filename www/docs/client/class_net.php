<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "networking audits";
$pg = new docPage($menu_entry);

?>

<h1>Platform Audits</h1>

<p>Invoked by,</p>

<pre class="cmd">
# s-audit.sh net
</pre>

<p>this audit type looks at a machine's network configuration.</p>

<h2>Checks</h2>

<dl>

	<dt>NTP</dt>
	<dd>Lists the NTP servers a machine is using. It does this by reading
	<tt>/etc/inet/ntp.conf</tt>, not by querying <tt>ntpq</tt>, so output
	here does not guarantee a synchronized clock. If the NTP daemon
	(<tt>xntpd</tt>) is not running, then &quot;not running&quot; is
	displayed after the server name. Preferred NTP servers are also
	denoted.</dd>

	<dd>If a machine is broadcasting NTP packets, &quot;acting as
	server&quot; is displayed.</dd>
 
	<dt>name service</dt>
	<dd>Examines <tt>/etc/nsswitch.conf</tt> to look what name services are
	being used for usernames/passwords, host name resolution, and RBAC
	roles.</dd>

	<dt>DNS server</dt>
	<dd>Reports the DNS servers the machine is using for hostname
	resolution.</dd>

	<dt>NIS domain</dt>
	<dd>If the machine is using NIS, the domain name is displayed.</dd>

	<dt>name server</dt>
	<dd>If the machine being audited is acting as a name server, then this
	check produces a list of the domains it serves up, along with the name
	service type and whether it is a master or slave server. Output is of
	the form</dd>

	<pre>name_service (master/slave) domain</pre>

	<dd>Currently the only supported name service types are DNS and
	NIS.</dd>

	<dt>port</dt>
	<dd>Gets a list of all open ports, and attempts to work out which
	program is using each one. Output is of the form</dd>

	<pre>port_number:service:process</pre>
	
	<dd>The &quot;service&quot;name comes from <tt>/etc/services</tt>, and
	the &quot;process&quot; comes from examining processes with
	<tt>pfiles(1)</tt>. Information is therefore limited on old versions of
	Solaris. Requires root privileges. Because it uses <tt>netstat</tt>,
	it's possible this test can hang.</dd>

	<dt>route</dt>
	<dd>This check shows an expurgated version of the current routing table.
	Loopback and multicast routes are discarded, as are <tt>netstat</tt>'s
	&quot;flags&quot;, &quot;ref&quot; and &quot;use&quot; columns. If a
	default route is not found in the system's <tt>/etc/defaultrouter</tt>
	file then &quot;not in defaultrouter&quot; is displayed after the route.
	Note that this is expected behaviour in a local zone using a shared IP
	instance. If any routes are persistent, then this is also reported. The
	interface to which a route belongs, if any, is also displayed.</dd>

	<dt>snmp</dt>
	<dd>Says if <tt>snmpdx</tt> is running.</dd>

	<dt>mac</dt>
	<dd>Gets MAC addresses. The script operates in one of two ways.
	Ordinarily it will only get the MAC of plumbed interfaces. However, if
	the <tt>-M</tt> option is supplied, <tt>s-audit.sh</tt> will temporarily
	plumb each unplumbed interface to get the address. This is the only test
	which is capable of changing, even temporarily, the state of the machine
	being audited, and you may not wish to use it. Requires root
	privileges.</dd>

	<dt>nic</dt>
	<dd>This test queries network interfaces. It reports the name of the
	interface, along with the IP address and the zone (if any) which uses
	that interface. Uncabled and unconfigured interfaces are reported as
	such.  The link speed and duplex setting is usually reported, but some
	old cards on some old versions of Solaris do not support this, and
	neither do virtual switches in LDOMs. VLANned ports are recognized, as
	are etherstubs, virtual NICs, and IPMP teams. Interfaces with DHCP
	assigned addresses are reported, as are exclusive IP instances for
	zones. A full audit requires root privileges, though much useful
	information can be obtained as a non-privileged user.</dd>

	<dd>Output is not particularly human-readable, as it has to carry a
	lot of information, but takes the form:</dd>

	<pre>device|address|hostname|speed-duplex|ipmp_group|vlan</pre>

	<dd>The <tt>ipmp_group</tt> field can also hold DHCP information, and
	the <tt>vlan</tt> field can also contain information on virtual
	switches.</dd>

</dl>

<?php

$pg->close_page();

?>

