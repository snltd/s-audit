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
	but the <a href="../interface">interface</a> is able to more accurately
	describe some machines based on the output of this check.  x86-based
	machines are always reported as &quot;x86&quot; Not run in a local
	zone.</dd>
 
	<dt>virtualization</dt>
	<dd>Tries to work out whether the current zone is a physical or virtual
	machine. It can recognize LDOMs, global and local zones, and branded
	zones with complete success. It recognizes VirtualBox and VMWare
	machines perfectly in Solaris 10 and later, but can be tricked by those
	environments on Solaris 8 and 9.</dd>

	<dt>cpus</dt>
	<dd>Reports the number of physical and virtual processors, their cores,
	and their speed. Note that in an LDOM primary, you cannot see all the
	cores in the box, so this number may appear misleading. Not run in a
	local zone.</dd>

	<dt>memory</dt>
	<dd>Gives the physical and virtual memory. Again, be aware that in a
	logically domained machine, the memory is divided between domains, so
	the memory available to the domain will be reported, not the memory of
	the whole machine. Not run in a local zone.</dd>

	<dt>sn</dt>
	<dd>Prints the machine's serial number, if it has been stored in the
	EEPROM. Requires Sun's <a href="http://www.sun.com/sneep">Sneep</a> to
	be installed. Not run in a local zone.</dd>

	<dt>obp</dt>
	<dd>Prints the OBP version. Not run in a local zone.</dd>

	<dt>alom</dt>
	<dd>On SPARC hardware, uses the <tt>scadm</tt> binary, if it is
	available, to get the ALOM firmware version. Note that this does not
	work on T1000s and T2000s, due to a lack of suitable <tt>scadm</tt>.
	Not run in a local zone and requires root privileges.</dd>

	<dt>disks</dt>
	<dd>Prints a list of the disks connected to the machine, be they
	internal, external, or virtual. If possible it will display the
	capacities of the disks (does not work on old x86 versions of Solaris),
	and the type of bus, e.g. SCSI, SAS, VHCI. Omitted in local zones.</dd>

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
	instance, a Sun L25 shows up as 2 x Quantum DLT. Old versions of Solaris
	are able to report less information. Omitted in local zones.</dd>

	<dt>pci_cards</dt>
	<dd>This reports generally human-readable information on PCI cards. It
	only really works on SPARC Solaris 10.  Consider it a bonus on that
	platform and forget about it on everything else. Omitted in local zones.
	The information is fairly human-readable, with the type of card given
	first, then the model, usually followed by the PCI slot and the bus
	speed. Omitted in local zones.</dd>

	<dt>sbus</dt>
	<dd>Gives some information on SBUS cards. Obviously only works on SPARC
	hardware. Omitted in local zones.</dd>

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

</dl>

<?php

$pg->close_page();

?>

