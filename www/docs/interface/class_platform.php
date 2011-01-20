<?php

//============================================================================
//
// class_platform.php
// ------------------
//
// Platform audit page of s-audit web interface documentation. The main
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

$menu_entry = "platform audit";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry, $generic_key);
$dh->doc_class_start();

?>

	<dt>hardware</dt>
	<dd>This field displays the name of the hardware. The names of SPARC
	systems are shown, whilst Intel/AMD machines are simply listed as
	&quot;i86pc&quot;. On a separate line, in parentheses, the instruction
	set architecture is displayed. That is, 32- or 64-bit, SPARC or
	x86. No information is displayed for local zones.</dd>

	<dd>The following colour-coding is used:</dd>

<?php
	echo $dh->colour_key($grid_key["hardware"]);
?>

	<dt>virtualization</dt>
	<dd>This field explains what type of environment the system is running
	in. If it is a physical server, &quot;physical&quot; is displayed. For
	virtualized environments, the nature of that virtualization is given,
	which may be LDOM, VirtualBox or VMWare.</dd>
	
	<dd>The following colour-coding is used:</dd>

<?php
	echo $dh->colour_key(
	array(
		array("global zones on zoned systems are in blue boxes", "boxblue",
		false), array("whole-root local zones are in red boxes", "boxred",
		false), array("non-native local zone are on amber fields",
		"solidamber", false)));
?>
	
	<dd>For non-global zones, the nature of the zone (whole or sparse root)
	is listed, along with the brand, if it is not native.</dd>
	
	<dt>CPU</dt>
	<dd>Shows the number of CPUs along with their clock speed and number of
	cores. Note that for the primary domain of a machine with LDOMs, this
	will only show the number of CPUs available to that domain, not to the
	server as a whole. For VirtualBox and VMWare environments, the number of
	CPUs available to the operating system is displayed - it may not have
	exclusive use of those CPUs.  No information is displayed for local
	zones.</dd>

	<dt>memory</dt>
	<dd>Shows the physical and virtual memory in an environment.  Note that
	this is the total memory, not the &quot;free&quot; or
	&quot;available&quot; memory at the time of the audit.  If a machine has
	no swap space, it will be highlighted by an amber field. In the primary
	domain of a machine with LDOMs, this will show the amount of physical
	RAM available to that domain, not installed in the server as a whole.
	For VirtualBox and VMWare environments, the memory given over to the
	system - it may not have exclusive use of it.  No information is
	displayed for local zones.</dd>

	<dd>The following colour-coding is used:</dd>

<?php
	echo $dh->colour_key(
	array(
		array("systems with no swap space are on amber fields",
		"solidamber", false)));
?>

	<dt>serial number</dt>
	<dd>If Sun Sneep has been used to put a machine's serial number in the
	EEPROM, it will be displayed here. Blank in local zones.</dd>

	<dt>OBP</dt>
	<dd>Shows the OBP version of a SPARC server. Information is still
	displayed for x86 machines, but will be of limited value. Blank in local
	zones.</dd>

	<dt>ALOM f/w</dt>
	<dd>On supported SPARC servers, shows the version of the ALOM firmware.
	Blank in local zones.</dd>

	<dt>ALOM IP</dt>
	<dd>On some SPARC servers, for instance v210s, it is possible to get the
	firmware version and IP address of the system ALOM from inside Solaris.
	<tt>s-audit.sh</tt> does this if it can, and such results are presented
	in this column on a solid coloured field. (The default colour is orange,
	but it can be changed in <tt>_conf/nic_colours.php</tt>. Please see
	below for the current colour on this system.)</dd>
	
	<dd>However, on some machines, such as T2000s, the lack of a
	<tt>scadm</tt> binary makes it impossible for <tt>s-audit.sh</tt> to get
	the ALOM IP address. When I was developing s-audit, my site's naming
	convention was to have system ALOMs in DNS, using hostnames formed by
	appending <tt>-lom</tt> to the system hostname. So, for instance, the
	ALOM on <tt>cs-db-02</tt> would be <tt>cs-db-02-lom</tt>. If you use a
	similar convention, the s-audit interface will try to &quot;guess&quot;
	ALOM addresses by appending your suffix to the hostname of the server it
	is looking at, then seeing if the name it has created resolves in DNS.
	If it does, then the address to which it resolves is displayed. You can
	set your own ALOM suffix by changing the</dd>

	<dd>
	<pre>
	define("ALOM_SFX", "-lom." . STRIP_DOMAIN);
	</pre>
	</dd>
	
	<dd>line in <tt>_conf/s-audit_config.php</tt>. If you have a standard
	colour of cable you use to connect your ALOMs, you can also set this
	with the</dd>

	<dd>
	<pre>
	"alom" => "#e9a655"             // ALOM cables
	</pre>
	</dd>

	<dd>line in <tt>_conf/nic_colours.php</tt>. The default is orange,
	because we cabled our ALOMs with orange cable at my old site. This
	system colour codes authoritative and &quot;guessed&quot; ALOM addresses
	as follows:</dd>

<?php
	echo $dh->colour_key($grid_key["ALOM IP"]);
?>

	<dt>storage</dt>
	<dd>This field shows all storage to which a host has access. The
	following colour-coding is used to highlight different storage
	types:</dd>

<?php
	echo $dh->colour_key($grid_key["storage"]);
?>

	<dd>Disks, on a pale blue field, are grouped by size and bus type, for
	instance, SCSI, VBOX, IDE (note that SATA disks are shown as
	&quot;IDE&quot;), SCSI VHCI, SAS, etc. Optical disks are on a grey
	field, again grouped by bus type. Optical drives which have disks loaded
	or mounted are highlighted in green and amber respectively.</dd>
	
	<dd>Tape drives are shown on a red field.  Note that jukeboxes typically
	contain multiple drives, and the drives are reported, not the
	enclosures.</dd>
	
	<dd>Optical storage arrays, for instance T3s and 3510s are shown on a
	dark blue field. If they can be retrieved, the model name and firmware
	revision are shown. Note that the disks the array contains will be
	listed AS WELL AS the array itself.</dd>

	<dt>PCI card</dt>
	<dd>Lists PCI cards. The type of card, e.g. network, SCSI, etc, is shown
	in <strong>bold face</strong>, with roughly human-readable details of
	the card following it in parentheses. For instance, &quot;SUNW,pci-qfe
	PCI0@33MHz&quot; can be interpreted as a Sun PCI Quad Fast Ethernet card
	in PCI slot 0, which has a 33MHz bus speed.</dd>

	<dd>This information can only be reliably retrieved on SPARC systems
	running Solaris 10 or later. If you see information for older, or x86
	systems, you're lucky.</dd>

	<dt>SBUS card</dt>

	<dt>printer</dt>
	<dd>Lists any printers to which the host has access. They may or may not
	be physically attached. The default printer is denoted.</dd>

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
	echo $dh->colour_key($grid_key["NIC"]), $dh->doc_class_end(),
	$pg->close_page();

?>

