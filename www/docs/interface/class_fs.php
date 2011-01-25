<?php

//============================================================================
//
// class_fs.php
// ------------
//
// Filesystem audit page of s-audit web interface documentation. The main
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

$menu_entry = "filesystem audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry, $generic_key);
$dh->doc_class_start();

?>

	<dt>zpool</dt>
	<dd>This field lists ZFS pools on the system. Only imported pools will
	be reported. The name of the pool is given in <strong>bold
	face</strong>, with its capacity in parentheses. On the next line, the
	version of the zpool is reported. If the system supports a higher
	version than the pool is using, the available version is also given, and
	the cell is coloured.</dd>

	<?php
		echo $dh->colour_key($grid_key["zpool"]);
	?>

	<dt>capacity</dt>
	<dd>Shows, in <strong>bold face</strong>, the total uncompressed storage
	capacity of all local filesystems mounted at the time of the audit. On a
	second line is the amount of data on the system, with the percentage of
	total space used in parentheses. File systems approaching or at full
	capacity are highlighted.</dd>

	<?php
		echo $dh->colour_key($grid_key["capacity"]);
	?>

	<dt>root fs</dt>
	<dd>Shows the type of filesystem used for the system root. This will be
	either UFS or ZFS. If <tt>s-audit.sh</tt> was able to identify a
	mirrored root, then this is reported. It can only do this in the case of
	software mirroring done by the zone being audited, so if a system does
	not report a mirrored root, that does not necessarily mean it does not
	have one. For instance, root maybe mirrored by hardware RAID, or a local
	zone or guest LDOM may be installed on a system mirrored by the global
	zone or primary domain.</dd>

	<?php
		echo $dh->colour_key($grid_key["root fs"]);
	?>

	<dt>fs</dt>
	<dd>This field lists filesystems mounted on the host, with some
	additional information. Each entry has two lines, the first giving the
	mountpoint in <strong>bold face</strong> with the filesystem type in
	parentheses. On the second, indented, line is the device file. For UFS
	filesystems this is the /dev/dsk path, for ZFS it is the dataset name,
	for NFS the remote path. NFS filesystems which are mounted, but do not
	have entries in <tt>/etc/vfstab</tt> are highlighted.</dd>

	<dd>ZFS filesystems have extra information. After the dataset name is
	the version of the filesystem, and after that, if the filesystem is
	compressed, it will say so. If the filesystem can be upgraded to a more
	recent version, the available ZFS version will be given.</dd>

	<dd>Not all filesystems shown in a <tt>df</tt> command are shown.
	Pseudo-filesystems of types <tt>dev</tt>, <tt>devfs</tt>, <tt>ctfs</tt>,
	<tt>mntfs</tt>, <tt>sharefs</tt>, <tt>tmpfs</tt>, <tt>fd</tt>,
	<tt>objfs</tt> and <tt>proc</tt> are ignored.</dd>

	<dd>The following colour coding is used.</dd>

	<?php
		echo $dh->colour_key($grid_key["fs"]);
	?>

	<dt>export</dt>
	<dd>Shows filesystems exported by the host, with security information.
	On the first line, the path of the filesystem is displayed, with the
	export type in parentheses. NFS, SMB and ISCSI exports are understood,
	as well as VDISK devices in logical domians.  For the first two, the
	path is the Unix mountpoint, for ISCSI and VDISK, it is the exported
	device, which may not be mounted. For NFS exports, this line has a third
	field, which tells you how many other machines <emph>known to
	s-audit</tt> were mounting that filesystem when they were last audited.
	Note that &quot;0 known mounts&quot; here does not necessarily mean
	nothing is using that export. Possibly the filesystem is only mounted at
	certain times, or it may only be mounted by machines which have not been
	examined by s-audit. NFS filesystems with no known mounts are
	highlighted by an amber field.</dd>

	<dd>The second line differs for different export types. For NFS it shows
	the export options. For SMB it shows the name of the export. No extra
	information is currently displayed for ISCSI or VDISKs.</dd>

	<dd>The following colour-coding is used.</dd>

	<?php
		echo $dh->colour_key($grid_key["export"]);
	?>


<?php

$dh->doc_class_end();
$pg->close_page();

?>

