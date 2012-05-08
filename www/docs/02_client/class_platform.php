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

	<dt>CPUs</dt>
	<dd>Reports the number of physical and virtual processors, their cores,
	and their speed. Note that in an LDOM primary, you cannot see all the
	cores in the box, so this number may appear misleading. In a VirtualBox
	or VMWare machine, the numbers are for processors to which the machine
	has access. They may or may not be for the machine's exclusive use. Not
	run in a local zone.</dd>

	<dt>memory</dt>
	<dd>Gives the physical and virtual memory. Again, be aware that in a
	logically domained machine, the memory is divided between domains, so
	the memory available to the domain will be reported, not the memory of
	the whole machine. In other virtualized environments, you will see the
	memory available to the VM, not necessarily that to which it has
	exclusive use. Not run in a local zone.</dd>

	<dt>serial number</dt>
	<dd>Prints the machine's serial number, if it has been stored in the
	EEPROM. Requires Sun's <a href="http://www.sun.com/sneep">Sneep</a> to
	be installed. Not run in a local zone.</dd>

	<dt>OBP</dt>
	<dd>Prints the OBP version. This number is largely meaningless on x86
	platforms. Not run in a local zone.</dd>

	<dt>ALOM f/w</dt>
	<dd>On SPARC hardware, uses the <tt>scadm</tt> binary, if it is
	available, to get the ALOM firmware version. Note that this does not
	work some platforms (e.g. T1000 and T2000), due to a lack of suitable
	<tt>scadm</tt>.  Not run in a local zone and requires root
	privileges.</dd>

	<dt>ALOM IP</dt>
	<dd>Gets the IP adress of the ALOM on SPARC platforms. The same
	restrictions apply as for the ALOM f/w check.</dd>

	<dt>storage</dt>
	<dd> The storage runs checks for disks, optical devices, fibre-channel
	arrays, hardware RAID devices, and tape drives.</dd>

	<dd>The disks check prints a list of the disks connected to the machine,
	be they internal, external, or virtual. If possible it will display the
	capacities of the disks (does not work on old x86 versions of Solaris),
	and the type of bus, e.g. SCSI, SAS, VHCI etc. Both old-style IDE and
	newer SATA disks are displayed as &quot;ATA&quot;, as Solaris is not
	able to distinguish between the two interfaces.</dd>

	<dd>Note that if the system is using hardware RAID, the number of disks
	presented to the operating system will be less than the physical number
	of disks connected. However, if hardware RAID is in use, a separate line
	of output will list the number of RAID volumes. Also be aware that if an
	FC array is found, not only will the enclosure itself be reported, but
	the logical devices it presents will also be reported as disks.</dd>
	
	<dd>Once disks have been handled the storage check reports on  all the
	optical drives (i.e. CD and DVD) it can find, along with their bus type
	(SCSI, IDE, USB). If there is a disk inside, reports &quot;loaded&quot;
	or &quot;mounted&quot; depending on the state.</dd>

	<dd>Next fibre-channel enclosures are displayed. The check tries to get
	the vendor's name and the firmware version. This isn't broadly tested,
	but it is known to work correctly with the T3 and 3510 arrays. Because
	of restrictions in <tt>luxadm</tt>, this check requires root
	privileges.</dd>

	<dd>SCSI tape drives are displayed, where possible, with the vendor name
	and model number.  Note that some libraries and jukeboxes contain
	multiple drives, and each of those drives will be counted, not the
	enclosure. For instance, a Sun L25 shows up as &quot;2 x Quantum
	DLT&quot;. Old versions of Solaris are able to report less information.
	</dd>

	<dd>Storage checks are omitted in local zones.</dd>

	<dt>multipath</dt>
	<dd>Currently s-audit is able to understand two kinds of multipathing.
	First, the Solaris 10 native <tt>mpxio</tt> variety, managed via the
	<tt>mpathadm</tt> tool. If this is running, the number of multipathed
	devices (that is, devices with a number of paths greater than 1) is
	reported. EMC PowerPath is also understood, and the number of
	multipathed devices will be reported.</dd>

	<dt>card</dt>
	<dd>This reports information on PCI  and SBUS cards. It only really
	works on SPARC Solaris 9 and 10.  Consider it a bonus on those platforms
	and forget about it on everything else.</dd>

	<dd>For PCI cards, the type of card is displayed, followed by the card's
	identifying string, the PCI slot it occupies, and the bus speed, all in
	parentheses. If the card supplies a model name, that is shown last.</dd>

	<dd>SBUS cards display the card's identifying string, followed by the
	SBUS slot in parentheses.</dd>

	<dd>This test is omitted in local zones.</dd>
	
	<dt>printers</dt>
	<dd>This test lists printers to which the machine has access. Default
	printers are denoted.</dd>

</dl>

<?php

$pg->close_page();

?>

