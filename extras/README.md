# s-audit support files

This directory contains shell scripts which help you set up and manage an
s-audit environment. Please see the preamble at the top of each script for
more details, but briefly:

* `s-audit_dns_resolver.sh`: Uses `dig` to run DNS lookups on all the sites
  found by s-audit's "hosted services" audit.  Examines all known sites,
  does DNS lookups on them using the DNS server specified in the `DNS_SRV`
  variable, and creates a file pairing URI with IP address. This file is
  picked up by s-audit's web interface.
* `s-audit_group.sh`: creates, removes, and lists audit groups known to the
  server on which it is run.
* `s-audit_pchdefs.sh`: uses a `patchdiag.xref` file to create a PHP array
  which lets the s-audit interface single-server and comparison pages
  produce mouse-over tooltips briefly describing each installed patch.
* `s-audit_pkgdefs-ips.sh`: creates a PHP array which lets the s-audit
  interface single-server and comparison pages produce mouse-over tooltips
  properly namimg each installed package on systems which use IPS.
* `s-audit_pkgdefs.sh`: creates a PHP array which lets the s-audit
  interface single-server and comparison pages produce mouse-over tooltips
    properly namimg each installed package on systems which use SYSV
	packages.
* `s-audit_secdefs.sh`: creates a file containing  a single PHP array which
  lists the default users, cron jobs, and `user_attrs` on a clean install of a
  machine. These data are used on the security audit page.
* `s-audit_subnet.sh`: audits subnets, producing a list which contains
  information on DNS records and pingable machines. Currently it produces
  information which is vaguely human-readable, but is designed to be
  understood by s-audit's PHP audit interface.

Some of these scripts are useful, some I expect to only be of interest to
the s-audit maintainers.
