#!/bin/ksh

#=============================================================================
#
# s-audit_dns_resolver.sh
# -----------------------
#
# Script to run DNS lookups on all the sites found by s-audit's "hosted
# services" audit.  Examines all known sites, does DNS lookups on them using
# the DNS server specified in the DNS_SRV variable, and creates a file
# pairing URI with IP address. This file is picked up by s-audit's web
# interface.
#
# Requires dig.
#
# Should be run by cron once the day's audit files are in.
#
# Part of s-audit. (c) 2011 SearchNet Ltd
#   see http://snltd.co.uk/s-audit for licensing and documentation
#
#=============================================================================

#-----------------------------------------------------------------------------
# VARIABLES

PATH=/usr/bin
	# Always set your PATH

AUDIT_DIR="/var/s-audit"
	# s-audit's /var directory

SRC_DIR="${AUDIT_DIR}/audit"
	# Where we expect to find audit files

OUTFILE="${AUDIT_DIR}/dns/uri_list.txt"
	# Where to write the "uri=a.b.c.d" format list. Override with -f

DNS_SRV="dns-server"
	# Which DNS server to use for lookups. Override with -s

DIG="/usr/local/bin/dig"
	# Path to dig executable. Override with -d

#-----------------------------------------------------------------------------
# FUNCTIONS

die()
{
	print -u2 "ERROR: $1"
	exit ${2:-1}
}

usage()
{
	cat<<-EOUSAGE
	usage:
	  ${0##*/} [-s dns_server] [-d dir] [-D path] [-o file]

	where:
	  -o :     path to output file.
	             [Default is '${OUTFILE}'.]
	  -D :     path to dig binary
	             [Default is '${DIG}'.]
	  -d :     directory containing audit files
	             [Default is '${SRC_DIR}'.]
	  -s :     DNS server on which to do lookups
	             [Default is '${DNS_SRV}'.]

	EOUSAGE
}

#-----------------------------------------------------------------------------
# SCIRPT STARTS HERE

while getopts "D:s:o:" option 2>/dev/null
do

	case $option in

		"d")	SRC_DIR=$OPTARG
				;;

		"D")	DIG=$OPTARG
				;;
	
		"o")	OUTFILE=$OPTARG
				;;

		"s")	DNS_SRV=$OPTARG
				;;
		
		*)		usage
				exit 2

	esac

done

[[ -x $DIG ]] \
	|| die "can't run dig [${DIG}]" 1

[[ -w ${OUTFILE%/*} ]] \
	|| die "can't write to output directory [${OUTFILE%/*}]" 2

# Pull URIs out of all the audit files. They're on lines beginning "site=".
# We are only interested in URIs with dots in them - ones without will take
# an age to time out. Run the whole lot into a batch lookup job with dig
# (only works with 9.4+), and filter the results through sed to produce
# lines of the form www.uri.com=1.2.3.4. Some sites just can't be resolved,
# and dig drops a line into the output informing you of this. These lines
# are prefixed by a semicolon, and we don't want them.  We also don't want
# trailing dots on CNAMES. Run everything through uniq to get rid of all the
# duplicate references to things which are CNAME aliases.  Why you can't
# just stick a -u in with the sort flags is left as an exercise for the
# reader

find $SRC_DIR -name \*.saud 2>/dev/null \
	| xargs grep "^website=" | cut -d\  -f2  | grep '\.' | sort -u \
	| $DIG +noall +answer @$DNS_SRV -f - \
	| sed '/^;/d;s/\.[ 	].*[ 	]/=/;s/\.$//' \
	| sort -t= -k2 \
	| uniq \
	>$OUTFILE

