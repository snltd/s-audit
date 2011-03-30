<?php

//============================================================================
//
// defs.php
// --------
//
// This file holds "translations" of internal version numbers and code names
// to more human-readable descriptions.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

//----------------------------------------------------------------------------

class defs {
	
	// The names machines identify themselves as, mapped to the names we
	// call them

	private $hw_db = array(
		"Sun Fire T200" => "Sun T2000"
	);

	// Solaris releases by date, converted to update number and/or hardware
	// release

	private $updates = array(

			"5.6" => array(
				"s297s" => "Initial Release",
				"HW:2" => "3/98 (h/w 2)"
				// Others are named as-per /etc/release
			),

			"5.7" => array(
				"s998" => "Initial Release"

				// All the others are just called 5/99, 8/99 etc, which is
				// what comes out of /etc/release

			),

			"5.8" => array(
				"6/00" => "update 1",
				"10/00" => "update 2",
				"1/01" => "update 3",
				"4/01" => "update 4",
				"7/01" => "update 5",
				"10/01" => "update 6",
				"2/02" => "update 7",
				"12/02" => "HW1",
				"5/03" => "HW2",
				"7/03" => "HW3",
				"2/04" => "HW4",
				"s_28" => "Maintenance update 1"
				),

			"5.9" => array(
				"9/02" => "update 1",
				"12/02" => "update 2",
				"4/03" => "update 3",
				"8/03" => "update 4",
				"12/03" => "update 5",
				"4/04" => "update 6",
				"9/04" => "update 7",
				"9/05" => "update 8",
				"9/05 HW Update" => "u9/HW update"
				),

			"5.10" => array(
				"03/05" => "GA",
				"01/06" => "update 1",
				"06/06" => "update 2",
				"11/06" => "update 3",
				"8/07" => "update 4",
				"5/08" => "update 5",
				"10/08" => "update 6",
				"5/09" => "update 7",
				"10/09" => "update 8",
				"9/10" => "update 9"
				)
            );

	// Sun Studio's -V output mapped to marketing release number

	private $sun_cc_vers = array(
			"5.0" => "5.0",
			"5.8" => "11",
			"5.9" => "12",
			"5.10" => "12u1",
			"5.11" => "12.2"
			);


	// Expansion cards
	
	private $card_db =  array(

			"sbus" => array(
				"SUNW,qfe" => "Sun Quad Fast Ethernet",
				"SUNW,socal/sf" => "Sun differential SCSI",
				"QLGC,isp/sd" => "QLogic FCAL HBA"
			),

			"pci" => array(
				"QLA2342" => "QLogic FCAL HBA",
				"SUNW,pci-qfe" => "Sun Quad Fast Ethernet",
				"LSI,1030" => "LSI diffrential SCSI"
			)
		);

	public function get_data($type)
	{
		return $this->$type;
	}

}
