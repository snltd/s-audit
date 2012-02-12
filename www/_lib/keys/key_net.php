<?php

//============================================================================
//
// key_net.php
// -----------
//
// Key for networking audits
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	"NTP" => array(
		array("preferred NTP server", "solidgreen", false),
		array("acting as NTP server", "solidorange", false),
		array("not running", "boxred", false)
	),

	"name server" => array(
		array("primary server", "solidgreen", false),
		array("slave server", "solidamber", false)
	),

	"port" => array(
		array("unexpected port", "solidamber", false),
		array("inetd controlled", "boxred", false)
	),

	"route" => array(
		array("not in /etc/defaultrouter", "solidamber", false),
		array("persistent route", "boxgreen", false)
	)

);

// Add a note about high numbered ports, if they're being omitted

if (defined("OMIT_PORT_THRESHOLD"))
	$grid_key["port"][] = array("NOTE: open ports above " .
	OMIT_PORT_THRESHOLD . " are not being displayed.", false, false);

// Generate the NIC key: Do we have a subnet_cols array?

if (defined(SUBNET_COLS) && isset($this->cols->subnet_cols)) {

	foreach($this->cols->get_col_list("subnet_cols") as $net=>$col) {
		$class = "net$net";

    	if ($net == "alom" || $net == "vlan")
        	continue;
    	elseif ($net == "unconfigured")
        	$txt = "unconfigured or VLANned interface";
    	elseif ($net == "vswitch")
        	$txt = "virtual switch";
    	elseif (preg_match("/^\d{1,3}.\d{1,3}.\d{1,3}$/", $net)) {
        	$txt = "${net}.0 network";
			$class = "net" . preg_replace("/\./", "", $net);
		}
		else
			$txt = $net;
	
    	$grid_key["net"][] = array($txt, $class);
	}

}
else {

	$grid_key["net"] = array(
		
		array("physical NIC", "boxnetphys", false),
		array("virtual NIC", "boxnetvirtual", false),
		array("VNIC", "boxnetvnic", false),
		array("vswitch", "boxnetvswitch", false),
		array("etherstub", "boxnetetherstub", false),
		array("aggregate", "boxnetaggr", false),
		array("Sun Cluster private", "boxnetclprivnet"),
		array("VCS LLT link", "boxnetLLT"),
		array("member of IPMP group", "solidgreen", false),
		array("DHCP assigned address", "solidamber", false)
	
	);

}

?>
