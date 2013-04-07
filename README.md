# s-audit

s-audit is designed to help you manage Solaris networks, by creating a
configuration management database. At its core is [the
client](https://github.com/snltd/s-audit/blob/master/client/s-audit.sh); a
shell script which is run on any host you wish to audit, and which produces
clear, human-readable output, or a machine-parseable audit file describing
that host.
=======
See it working at:

  http://snltd.co.uk/s-audit/demonstrator/s-audit/index.php?g=example

s-audit is designed to help you manage Solaris networks. At its core is the
client; a shell script which is run on any host you wish to audit, and which
produces clear, human-readable output, or a machine-parseable audit file
describing that host.

Copy that audit file to a machine which has the s-audit interface installed
on it, and that host is described in a series of easy-to-understand grids,
which give you at-a-glance overviews of your hardware/virtualization, O/S
revisions and configurations, filesystem layouts and usage, and much more.

The s-audit interface also dynamically generates IP address maps, and lets
you compare machines, helping you track down the kinds of configuration
inconsistencies which can lead to problems.s-audit is designed to help you
manage Solaris networks. At its core is the client; a shell script which is
run on any host you wish to audit, and which produces clear, human-readable
output, or a machine-parseable audit file describing that host.

Copy that audit file to a machine which has the s-audit interface installed
on it, and that host is described in a series of easy-to-understand grids,
which give you at-a-glance overviews of your hardware/virtualization, O/S
revisions and configurations, filesystem layouts and usage, and much more.

The s-audit interface also dynamically generates IP address maps, and lets
you compare machines, helping you track down the kinds of configuration
inconsistencies which can lead to problems.

Though s-audit is no replacement for a fully configuration managed system
using, for instance, ohai and Chef, I have found it an invaluable tool over
the last few years. I hope you do too.

# See it Working

[There is a working demonstration of the interface at my company's
website](http://snltd.co.uk/s-audit/demonstrator/s-audit/index.php?g=example).

# Documentation

Full documentation for the client and the interface is included in the
s-audit download. [You can read it online
here](http://snltd.co.uk/s-audit/demonstrator/docs/index.php).
