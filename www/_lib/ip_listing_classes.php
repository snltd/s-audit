<?php

//============================================================================
//
// ip_listing_classes.php
// ----------------------
//
// Classes used in the IP listing page
//
//============================================================================

class GetIpList {

	// This gets all the data for the IP listing page. It's equivalent to
	// GetServers:: on a normal audit page. Audit files are processed by the
	// GetIPFromAudit:: class, which is in the reader_x_class.php files to
	// allow for alternative storage methods. Everything else will always be
	// in a flat file, so it's kept here.

	public $addrs = array();
		// Can contain the following
		//
		// [IP_RES] => array(ip_addr => hostname)  - reserved IPs
		// [IP_LIVE] => array(ip_addr => hostname) - IPs in live audit files
		// [IP_PING] => array(ip_addr)             - pingable but not
		//                                           resolvable
		// [IP_DNS] => array(ip_addr => hostname)  - DNS resolved IPs

	public $timestamp = array();
		// elements are IP_RES and IP_LIST, and they hold the timestamps of
		// those files, if they exist

	public $scan_host;
		// The hostname of the machine that produced the IP_LIST_FILE

	public $subnets = array();
		// A unique array of subnets x.x.x.0

	public function __construct($map)  {
		
		// Use a separate class to get all the IP addresses from the audit
		// files

		if (sizeof($map->map) > 0) {
			$aud_ips = new GetIPFromAudit($map);
			$this->addrs["IP_LIVE"] = $aud_ips->get_ips();
		}
		else
			$this->addrs["IP_LIVE"] = array();

		// Use the reserved IP file to populate addrs[IP_RES]

		if (file_exists(IP_RES_FILE)) {
			$this->get_ip_res_file(IP_RES_FILE);
			$this->timestamp["IP_RES"] = filemtime(IP_RES_FILE);
		}
		else
			$this->addrs["IP_RES"] = array();

		// Use the IP list file to populate addrs[IP_PING] and
		// addrs[IP_DNS]. get_ip_list_file also sets a timestamp element

		if (file_exists(IP_LIST_FILE))
			$this->get_ip_list_file(IP_LIST_FILE);
		else
			$this->addrs["IP_DNS"] = $this->addrs["IP_PING"] = array();

		$this->mk_subnet_list();
		asort($this->subnets);
	}

	private function get_ip_list_file($list_file)
	{
		$ilf = file($list_file);

		// Parse the header and store what we find. We have to work out a
		// Unix timestamp from the header.  If there isn't a valid header,
		// forget it

		$h = preg_split("/[\s:\/]+/", trim($ilf[0]));

		if ((count($h) != 7) || ($h[0] != "@@"))
			return false;

		$this->scan_host = $h[1];
		$this->timestamp["IP_LIST"] = mktime($h[2], $h[3], 0, $h[5], $h[4],
		$h[6]);
	
		// Now crunch through the rest of the file, populating the class
		// arrays as we go

		for ($i = 1; $i < sizeof($ilf); $i++) {
			$e = preg_split("/\s+/", trim($ilf[$i]), 3);

			// Valid first column entries go in the pingable array

			if ($e[0] == long2ip(ip2long($e[0])))
				$this->addrs["IP_PING"][$e[0]] = 0;

			if (($e[1] != "-") && ($e[2] == long2ip(ip2long($e[2]))))
				$this->addrs["IP_DNS"][$e[2]] = $e[1];
			
		}

	}

	private function get_ip_res_file($res_file)
	{
		// Read and validate the IP_RES_FILE, putting the data it contains
		// into an address => hostname array

		$irf = file($res_file);

		// Pull out lines which look roughly correct, then validate properly
		// and put valid addresses into an array

		$virf = preg_grep("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\s+\S+$/",
		$irf);

		foreach($virf as $line) {
			$a = preg_split("/\s+/", $line);

			if ($a[0] == long2ip(ip2long($a[0])))
				$this->addrs["IP_RES"][$a[0]] = $a[1];
		}

	}

	private function mk_subnet_list()
	{
		// Look at the addrs array and get all the subnets in it.

		$nets = array();

		foreach($this->addrs as $a=>$b) {
			$nets = array_merge($nets, preg_replace("/\.\d*$/", "",
			array_keys($b)));
		}

		$this->subnets = array_unique($nets);
	}

}

//----------------------------------------------------------------------------
// DATA DISPLAY

class IPGrid extends HostGrid{

	// This class creates the HTML for the IP address audit. A column for
	// each known subnet, and lots of colour-coding.

	protected $fields;	// array of networks (from $s)
	protected $l;		// the list of IP addresses (from $s)

	public function __construct($s)
	{
		// The fields are the subnets we know about. We have to manually
		// include the key since we don't call the parent::__construct()

		$this->fields = $s->subnets;
		$this->l = $s->addrs;
		include_once(KEY_DIR . "/key_ip_listing.php");
		$this->grid_key = $grid_key;
	}

	public function grid_header()
	{
		// Print the horizontal table column headers. Override the default
		// method because we force each column to be the same width
	
		$ret_str = "\n<tr>";
		$w = 100 / sizeof($this->fields);
	
		foreach($this->fields as $field)
			$ret_str .= "<th width=\"${w}%\" >${field}.0</th>";
	
		return $ret_str . "</tr>";
	}

	public function grid_body()
	{
		$ret = "";

		// Working down the rows, where $r is the row

		for ($r = 1; $r < 255; $r++) {

			$ret .= "\n<tr>";

			// Working along the colunmns -- i.e. the subnets

			foreach($this->fields as $n) {
				$styl = $ic = $et = false;

				// Make the address, then see if it's in any of the arrays

				$a = "${n}.$r";

				if (in_array($a, array_keys($this->l["IP_DNS"]))) {
					$styl = "resolved";
					$et = " (" . $this->l["IP_DNS"][$a] . ")";
				}
				elseif (in_array($a, array_keys($this->l["IP_LIVE"]))) {
					$styl = "onlylive";
					$et = " (" . $this->l["IP_LIVE"][$a] . ")";
				}
				elseif (in_array($a, array_keys($this->l["IP_PING"])))
					$styl = "onlyping";
				elseif (in_array($a, array_keys($this->l["IP_RES"]))) {
					$et = " (" . $this->l["IP_RES"][$a] . ")";
					$styl = "reserved";
				}
				
				// If we have the IP_DNS array, we can look to see if things
				// were up or not on the last sweep

				if ($styl && sizeof($this->l["IP_DNS"] > 0)) {

					$ic = (in_array($a, array_keys($this->l["IP_DNS"]))) 
						? inlineCol::box("green")
						: inlineCol::box("red");
				}

				// Bold hosts we have audits for

				if (in_array($a, array_keys($this->l["IP_LIVE"])))
					$et = preg_replace("/\(([^\s\)]*)/",
					"(<strong>$1</strong>", $et);

				$ret .= new Cell("$a $et", $styl, $ic);
			}

			$ret .= "</tr>";
		}

		return $ret;
	}

	protected function grid_key()
	{
		$nf = sizeof($this->fields);

		$ret = "\n<tr><td class=\"keyhead\" colspan=\"${nf}\">key</td>" .
		"</tr>\n<tr>";

        $ret .= $this->grid_key_col($this->grid_key["general"], $nf);

		return $ret;
	}

}

?>
