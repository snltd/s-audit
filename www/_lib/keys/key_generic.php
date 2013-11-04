<?php

//============================================================================
//
// key_generic.php
// ---------------
//
// Generic key data included on every audit type
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$generic_key = array(

	"hostname" => array(
		array("<strong>physical server</strong>/VM", "sa", false),
		array("native zone", "za", false),
	),

	"audit completed" => array(
		array("data &gt; 24h old", "solidamber", false),
		array("data &gt; 1 week old", "solidred", false),
		array("impossible time", "solidorange", false)
	)

);

$eng = array(
	"unk" => "unknown platform",
	"lzone" => "local zone",
	"bzone" => "branded zone",
	"szone" => "sparse root zone",
	"domu" => "XEN domU",
	"dom0" => "XEN dom0",
	"vbox" => "VirtualBox",
	"vmware" => "VMWare",
	"kvm" => "KVM guest",
	"ldmp" => "primary LDOM",
	"ldm" => "guest LDOM");

foreach($this->cols->get_col_list("m_cols") as $vm=>$col) {
	$generic_key["hostname"][] = array($eng[$vm], "k$vm");
}

foreach($this->cols->get_col_list("z_cols") as $vm=>$col) {
	$generic_key["hostname"][] = array($eng[$vm], "k$vm");
}

?>
