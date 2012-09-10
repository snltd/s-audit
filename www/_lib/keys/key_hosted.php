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

	"AI service" => array(
		array("SPARC service", false, $this->cols->icol("box", "sparc",
		"ai_cols")),
		 array("x86 service", false, $this->cols->icol("box", "x86",
		 "ai_cols"))
	),

	"AI client" => array(
		array("SPARC client", false, $this->cols->icol("box", "sparc",
		"ai_cols")),
		array("x86 client", false, $this->cols->icol("box", "x86", "ai_cols"))
	)


);

// Generate the rest of the db and webserver keys automatically

foreach($this->cols->get_col_list("db_cols") as $db=>$col) {
	$grid_key["database"][] = array("$db database", false,
	$this->cols->icol("box", "$db", "db_cols"));
}

foreach($this->cols->get_col_list("ws_cols") as $ws=>$col) {
	$grid_key["website"][] = array("$ws site", false,
	$this->cols->icol("box", $ws, "ws_cols"));
}

// Add this to the key if we're in a doc page (in which case there's no map)
// or if we're in an audit page and have the uri map

if (!isset($this->map) || file_exists($this->map->get_path("uri_map"))) {
	$grid_key["website"][] = array("site resolves", "strongg", false);
	$grid_key["website"][] = array("site does not resolve", "strongr", false);
}
	
?>
