<?php

//============================================================================
//
// pci_classes.php
// ---------------
//
// An abstract class and a bunch of extensions which parse raw prtdiag(1)
// PCI data. Required by platform audits.
//
// Part of s-audit. (c) 2011-2012 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

abstract class pci_parser {

	// A template for parsing PCI card info from prtdiag
	// [bus]      the bus type - sbus/pci/pcie/pci-x
	// [c_type]   the card type. Normally this is something generic
	//            like "network", but with legacy data, can also be
	//            the model name.
	// [c_model]  the model name of the card. Often not available
	// [c_loc]    the slot the card is in Sometimes has the
	//            backplane or side
	// [c_name]   the name prtdiag gives the card. e.g. SUNW,pci-ce
	// [c_hz]     the speed the card is running at, in MHz

	protected $row;
		// The raw row of data we are given

	protected $a;
		// $row exploded on whitespace
	
	protected $ret;
		// the array we return via the get_info method

	protected $debug = false;
		// Set this to true and you'll get the input strings at the top of 
		// the page

	// The following variables point to the array element holding the data
	// we're interested in. If the element needs further processing,
	// overload the method that gets it.

	protected $bus_field = 0;
	protected $c_model_field;
	protected $c_hz_field;
	protected $c_name_field;
	protected $c_type_field;
	protected $c_loc_field;

	protected $ignore_str;
		// A string used by preg_match in the ignore() method to screen out
		// unwanted information. Don't put the leading and trailing slashes
		// in

	public function __construct($row)
	{

		// Are we interested in this row?

		if($this->ignore($row))
			return false;

		// Populate variables

		$this->row = preg_replace("/^\S+ \((.*)\)$/", "$1", $row);
		$this->a =  preg_split("/\s+/", $this->row);

		if ($this->debug) pr($this->a);

		// Call methods to populate more variables

		$this->ret["bus"] = $this->get_bus();
		$this->ret["c_type"] = $this->get_c_type();
		$this->ret["c_model"] = $this->get_c_model();
		$this->ret["c_loc"] = $this->get_c_loc();
		$this->ret["c_name"] = $this->get_c_name();
		$this->ret["c_hz"] = $this->get_c_hz();

		// If the name and the model are the same, forget the name

		if ($this->ret["c_model"] == $this->ret["c_name"])
			unset($this->ret["c_name"]);

	}

	// These are all broken out into methods so they can be easily
	// overridden if you need to do something more complicated than just
	// pick an element out of an array

	protected function get_bus()
	{
		// Get the bus type: e.g. PCI

		return $this->a[$this->bus_field];
	}

	protected function get_c_type()
	{
		// Get the card type: e.g. "network"

		return (isset($this->a[$this->c_type_field]))
			? preg_replace("/-pci.*$/", "", $this->a[$this->c_type_field])
			: false;
	}

	protected function get_c_loc()
	{
		// Get the slot (and possibly the side) the card is in

		return $this->a[$this->c_loc_field];
	}

	protected function get_c_model()
	{
		// get the card model name. Often you only have it for certain
		// cards, so check the field exists

		// Sometimes we have to give a choice of fields through the
		// model_field1 and 2 variables

		if (isset($this->c_model_field1)) {

			if (isset($this->a[$this->c_model_field2]))
				$ret = $this->a[$this->c_model_field2];
			elseif (isset($this->a[$this->c_model_field1]))
				$ret = $this->a[$this->c_model_field1];
			else
				$ret = false;
		}
		else {

			$ret = (isset($this->a[$this->c_model_field]))
				? $this->a[$this->c_model_field]
				: false;
		}

		return $ret;
	}

	protected function get_c_name()
	{
		// Remove the hex from the end of the name

		return (isset($this->a[$this->c_name_field]))
			? preg_replace("/,[abcdef\d]+$/", "",
				$this->a[$this->c_name_field])
			: false;
	}

	protected function get_c_hz()
	{
		// Not every machine reports speed (T2000 for instance)

		return (isset($this->c_hz_field))
			? $this->a[$this->c_hz_field]
			: false;
	}

	protected function ignore($data)
	{
		// Use a regex to filter out things like PCI bridges and controllers
		// that are on the main board or riser. If $ignore_str isn't set,
		// let everything pass through

		$ret = false;

		if (isset($this->ignore_str)) {
			
			if (preg_match("/$this->ignore_str/", $data)) {
				$ret = true;
			}

		}

		return $ret;
	}

	public function get_info()
	{
		// Return the information
		//pr($this->ret);
		return $this->ret;
	}

}

//============================================================================
//
// Every machine we come across needs a class which extends pci_parser. Some
// are the same, but there are a lot of subtle (and not so subtle)
// differences. Few of these are perfect, as PCI card information is often
// incomplete, and it's sometimes hard to screen out things which are part
// of the machine itself. Still, I hope they all provide information which
// is useful, and not confusing.

//- x86 machines -------------------------------------------------------------

// With Solaris 11, these can produce info, but it's not particularly
// useful. It just tells you what slots the machine has. For now, I'm just
// going to filter all the "available"s out.

class pci_i386 extends pci_parser {
	protected $ignore_str = "in use|available";
}

//- old SunFire machines -----------------------------------------------------

class pci_sunfirev240 extends pci_parser {

// This is nasty - it comes on two rows
// bus type | MHz | Slot   | Name | model
// 0        | 1   | 2      | 3    | 4
//                | status | path
//                  0      | 1 

	protected $bus_type = 0;
	protected $c_hz_field = 1;
	protected $c_loc_field = 2;
	protected $c_name_field = 3;
	protected $c_model_field1 = 4;
	protected $c_model_field2 = 5;

	protected $ignore_str = "pci@|MB|rmc-comm|\(usb\)";

}

class pci_sunfirev440 extends pci_sunfirev240 {

	// Same as the v240
}

class pci_sunfirev210 extends pci_sunfirev240 {

	// Same as the v240
}

class pci_sunfirev490 extends pci_parser {

// io_typ | portID | side | slot | hz | max_hz |d,f | state | name | model
//  0     | 1      | 2    | 3    | 4  | 5      | 6  |  7    | 8    | 9
	
	protected $c_hz_field = 4;
	protected $c_type_field = 8;
	protected $c_name_field = 9;
	protected $c_slot_field = 3;
	protected $c_side_field = 2;
	protected $c_model_field1 = 9;
	protected $c_model_field2 = 10;
	protected $ignore_str = "PCI-BRIDGE";

	protected function get_c_loc()
	{
		return "side " . $this->a[$this->c_side_field] . "/slot " .
		$this->a[$this->c_slot_field];
	}

}

class pci_sunfire880 extends pci_sunfirev490 {

// Almost the same as the v490

// brd | type | Port| Side | Slot | hz | max_hz | d,f | state | name | model
// 0   | 1    | 2   | 3    | 4    | 5  | 6      | 7   | 8     | 9    | 10

	protected $bus_field = 1;
	protected $c_type_field = 9;
	protected $c_hz_field = 5;
	protected $c_model_field1 = 10;
	protected $c_model_field2 = 11;
	protected $c_slot_field = 4;
	protected $c_side_field = 3;
	protected $ignore_str = "usb-";
}

class pci_sunfiree25k extends pci_sunfirev490 {

// slot | type | Port | side | hz | max_hz | d,f | state | name | model
// 0    | 1    | 2    | 3    | 4  | 5      | 6   | 7     | 8    | 9

	protected $bus_field = 1;
	protected $c_side_field = 3;
	protected $c_slot_field = 0;
	protected $c_hz_field = 4;
	protected $c_model_field = 9;
	protected $c_name_field = 8;

	protected $ignore_str = "pci-bri|bootb|firewir|usb-|scsi-pci1000|pci108e";


}

//- T-series -----------------------------------------------------------------

class pci_sunfiret200 extends pci_parser {

// Location | Type | Slot | Path | Name | Model
// 0        | 1    | 2    | 3    | 4    | 5

	protected $bus_field = 1;
	protected $c_name_field = 4;
	protected $c_type_field = 4;
	protected $c_model_field = 5;
	protected $c_loc_field = 0;
	protected $ignore_str = "IOBD";
}

class pci_sparct32 extends pci_parser {

// Slot | Bus Type | Name | Model | Status | Type | Path
// 0    | 1        | 2    | 3     | 4      | 5    | 6

	protected $bus_field = 1;
	protected $c_name_field = 2;
	protected $c_type_field = 5;
	protected $c_model_field = 3;
	protected $c_loc_field = 0;
	protected $ignore_str = "MB\/NET|USB|usb-|\/pci@";
}

class pci_sparcenterpriset5120 extends pci_sparct32 {

	// Same as the T3-2

}

class pci_t5140 extends pci_sparct32 {

	// Same as the T3-2

}

class pci_t5240 extends pci_sparct32 {

	// Same as the T3-2

}

//- Fujitsu hardware ---------------------------------------------------------

class pci_fujitsusiemensprimepower6501slot8xsparc64v extends pci_parser {

// board | type | freq | slot | name | model
// 0     | 1    | 2    | 3    | 4    | 5

	protected $bus_field = 1;
	protected $c_name_field = 4;
	protected $c_model_field1 = 5;
	protected $c_model_field2 = 6;
	protected $c_hz_field = 2;
	protected $ignore_str = "pci Rev|SUNW,hme|53C875|pci10df|375-3290";

	protected function get_c_loc()
	{
		return "board " . $this->a[0] . "/slot" . $this->a[3];
	}
}

class pci_fujitsusiemenscomputerssparcenterprisem4000server extends
pci_parser {

// LSB | Type | LPID | RvID,DvID,VnID | BDF | State Act, | Max | Name |  Model
// 0   | 1    | 3    | 4              | 5   | 6          | 7   | 8    |  9

	protected $bus_field = 1;
	protected $c_name_field = 12;
	protected $c_model_field = 13;
	protected $c_loc_field = 2;
	protected $ignore_str = "N\/A";

	protected function get_c_hz()
	{
		// Use a special function because PCI-X reports in MHz, but PCIE
		// reports the number of lanes

		$speed = $this->a[11];

		return ($speed <= 32) 
			? "$speed lane"
			: "${speed}HHz";
	}

}

?>
