<?php

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

	// The following variables point to the array element holding the data
	// we're interested in. If the element needs further processing,
	// overload the method that gets it.

	protected $bus_field = 0;
	protected $c_model_field;
	protected $c_hz_field;
	protected $c_name_field;
	protected $c_type_field;
	protected $c_loc_field;

	public function __construct($row)
	{
		// Populate variables. That is all

		if($this->filter($row))
			return false;

		$this->row = preg_replace("/^\S+ \((.*)\)$/", "$1", $row);
		$this->a =  preg_split("/\s+/", $this->row);

		$this->ret["bus"] = $this->get_bus();
		$this->ret["c_type"] = $this->get_c_type();
		$this->ret["c_model"] = $this->get_c_model();
		$this->ret["c_loc"] = $this->get_c_loc();
		$this->ret["c_name"] = $this->get_c_name();
		$this->ret["c_hz"] = $this->get_c_hz();

		// If the name and the model are the same, forget the name

		if ($this->ret["c_model"] == $this->ret["c_name"])
			unset($this->ret["c_name"]);

		$this->ret = array_unique($this->ret);
	}

	protected function filter($data)
	{
		// Used to filter out things like PCI-bridges and backplanes

		return $data;
	}
	
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
		// Get the slot the card is in

		return $this->a[$this->c_loc_field];
	}

	protected function get_c_model()
	{
		// get the card model name. Often you only have it for certain
		// cards, so check the field exists

		return (isset($this->a[$this->c_model_field]))
			? $this->a[$this->c_model_field]
			: false;
	}

	protected function get_c_name()
	{
		return (isset($this->a[$this->c_name_field]))
			? $this->a[$this->c_name_field]
			: false;
	}

	protected function get_c_hz()
	{
		// Not every machine reports speed (T2000 for instance)

		return (isset($this->c_hz_field))
			? $this->a[$this->c_hz_field]
			: false;
	}

	public function get_info()
	{
		//pr($this->ret);
		return $this->ret;
	}

}

class pci_sunfirev490 extends pci_parser {

// io_typ port_id bus_side slot bus_freq max_freq dev,func state name model
//   0   |  1    |    2   | 3  |  4     |   5    |   6    |  7  | 8  |  9
	
	protected $c_hz_field = 4;
	protected $c_type_field = 8;
	protected $c_name_field = 9;

	protected function get_c_model()
	{
		// Sometimes the model name is 9, sometimes 10, because field 8 can
		// have whitespace

		return (isset($this->a[10])) ? $this->a[10] : $this->a[9];
	}

	protected function get_c_loc()
	{
		return "side " . $this->a[2] . "/slot " . $this->a[3];
	}

	protected function filter($data)
	{
		return preg_match("/PCI-BRIDGE/", $data)
			? true
			: false;
	}
}

class pci_sunfiret200 extends pci_parser {

// Location | Type | Slot | Path | Name | Model
// 0        | 1    | 2    | 3    | 4    | 5

	protected $bus_field = 1;
	protected $c_name_field = 4;
	protected $c_type_field = 4;
	protected $c_model_field = 5;
	protected $c_loc_field = 0;

	protected function filter($data)
	{
		return preg_match("/usb|IOBD\/NET|\/PCIX |PCI-SWITCH/", $data)
			? true
			: false;
	}

}

class pci_sparct32 extends pci_parser {

// Slot | Bus Type | Name | Model | Status | Type | Path
// 0    | 1        | 2    | 3     | 4      | 5    | 6

	protected $bus_field = 1;
	protected $c_name_field = 2;
	protected $c_type_field = 5;
	protected $c_model_field = 3;
	protected $c_loc_field = 0;

	protected function filter($data)
	{
		return preg_match("/MB\/NET|USB|usb-/", $data)
			? true
			: false;
	}

}

class pci_sparcenterpriset5120 extends pci_sparct32 {

	// Same as the T3-2

}

class pci_t5140 extends pci_sparct32 {

	// Same as the T3-2

}


class pci_fujitsusiemenscomputerssparcenterprisem4000server extends
pci_parser {

// LSB | Type | LPID | RvID,DvID,VnID | BDF | State Act, | Max | Name |  Model
// 0   | 1    | 3    | 4              | 5   | 6          | 7   | 8    |  9

	protected $bus_field = 1;
	protected $c_name_field = 12;
	protected $c_model_field = 13;
	protected $c_loc_field = 2;

	protected function filter($data) {
		return preg_match("/N\/A/", $data)
			? true
			: false;
	}

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
