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

	"capacity" => array(
		array("&gt;85% of capacity used", "solidamber", false),
		array("&gt;90% of capacity used", "solidred", false)
	),

	"root fs" => array(
		array("UFS root", "ufs", false),
		array("ZFS root", "zfs", false)
	),

	"fs" => array(
		array("ZFS dataset can be upgraded", "solidorange", false),
		array("NFS mount not in vfstab", "solidred", false),
		array("read-only filesystem", "solidgrey", false),
	),

	"export" => array(
		array("NFS filesystem with no known mounts", "solidamber", false)
	)

);

// Automatically populate the fs and export fields.

foreach (colours::$fs_cols as $f=>$col) {

	// most fstyps only make sense in one context

	$txt = strtoupper($f);

	if ($f == "ufs" || $f == "zfs" || $f == "hsfs" || $f == "zfs" || $f ==
	"lofs" || $f == "vboxfs" || $f == "smbfs" || $f == "nfs")
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
