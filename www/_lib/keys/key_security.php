<?php

//============================================================================
//
// key_security.php
// -----------------
//
// Key for security audits
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	"user" => array(
		array("username with multiple UIDs", "solidred", false),
		array("UID with multiple usernames", "boxred", false)
	),

	"empty password" => array(
		array("non-root user", "solidamber", false),
		array("root user", "solidred", false)
	),

	"authorized key" => array(
		array("root user", "solidred", false)
	),

	"SSH root" => array(
		array("can SSH as root", "solidred", false),
		array("no data", "solidorange", false)
	),

	"dtlogin" => array(
		array("service running", "solidamber", false),
		array("not running", "boxred", false)
	)
);

?>
