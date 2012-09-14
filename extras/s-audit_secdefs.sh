#!/bin/ksh

#=============================================================================
#
# s-audit_secdefs.sh
# ------------------
#
# Part of the support files for the s-audit interface. This script creates a
# file containing  a single PHP array which lists the default users, cron
# jobs, and user_attrs on a clean install of a machine. These data are used
# on the security audit page.
#
# The script writes to sec_defs-DISTRO-5.x.php, in the current working
# directory. Copy the resulting file to the _lib/defs/security subdirectory
# of the PHP auditor interface.
#
# The user can provide DISTRO as an argument. If none is supplied, "Solaris"
# is used.
#
# A range of sec_def files are bundled with the s-audit interface, so no one
# other than me will probably ever use this.
#
# Part of s-audit. (c) 2011-2012 SearchNet Ltd
#   see http://snltd.co.uk/s-audit for licensing and documentation
#
#=============================================================================

PATH=/usr/bin

#-----------------------------------------------------------------------------
# FUNCTIONS

function die
{
	print -u2 "ERROR: $1"
	exit ${2:-1}
}

#-----------------------------------------------------------------------------
# SCRIPT STARTS HERE

if [[ $1 == "help" || $1 == "-h" ]]
then
	print "usage: ${0##*/} [distribution_name]"
	exit 2
fi

if ! id | egrep -s "^uid=0"
then
	print "ERROR: script must be run as root."
	exit 1
fi

DIST=${1:-Solaris}

# Now we know what to call the file, and we can open it 

OUTFILE="sec_defs-$DIST-$(uname -r).php"

print -u2 "Creating definition file '$OUTFILE'."

exec 1>$OUTFILE

cat << EOPHP 
<?php

//============================================================================
//
// s-audit security definition file for $DIST $(uname -r).
//
// Generated $(date) by ${0##*/}
//
//============================================================================

\$sec_data = array(

EOPHP

# user attrs, if this system has them. 5.11 introduced the /etc/user_attr.d
# directory

if [[ -n /etc/user_attr ]]
then
	ATTR_FILES=/etc/user_attr

	[[ -d /etc/user_attr.d ]] \
		&& ATTR_FILES="$ATTR_FILES $(ls /etc/user_attr.d/*)"

	print '	"user_attrs" => array('
	cat $ATTR_FILES | sed -n '/^[^#]/s/.*/		"&",/p' | sort -u \
	| sed '$s/,$/),/'
fi

# Users. Remove my 264 user, because it's always there

print '\n	"users" => array('

cut -d: -f1,3 /etc/passwd | sed 's/:/ (/;s/$/)/;s/.*/		"&",/' \
| grep -v "rob (264)" | sed '$s/,$/),/'

# Crontabs. Prefix every cron job with "username:"

print  '\n	"crontabs" => array('

find /var/spool/cron/crontabs -type f -a ! -name \*.au | while read f
do
	sed "/^#/d;s/.*/${f##*/}:&/" $f
done | sed 's/"/\\"/g;s/.*/		"&",/' | sed '$s/,$/)/'

print ");\n\n?>"

# That's it.

