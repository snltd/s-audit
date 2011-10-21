<?php

//============================================================================
//
// key_os.php
// ----------
//
// Key for O/S audits
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	"version" => array(
		array("different version<br/>in local zone", "boxamber", false)
	),

	"release" => array(
		array("different release<br/>in local zone", "boxamber", false)
	),

	"kernel" => array(
		array("latest installed kernel", "ver_l", false),
		array("older kernel", "ver_o", false),
		array("different kernel<br/>in local zone", "boxamber", false)
	),

	"hostid" => array(
		array("different hostid<br/>in local zone", "boxamber", false)
	),

	"VM" => array(
		array("running", "solidgreen", false),
		array("installed/halted", "solidamber", false),
		array("errored/incomplete", "solidred", false),
	),

	"uptime" => array(
		array("rebooted today", "solidamber", false),
		array("local zone rebooted<br/>after global zone", "boxamber", false)
	),

	"boot env" => array(
		array("active now", "boxgreen", false),
		array("active on reboot", "boxred", false),
		array("incomplete environment", "solidamber")
	),

	"SMF services" => array(
		array("services in maintenence mode", "solidred", false)
	),

	"packages" => array(
		array("partially installed<br/>packages", "solidamber", false)
	),

	"publisher" => array(
		array("preferred publisher", "boxgreen", false)
	),

	"patches" => array(
		array("in local zone, possible<br/>missing patches", "solidamber",
		false)
	)

);

# Generate the VM column automatically

$eng = array(
		"unk" => "unknown",
        "lzone" => "local zone",
        "bzone" => "branded zone",
        "domu" => "XEN dom0",
        "dom0" => "XEN dom0",
        "vbox" => "VirtualBox",
        "vmware" => "VMWare",
        "ldmp" => "primary LDOM",
        "ldm" => "guest LDOM");


foreach($this->cols->get_col_list("vm_cols") as $vm=>$col) {
	if ($vm == "phys") continue;
	$grid_key["VM"][] = array($eng[$vm], $vm);
}


?>
