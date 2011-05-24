<?php

//============================================================================
//
// s-audit_config.php
// ------------------
//
// Configuration for the s-audit's web interface. Tells the other PHP
// scripts where things are. You shouldn't need to change this. User changes
// are in site_config.php.
// 
// Some things are defined here, rather than hard-coded, simply to help me
// put the official s-audit docs onto snltd.co.uk.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

define("MY_VER", "3.0");
    // Interface software version

define("ROOT", $_SERVER["DOCUMENT_ROOT"]);
	// Site root. Usually document root

define("ROOT_URL", false);
	// top-level URL

define("CONF_DIR", ROOT . "/_conf");
	// config file directory

define("LIB", ROOT . "/_lib");
	// path to _lib/ directory. Lots of things are in there

define("AUDIT_DIR", "/var/snltd/s-audit");
	// Root of audit data

define("SITE_CONFIG", CONF_DIR . "/site_config.php");
	// Path to omitted data file

define("OMITTED_DATA_FILE", CONF_DIR . "/omitted_data.php");
	// Path to omitted data file

define("PER_PAGE", 20);
	// How many servers to show on each page. This refers to GLOBAL zones

define("DOC_URL", ROOT_URL . "/docs");
	// URL to top of document tree

define("CSS_URL", ROOT_URL . "/_css");
	// URL to stylesheets

define("KEY_DIR", LIB . "/keys");
	// Keys for grids. Also used in documentation

define("CB_URL", DOC_URL . "/_files");
	// URL to code blocks and HTMLized code blocks

define("CB_DIR", ROOT . "/docs/_files");
	// Where the codeBlock class finds syntax coloured script files. (Path,
	// not URL.)

define("PKG_DEF_DIR", LIB . "/pkg_defs");
	// Where to find package definition files

define("PCH_DEF_DIR", LIB . "/pch_defs");
	// Where to find patch definition files

define("MAX_AF_VER", 3.0);
define("MIN_AF_VER", 3.0);
	// Maximum and minimum audit file versions we support

define("C_YEAR", "2011");
	// Year for (c) messages

// We always need our basic classes

require_once(LIB . "/display_classes.php");
require_once(LIB . "/colours.php");

// And the site config

require_once(SITE_CONFIG);

// Finally, we need classes which get audit data

require_once(LIB . "/reader_classes.php");

?>
