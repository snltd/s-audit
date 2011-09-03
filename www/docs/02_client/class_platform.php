<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "platform audits";
$pg = new docPage($menu_entry);

?>

<h1>Platform Audits</h1>

<p>Invoked by,</p>

<pre class="cmd">
# s-audit.sh platform
</pre>

<p>this audit type looks at the physical or virtual environment.</p>

<h2>Checks</h2>

<dl>

	<dt>hardware</dt>
	<dd>Tries to get the name of the hardware platform, for example,
	&quot;T2000&quot; or &quot;v210&quot;. It's not always entirely
	accurate, especially on older machines and older versions of Solaris,
	but the <a href="../03_interface">interface</a> is able to more
	accurately describe some machines based on the output of this check.
	x86-based machines are always reported as &quot;x86&quot; Not run in a
	local zone.</dd>
 
	<dt>virtualization</dt>
	<dd>Tries to work out whether the current zone is a physical or virtual
	machine. It can recognize LDOMs, global and local zones, and branded
	zones with complete success. It recognizes VirtualBox and VMWare
	machines perfectly in Solaris 10 and later, but can be tricked by those
	environments on Solaris 8 and 9.</dd>

	<dd>If VMWare tools or VirtualBox guest additions are found in a
	virtualized environment, the version number is displayed.</dd>

	<dt>cpus</dt>
	<dd>Reports the number of physical and virtual processors, their cores,
	and their speed. Note that in an LDOM primary, you cannot see all the
	cores in the box, so this number may appear misleading. In an VirtualBox
	or VMWare machine, the numbers are for processors to which the machine
	has access. They may be for the machine's exclusive use. Not run in a
	local zone.</dd>

	<dt>memory</dt>
	<dd>Gives the physical and virtual memory. Again, be aware that in a
	logically domained machine, the memory is divided between domains, so
	the memory available to the domain will be reported, not the memory of
	the whole machine. In other virtualized environments, you will see the
	memory available to the VM, not necessarily that to which it has
	exclusive use. Not run in a local zone.</dd>

	<dt>sn</dt>
	<dd>Prints the machine's serial number, if it has been stored in the
	EEPROM. Requires Sun's <a href="http://www.sun.com/sneep">Sneep</a> to
	be installed. Not run in a local zone.</dd>

	<dt>obp</dt>
	<dd>Prints the OBP version. This number is largely meaningless on x86
	platforms. Not run in a local zone.</dd>

	<dt>alom</dt>
	<dd>On SPARC hardware, uses the <tt>scadm</tt> binary, if it is
	available, to get the ALOM firmware version. Note that this does not
	work some platforms (e.g. T1000 and T2000), due to a lack of suitable
	<tt>scadm</tt>.  Not run in a local zone and requires root
	privileges.</dd>

	<dt>disks</dt>
	<dd>Prints a list of the disks connected to the machine, be they
	internal, external, or virtual. If possible it will display the
	capacities of the disks (does not work on old x86 versions of Solaris),
	and the type of bus, e.g. SCSI, SAS, VHCI. Both old-style IDE and newer
	SATA disks are displayed as &quot;ATA&quot;, as Solaris is not able to
	distinguish between the two interfaces.</dd>

	<dd>Note that if the system is using hardware RAID, the number of disks
	presented to the operating system will be less than the physical number
	of disks connected. However, if hardware RAID is in use, a separate line
	of output will list the number of RAID volumes.</dd>
	
	<dd>This test is omitted in local zones.</dd>

	<dt>optical</dt>
	<dd>Gives a list of all the optical drives (i.e. CD and DVD) it can
	find, along with their bus type (SCSI, IDE, USB). If there is a disk
	inside, reports &quot;loaded&quot; or &quot;mounted&quot; depending on
	the state.  Omitted in local zones.</dd>

	<dt>lux_enclosures</dt>
	<dd>Lists fibre-channel enclosures. Tries to get the vendor's name and
	the firmware version. This isn't broadly tested, but it is known to work
	correctly with the T3 and 3510 arrays. Omitted in local zones and
	requires root privileges.</dd>

	<dt>tape_drives</dt>
	<dd>Displays, where possible, the vendor name and model number of SCSI
	tape drives. Note that some libraries and jukeboxes contain multiple
	drives, and each of those drives will be counted, not the enclosure. For
	instance, a Sun L25 shows up as &quot;2 x Quantum DLT&quot;. Old
	versions of Solaris are able to report less information. Omitted in
	local zones.</dd>

	<dt>pci_cards</dt>
	<dd>This reports information on PCI cards. It only really works on SPARC
	Solaris 9 and 10.  Consider it a bonus on those platforms and forget
	about it on everything else. Omitted in local zones.  The information is
	fairly human-readable, with the type of card given first, then the
	model, usually followed by the PCI slot and the bus speed. Omitted in
	local zones.</dd>

	<dt>sbus</dt>
	<dd>Gives some information on SBUS cards. Obviously only works on SPARC
	hardware. Omitted in local zones.</dd>
</dl>

<?php

$pg->close_page();

?>

