<?php

//============================================================================
//
// site_config.php
// ---------------
//
// User-definable constants for the s-audit web interface. Change these to
// suit your site.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

define("SITE_NAME", "development");
	// Site name, displayed on all page titles

define("STRIP_DOMAIN", "localnet");
	// If this is defined, the domain name will be stripped off hostnames on
	// the IP listing page and NFS shares on the security page. (Assuming
	// they are fully qualified.) This is also usually tagged on to the
	// ALOM_SFX definition (see below).

define("ALOM_SFX", "-lom." . STRIP_DOMAIN);
	// When we try to guess ALOM IP addresses, we tag this on to the end of
	// the hostname, then do a DNS lookup. If this isn't defined, then the
	// "guessing" is not done

define("SHOW_SERVER_INFO", true);
	// Whether or not to expose server information in the footer

define("OMIT_PORT_THRESHOLD", 5000);
	// If this is defined, open ports above this number will not be
	// displayed

define("OMIT_STANDARD_USERS", true);
	// If this is defined, standard Solaris users are not shown on the
	// security audit page

define("OMIT_STANDARD_CRON", true);
	// If this is defined, standard cron jobs are not shown on the security
	// audit page

define("OMIT_STANDARD_ATTRS", true);
	// If this is defined, standard user_attrs are not shown on the security
	// audit page

define("SS_HOST_COLS", 6);
    // How many columns of local zones on the single server view page

define("LOWEST_T", 1293840000);
	// Assuming server clocks are correct, the earliest possible time at
	// which an audit could have been performed, in seconds since the epoch.
	// This should help you catch hosts with way-off clocks. Default is
	// 00:00:00 01/01/2011

?>
