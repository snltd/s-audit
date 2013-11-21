<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "filesystem audits";
$pg = new docPage($menu_entry);

?>

<h1>Filesystem Audits</h1>

<p>Invoked by</p>

<pre class="cmd">
# s-audit.sh fs
</pre>

<p>this audit type looks at filesytems and the data they hold.</p>

<h3>Checks</h3>

<dl>

	<dt>zpool</dt>
	<dd>Lists the names of all imported zpools along with their capacity,
    their version, their state, and on recent versions of Solaris 10 and
    11, the highest zpool version supported on the machine. The number of
    devices reported includes any devices assigneed to log and/or cache. If
    a pool has a log or cache, it will be stated. Not run in local
     zones.</dd>

	<dt>disk group</dt>
	<dd>Lists Veritas Volume Manager disk groups. The name of the group is
	given first, followed by its status in parentheses, followed by the
	number of disks and the number of volumes which belong to the
	group.</dd>

	<dt>metaset</dt>
	<dd>Lists DiskSuite/SVM metaset names. No other information is currently
	retrieved.</dd>

	<dt>capacity</dt>
	<dd>Adds up the available and used space on all local filesystems and
	reports it as numbers and a percentage used. Omitted in local
	zones.</dd>

	<dt>root_fs</dt>
	<dd>Reports the type of the root filesystem, which may be UFS or ZFS. In
	a global zone, also reports if that filesystem is mirrored. Note that it
	is generally impossible for a virtualized O/S to know that it is running
	on a mirrored root. Hardware RAID is not understood by this function,
	only SVM or ZFS mirroring.</dd>

	<dt>fs</dt>
	<dd>Produces a list of all filesystems known to the zone. Left to right,
	it gives the mountpoint, the fstyp (e.g. zfs), special options (e.g. ro
	for read-only) and the device (e.g. <tt>/dev/dsk/c0t0d0s0</tt> or
	<tt>tank/mydata</tt>.)</dd>

	<dd>ZFS datasets then report the version of the filesystem / the highest
	version supported on the box (e.g. 3/4), and "comp" if the filesystem is
	compressed. (This is not fully supported in very early ZFS releases.)
	NFS mounts report the mountpoint and the server:/path of the source
	fs.</dd>

	<dt>exports</dt>
	<dd>Produces a list of all exported filesystems. The information begins
	with the method of filesystem export (e.g. NFS, SMB, iSCSI, or LDOM
	VDISK), the path of the exported directory or device, and any options,
	e.g. <tt>sec=sys,rw=@10.10,root=@10.10</tt>.</dd>

</dl>

<?php

$pg->close_page();

?>

