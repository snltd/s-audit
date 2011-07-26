<?php

//============================================================================
//
// key_fs.php
// ----------
//
// Key data for filesystem audits.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	"zpool" => array(
		array("faulted zpool", "solidred", false),
		array("degraded zpool", "solidamber", false),
		array("online zpool can be<br/>upgraded", "solidorange", false)
	),

	"disk group" => array(
		array("disabled group", "solidred", false),
		array("warning state", "solidamber", false)
	),

	"capacity" => array(
		array("&gt;90% of capacity used", "solidred", false),
		array("&gt;85% of capacity used", "solidamber", false)
	),

	"root fs" => array(
		array("UFS root", "ufs", false),
		array("ZFS root", "zfs", false)
	),

	"fs" => array(
		array("&gt;90% of capacity used", "solidred", false),
		array("&gt;85% of capacity used", "solidamber", false),
		array("mount not in vfstab", "solidpink"),
		array("read-only filesystem", "solidgrey", false),
		array("ZFS dataset can be upgraded", "zfs",
		$this->cols->icol("solid", "orange"))
	),

	"export" => array(
		array("NFS filesystem with no known mounts", "nfs",
		$this->cols->icol("solid", "amber")),
		array("unassigned VDISK", "vdisk", $this->cols->icol("solid",
		"amber"))
	)

);

// Automatically populate the fs and export fields.

foreach ($this->cols->get_col_list("fs_cols") as $f=>$col) {

	// most fstyps only make sense in one context

	$txt = strtoupper($f);

	if ($f == "ufs" || $f == "zfs" || $f == "hsfs" || $f == "zfs" || $f ==
	"lofs" || $f == "vxfs" || $f == "vboxfs" || $f == "smbfs" || $f == "nfs")
		$grid_key["fs"][] = array("$txt filesystem", $f, false);
	else
		$grid_key["export"][] = array("$txt export", $f,false);
	
	// NFS is in both

	if ($f == "nfs")
		$grid_key["export"][] = array("$txt export", $f, false);

}

if (defined("STRIP_DOMAIN"))
	$grid_key["export"][] = array("NOTE: The domain name &quot;" .
	STRIP_DOMAIN .  "&quot; has been removed<br/>from hostnames for
	legibility.", false, false);

?>
