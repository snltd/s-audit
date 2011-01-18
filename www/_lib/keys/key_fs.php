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
		array("zpool can be<br/>upgraded", "solidorange", false)
	),

	"capacity" => array(
		array("&gt;85% of capacity used", "solidamber", false),
		array("&gt;90% of capacity used", "solidred", false)
	),

	"root fs" => array(
		array("UFS root", "boxufs", false),
		array("ZFS root", "boxzfs", false)
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
		$grid_key["fs"][] = array("$txt filesystem", false,
		inline_col::box($col));
	else
		$grid_key["export"][] = array("$txt export", false,
		inline_col::box($col));
	
	// NFS is in both

	if ($f == "nfs")
		$grid_key["export"][] = array("$txt export", false,
		inline_col::box($col));

}

$grid_notes = array("exports" => "the <tt>" . STRIP_DOMAIN . "</tt>  domain
name has been removed from hostnames for legibility. Remember that hostnames
in NFS share options (or ZFS <tt>sharenfs</tt> settings) must be fully
qualified.");

?>
