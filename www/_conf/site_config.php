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
	// they are fully qualified.)

define("ALOM_SFX", "-lom." . STRIP_DOMAIN);
	// When we try to guess ALOM IP addresses, we tag this on to the end of
	// the hostname, then do a DNS lookup. If this isn't defined, then the
	// "guessing" is not done

define("SHOW_SERVER_INFO", true);
	// Whether or not to expose server information in the footer



?>
