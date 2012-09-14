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

	<dd>
	<pre>name_service (master/slave) domain</pre>
	</dd>

	<dd>Currently the only supported name service types are DNS and
	NIS.</dd>

	<dt>routing</dt>
	<dd>Tells you if IP routing and/or forwarding is enabled. IPv4 and IPv6
	are reported separately. Only enabled services are shown, so on a system
	with routing and forwarding disabled, expect no information.</dd>

	<dd>On Solaris 10 and later, when <tt>routeadm</tt> is available, status
	is reported in two parts, for exampled <tt>enabled/enabled</tt>. The
	first part is the current configuration, the second the system state.
	See the <tt>routeadm</tt> man page for further information.</dd>

	<dt>port</dt>
	<dd>Gets a list of all open ports, and attempts to work out which
	program is using each one. Output is of the form</dd>

	<dd>
	<pre>port_number:service:process</pre>
	</dd>
	
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

	<dt>net</dt>
	<dd>This test queries network devices. For Solaris versions earlier than
	5.10, this means physical and virtual NICs, but for later versions,
	<tt>s-audit.s</tt> reports on aggregates, etherstubs, virtual switched
	and VNICs. On Solaris 10 and later, all physical NICs are reported,
	while earlier versions can only find plumbed interfaces. The following
	network device types are reported in the following way.</dd>

	<dd>
		<dt>phys</dt>
		<dd>Physical NICs. Note that to an instance of Solaris running in a
		VirtualBox or under VMWare, NICs presented by the host environment
		appear to be physical devices.</dd>

		<dt>virtual</dt>
		<dd>Virtual NICs display their IP address, hostname or, if they
		belong to a zone, the name of that zone. Exclusive IP instances are
		reported as such.</dd>

		<dd>The name of the interface is shown first, then the
		&quot;phys&quot; type, the IPv4 IP address, the hostname, the link
		speed and duplex (in parentheses), then the MAC address.</dd>

		<dd>IPv6 interfaces are not currently supported.</dd>

		<dt>etherstub</dt>
		<dd>The name of the etherstub is shown, followed by the string
		&quot;etherstub&quot;.

		<dt>clprivnet</dt>
		<dd>Sun Cluster private interconnects are shown as
		&quot;clprivnet&quot; type. The device name is shown, followed by
		the type, and the physical NICs which make up the aggregate.
		Infiniband private interconnects have not been tested.</dd>

		<dt>aggregate</dt>
		<dd>Aggregates are displayed with the aggregate name, the
		&quot;aggr&quot; keyword, then the devices which have been combined
		to make the aggregate. The link policy, and MAC address are also
		displayed.</dd>

		<dt>vswitch</dt>
		<dd>If you are using Logical Domains, <tt>s-audit.sh</tt> will find
		any virtual switches. It reports the switch name, the
		&quot;vswitch&quot; keyword, and the physical NIC to which the
		switch is bound. The &quot;phys&quot; data for that NIC will be
		tagged with &quot;+vsw&quot;.</dd>
	
		<dt>LLT</dt>
		<dd>Veritas Cluster Server private interconnects do not have a
		device name, so are shown as &quot;LLT link over&quot;, and the
		device names of the NICs used to make the LLT link. Only ethernet
		interfaces have been tested.</dd>

		</dt>

	</dd>

	<dd>Note that some old cards on some old versions of Solaris are unable
	to report their speed, as are virtual switches in LDOMs. VLANned ports
	are recognized and reported as such. Interfaces with DHCP assigned
	addresses are reported, as are IPMP groups.</dd>

	<dd>Ordinarily the script will only get the MAC of plumbed interfaces.
	However, if invoked with the <tt>-M</tt> option, <tt>s-audit.sh</tt>
	will temporarily plumb each unplumbed interface to get the address. This
	is the only test which is capable of changing, even temporarily, the
	state of the machine being audited, and you may not wish to use it.</dd>

	<dd>Requires root privileges for a full audit, but will produce useful
	data as a non-privileged user.</dd>

</dl>

<?php

$pg->close_page();

?>

