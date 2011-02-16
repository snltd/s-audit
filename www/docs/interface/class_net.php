<?php

//============================================================================
//
// class_security.php
// ------------------
//
// Security audit page of s-audit web interface documentation. The main
// docPage() class is in display_classes.php.
//
// Note that the first part of the documentation for all class pages is
// printed by the docHelper::doc_class_start() function, and the end by
// docHelper::doc_class_end().
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

// Include the key file for this page to help us document the colour-coding.
// This help keep things consistent.

include(KEY_DIR . "/" . preg_replace("/class/", "key",
basename($_SERVER["PHP_SELF"])));
include(KEY_DIR . "/key_generic.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "networking audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry, $generic_key);
$dh->doc_class_start();

?>

<dt>NTP</dt>
<dd>This column lists the NTP servers a machine is using.
&quot;Normal&quot; servers are listed normally, whilst preferred
servers are highlighted by a green field. If a machine is also acting as an
NTP server, &quot;broadcasting as server&quot; is displayed on an orange
field. NTP services not running are denoted by a red box.</dd>

<?php
	echo $dh->colour_key($grid_key["NTP"]);
?>

<dt>name service</dt>
<dd>Lists the methods by which the machine resolves usernames, hosts, and
RBAC credentials.</dd>

<dt>DNS server</dt>
<dd>Lists, one per line, the DNS servers the machine uses to resolve
hostnames. If you do not use DNS, this column will not be displayed.</dd>

<dt>NIS domain</dt>
<dd>Shows the NIS domain to which a machine belongs. If you do not use NIS,
this column will not be displayed.</dd>

<dt>name server</dt>
<dd>If a machine is acting as a name server, this column shows the domains
which it serves. Currently only DNS and NIS are supported.</dd>

<dd>Domains for which the machine acts as a primary, or master server, are
shown on a green field. Domains for which it acts as a secondary, or slave,
server, are on amber. The type of name service which serves the domain is
shown in <strong>bold face</strong>.</dd>

<dd>If you do not have any NIS or DNS servers, this column will not be
displayed.</dd>

<?php
	echo $dh->colour_key($grid_key["name server"]);
?>

<dt>port</dt>
<dd>This column lists open ports on a machine, and tries to explain what
program has opened them.</dd>

<dd>The number of the port is shown in <strong>bold face</strong>, with
further information in parentheses. This gives the service name first,
(taken from <tt>/etc/services</tt>) then a forward slash, then the name of
the process which owns the open port. If <tt>s-audit.sh</tt> was unable to
find a service name or owning process, a dash is displayed. This is common
for kernel-level services like <tt>lockd</tt> or <tt>sunrpc</tt>.</dd>

<dd>The inteface holds a list of ports it expects to be open. This is stored
in the <a href="../extras/omitted_data.php"><tt>OMITTED_DATA_FILE</tt></a>
file. The expected ports on this system are:</dd>

<?php
	echo $dh->list_omitted("usual_ports");
?>

<dd>Open ports which are not in the list above are placed on an amber
field. Ports which are held open by <tt>inetd</tt> are denoted by a red
box.</dd>

<dd>If <tt>OMIT_PORT_THRESHOLD</tt> is defined in <tt>user_config.php</tt>
then ports higher than the number there defined are not displayed. On this
system

<?php
	echo (defined("OMIT_PORT_THRESHOLD"))
		? "ports above " . OMIT_PORT_THRESHOLD . " are not"
		: "<tt>OMIT_PORT_THRESHOLD</tt> is not defined, so all open ports
		are";
?>

displayed.</dd>

<?php
	echo $dh->colour_key($grid_key["port"]);
?>

<dt>route</dt>
<dd>Displays an interpreted version of the machines routing table. Loopback
and IPV6 routes are not shown. Default routes which are not defined in the
machines <tt>/etc/defaultrouter</tt> file are highlighted on an amber field.
This may be a problem in global zones, but is normal in local zones which
use a shared IP instance. Persistent routes are highlighted by a green
box. Where applicable, the interface to which a route applies is given after
the route, in parentheses.</dd>

<?php
	echo $dh->colour_key($grid_key["route"]);
?>

	<dt>MAC</dt>
	<dd>Lists the MAC address for each discovered interface. Blank in local
	zones, unless that zone uses a VNIC or exlusive IP instance.</dd>

	<dt>NIC</dt>
	<dd>On the left of the field are physical (in <strong>bold</strong>
	face) or virtual NIC names. For physical NICs, the speed and duplex
	setting, if they could be determined, are displayed below the name. On
	the right is the IP address assigned to that NIC, with its hostname in
	parentheses. If a NIC is not cabled, or not configured, that information
	is displayed. In a global zone which has local zones under it, virtual
	NICs are shown in the global zone, paired with the zone to which they
	belong. They are also shown in the row belonging to the local zone.
	Exclusive IP instances are denoted.</dd>

	<dd>Crossbow VNICs are listed by name, and in a global zone, the
	physical interface to which they are bound is displayed.</dd>

	<dd>Interfaces assigned by DHCP say &quot;DHCP&quot; under their
	physical name, and IPMP teamed intefaces are also denoted.</dd>

	<dd>NIC lines are colour-coded according to the contents of the
	<tt>_conf/nic_colours.php</tt> file. This system is currently using the
	following colours:</dd>

<?php
	echo $dh->colour_key($grid_key["NIC"])
?>


<?php

$dh->doc_class_end();
$pg->close_page();

?>

