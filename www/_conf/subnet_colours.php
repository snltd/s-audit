<?php

//============================================================================
//
// subnet_colours.php
// ------------------
//
// This file lets you define colours for various subnets on the platform
// audit page. So, if you use different coloured cables for different
// networks, the audit page can match your scheme.
//
// This file is referenced by _lib/colours.php.
// 
// The array is of the form 
//
// "x.y.z" => "#abcdef"
//
// where x.y.z is the first three octets of the subnet (for instance 10.10.8
// for 10.10.8.0) and #abcdef is a standard HTML hex colour.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

// This array tells us how to colour NICs. The subnets and colours are
// site-specific, so you will most likely want to change them.

$subnet_cols = array(
	"10.0.2" => "#62A189",			// aqua (Windows VirtualBox internal net)
	"192.168.56" => "#59A6C2",		// VirtualBox
	"172.16.0" => "#68BDDC",		// Sun Cluster private link
	"172.16.1" => "#7277DC",		// Sun Cluster private link
	"172.16.193" => "#3A7ADC",		// Sun Cluster clprivnet
	"192.168.1" => "#C598C1",		// purple
	"10.10.8" => "#967e4a",			// brown
	"10.10.7" => "#61ab52",			// green
	"10.10.4" => "#f1ef5c",			// yellow
	"10.10.6" => "#d5554c",			// red

//-- Do not remove entries below here. You can change the colours ------------

	"alom" => "#e9a655",			// ALOM cables
	"unconfigured" => "#a8a8a8",	// Unconfigured, but plumbed interfaces
	"vlan" => "#a8a8a8",			// VLANned interfaces
	"vswitch" => "#7a89bb",			// Virtual Switches
	"etherstub" => "#6D8C73"		// etherstubs
);

?>
