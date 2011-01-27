<?php

//============================================================================
//
// key_hosted.php
// --------------
//
// Key for hosted services audits. Used on the audit page and in the
// documentation
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	"website" => array(
		array("server/vhost name or alias", false, "font-weight: bold"),
		array("doc_root is an NFS mount", "solidred", false),
		array("doc_root contains NFS mounts", "solidamber", false),
		array("config file not named *.conf", "solidamber", false)
	),

	"database" => array(
		array("database not updated in last month", "solidamber", false),
	),

);

// Generate the rest of the db and webserver keys automatically

foreach(colours::$db_cols as $db=>$col) {
	$grid_key["database"][] = array("$db database", false,
	inlineCol::box($col));
}

foreach(colours::$ws_cols as $ws=>$col) {
	$grid_key["website"][] = array("$ws site", false, inlineCol::box($col));
}


if (file_exists(URI_MAP_FILE) || preg_match("/docs/",
$_SERVER["PHP_SELF"])) {
	$grid_key["website"][] = array("site resolves", "strongg", false);
	$grid_key["website"][] = array("site does not resolve", "strongr", false);
}
	
?>
