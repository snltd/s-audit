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

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "platform audit";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry);
$dh->doc_class_start();

?>

<dt>hardware</dt>
<dd>This field displays the name of the hardware. The names of SPARC systems
are shown, whilst Intel/AMD machines are simply listed as &quot;i86pc&quot;.
On a separate line, in parentheses, the instruction set architecture is
displayed. That is, 32- or 64-bit, SPARC or x86. No information is displayed
for local zones.</dd>

<dd>The following colour-coding is used:</dd>

<?php
	echo $dh->colour_key($dh->grid_key["hardware"]);
?>

<dt>virtualization</dt>
<dd>This field explains what type of environment the system is running in.
If it is a physical server, &quot;physical&quot; is displayed. For
virtualized environments, the nature of that virtualization is given, which
may be a harware domain (as on an E10K for instance), XEN domain, Logical
Domain, VirtualBox or VMWare.</dd>

<dd>The following colour-coding is used:</dd>

<?php
	echo $dh->colour_key(
	array(
		array("global zones on zoned systems are in blue boxes", "boxblue",
		false),
		array("whole-root local zones are in red boxes", "boxred", false),
		array("non-native local zone are on amber fields", "solidamber",
		false)
		)
	);
?>

<dd>Global zones are denoted by a blue box. If a system does not have a blue
box in this field then it does not support zones.</dd>

<dd>For non-global zones, the nature of the zone (whole or sparse root) is
listed, along with the brand, if it is not native. On Solaris 11 hosts,
&quot;solaris&quot; branded zones are considered native.</dd>


<dt>CPU</dt>
<dd>Shows the number of CPUs along with their clock speed and number of
cores. Note that for the primary domain of a machine with LDOMs, this will
only show the number of CPUs available to that domain, not to the server as
a whole. For VirtualBox and VMWare environments, the number of CPUs
available to the operating system is displayed - it may not have exclusive
use of those CPUs.  No information is displayed for local zones.</dd>

<dt>memory</dt>
<dd>Shows the physical and virtual memory in an environment.  Note that this
is the total memory, not the &quot;free&quot; or &quot;available&quot;
memory at the time of the audit.  If a machine has no swap space, it will be
highlighted by an amber field. In the primary domain of a machine with
LDOMs, this will show the amount of physical RAM available to that domain,
not installed in the server as a whole.  For VirtualBox and VMWare
environments, the memory given over to the system - it may not have
exclusive use of it.  No information is displayed for local zones.</dd>

<dd>The following colour-coding is used:</dd>

<?php
	echo $dh->colour_key(
		array(
			array("systems with no swap space are on amber fields",
			"solidamber", false)
			)
		);
?>

<dt>serial number</dt>
<dd>If Sun Sneep has been used to put a machine's serial number in the
EEPROM, it will be displayed here. Blank in local zones.</dd>

<dt>OBP</dt>
<dd>Shows the OBP version of a SPARC server. Information is still
displayed for x86 machines, but will be of limited value. Blank in local
zones.</dd>

<dd>The version number will be on a green or red field depending on whether
or not it is the highest version number for <em>the hardware shown in the
&quot;hardware&quot; field</em>. Therefore multiple green boxes may be seen
with different contents. In that case each shows the highest currently
installed OBP version for that particular server type.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["OBP"]);
?>

<dt>LOM f/w</dt>
<dd>On supported SPARC servers, shows the version of the LOM or SC firmware,
along with the type of controller.  Blank in local zones.</dd>

<dd>As with the OBP version, boxes are colour coded to show the highest
installed version numbers for each hardware type.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["ALOM f/w"]);
?>

<dd>Note that old versions of the s-audit client call this field &quot;ALOM
IP&quot;, so you may see both fields in your audits.</dd>


<dt>LOM IP</dt>
<dd>On some SPARC servers, for instance v210s, it is possible to get the
firmware version and IP address of the system LOM or SC from inside Solaris.
<tt>s-audit.sh</tt> does this if it can, and such results are presented in
this column on a solid coloured field. (The default colour is orange, but it
can be changed in <tt>_conf/nic_colours.php</tt>. Please see below for the
current colour on this system.)</dd>

<dd>However, on some machines, such as T2000s, the lack of a <tt>scadm</tt>
binary makes it impossible for <tt>s-audit.sh</tt> to get the ALOM IP
address. When I was developing s-audit, my site's naming convention was to
have system ALOMs in DNS, using hostnames formed by appending <tt>-lom</tt>
to the system hostname. So, for instance, the ALOM on <tt>cs-db-02</tt>
would be <tt>cs-db-02-lom</tt>. If you use a similar convention, the s-audit
interface will try to &quot;guess&quot; ALOM addresses by appending your
suffix to the hostname of the server it is looking at, then seeing if the
name it has created resolves in DNS.  If it does, then the address to which
it resolves is displayed. You can set your own LOM suffix by changing
the</dd>

<dd>
<pre>
define("ALOM_SFX", "-lom." . STRIP_DOMAIN);
</pre>
</dd>

<dd>line in <tt>_conf/s-audit_config.php</tt>. If you have a standard colour
of cable you use to connect your ALOMs, you can also set this with the</dd>

<dd>
<pre>
"alom" => "#e9a655"             // ALOM cables
</pre>
</dd>

<dd>line in <tt>_conf/nic_colours.php</tt>. The default is orange, because
the site for which s-audit was originally developed connected ALOMs with
orange cable. This system colour codes authoritative and &quot;guessed&quot;
ALOM addresses as follows:</dd>

<dd>Note that old versions of the s-audit client call this field &quot;ALOM
IP&quot;, so you may see both fields in your audits.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["ALOM IP"]);
?>

<dt>storage</dt>
<dd>This field shows all storage to which a host has access. The following
colour-coding is used to highlight different storage types:</dd>

<?php
	echo $dh->colour_key($dh->grid_key["storage"]);
?>

<dd>Disks are grouped by size and bus type, for instance, SCSI, VBOX, IDE
(note that SATA disks are shown as &quot;IDE&quot;), SCSI VHCI, SAS, etc.
Optical disk drives are again grouped by bus type, and those which have
disks loaded or mounted are highlighted by amber and green fields
respectively. The number of disks reported may not be the number of physical
drives, as multipathed devices are typically presented to the system as
multiple drives.</dd>

<dd>Note that tape jukeboxes typically contain multiple drives, and the
drives are reported, not the enclosures.</dd>

<dd>For some optical storage arrays, for instance T3s and 3510s shown on a
dark blue field. If they can be retrieved, the model name and firmware
revision are shown. Note that the disks the array contains will be listed AS
WELL AS the array itself. FC devices which cannot be identified display
their WWN.</dd>

<dt>multipath</dt>

<dd>Displays the type(s) of multipathing used on the system (currently
native mpxio and EMC PowerPath are recognized), and the number of
multipathed devices. PowerPath devices are broken down by type.</dd>

<dt>card</dt>
<dd>Lists PCI and SBUS cards. If the card can be indentifed by consulting
the <tt>card_db</tt> array in the <a href="../extras/defs.php">interface
definitions file</a> then the descriptive name from that file is shown in 
<strong>bold face</strong>, with the name the card uses to identify itself
following in parentheses. If the card's name is not known to the system,
then the name it gave to the auditor is displayed alone. Underneath is
information on the slot the card is in. SBUS card slots are identified by a
number, PCIs are slightly more complicated, but still generally easy to
understand. For instance, <tt>PCI0@33MHz</tt> is PCI slot 0, which runs at
33MHz, whilst +ISER-LEFT/PCI3@188MHz is PCI slot 3 on a more modern
machine.</dd>

<dd>PCI card information can only be reliably retrieved on SPARC systems
running Solaris 10 or later. If you see information for older, or x86
systems, you're lucky.</dd>

<dd>SBUS card auditing is supported on all SBUS equipped SPARC systems.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["storage"]);
?>

<dt>EEPROM</dt>
<dd>Lists selected EEPROM settings. Standard EEPROM parameters such as
<tt>diag-level</tt> are in a blue box, device aliases (devaliases) are in
red.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["EEPROM"]);
?>

<dt>printer</dt>
<dd>Lists any printers to which the host has access. They may or may not
be physically attached. The default printer is denoted.</dd>

<?php
$dh->doc_class_end();
$pg->close_page();

?>
