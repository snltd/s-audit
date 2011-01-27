#!/bin/ksh

#=============================================================================
#
# s-audit_subnet.sh
# -----------------
#
# This script audits subnets, producing a list which contains information on
# DNS records and pingable machines. Currently it produces information which
# is vaguely human-readable, but is designed to be understood by s-audit's
# PHP audit interface.
#
# This was written for a specific environment, and may or may not be of use
# to you. It works by pinging all addresses on given subnets, and by sending
# a batch query to a DNS server.
# 
# The end result is a file with three whitespace separated fields. Look at
# the comments near the bottom for the format. It's trivial to produce a
# similar file with NMAP and sed.
#
# So, why not use NMAP? First, it's a bit chunk of C++ that only compiles
# with GCC, so needs extra libraries and stuff, and s-audit is designed to
# be ultra-portable. Second, doing an nmap -sP scan with t < 3 missed
# certain things on the network I wanted to audit, and  didn't give
# consistent results over multiple runs. Doing a slow scan was consistent,
# but took hours. (Literally.)
#
# Usage
# 
#   s-audit_subnet.sh subnet ...
#
# where "subnet" is a network address. e.g. 10.10.8.0
# 
# Requirements
#
# A DNS server which knows about the network you wish to audit and accepts
# batch requests (i.e. BIND 9), pingable hosts, and a dig(1) executable.
#
# Part of s-audit. (c) 2011 SearchNet Ltd
#   see http://snltd.co.uk/s-audit for licensing and documentation
#
#=============================================================================

#-----------------------------------------------------------------------------
# VARIABLES

PATH=/usr/bin:/usr/sbin
	# Always set your PATH

DIG="/opt/bin/dig"
	# Path to dig binary

DNS_SRV="cs-infra-01z-dns-i"
	# DNS server to use for lookups

PARL_PINGS=25
	# How many pings to do in parallel. Shouldn't cause any kind of load on
	# the system, just depends on how badly you want to flood your process
	# table

TMPFILE="/tmp/${0##*/}.$$.$RANDOM"
	# Temp file location

#-----------------------------------------------------------------------------
# FUNCTIONS

ping_subnet()
{
	# Ping every address on the given subnet, running as many parallel pings
	# as are defined by the PARL_PINGS variable.

	# $1 is the subnet, of the form a.b.c 

	typeset -i i=1

	while [[ $i -lt 256 ]]
	do

		if [[ $(jobs -p | wc -l) -lt $PARL_PINGS ]]
		then
			ping ${1}.$i 1  &
			(( i = $i + 1 ))
		fi

	done 
}

resolve_subnet()
{
	# Send a batch query to a DNS server, trying to reverse lookup every
	# address.
	
	# $1 is the subnet, of the form a.b.c

	typeset -i i=1

	while [[ $i -lt 256 ]]
	do
		print -- -x ${1}.$i
		(( i = $i + 1))
	done | $DIG @$DNS_SRV +nocmd  +noall +answer -f - \ | sed \
	's/^\([0-9]*\).\([0-9]*\).\([0-9]*\).\([0-9]*\).*PTR	\(.*\).$/\4.\3.\2.\1 \5/'
}

die()
{
	print -u2 "ERROR: $1"
	exit ${2:-1}
}

#-----------------------------------------------------------------------------
# SCRIPT STARTS HERE

# A few checks.

if [[ $# == 0 ]]
then
	print "usage: ${0##*/} subnet..." 
	exit 2
fi

[[ -x $DIG ]] || die "can't execute dig."

# For all given subnets, do a reverse DNS lookup on all addresses, and put
# the results of that query  in a temporary file. Then ping every address on
# the subnet, and join the results with the temp file. This produces output
# of the following form:

# pingable_address hostname dns_address

# So, if the first field exists, but the others don't, we have a live
# address that's not in DNS. If the first field is blank but two and three
# aren't, field 2 is a DNS name which resolves to field 3, but doesn't
# respond to a ping. Note. Both lists have to be sorted in the same way, or
# the join will fail

for subnet in $@
do
	net=${subnet%.0}

	print $net | egrep -s '^[0-9]+\.[0-9]+\.[0-9]$' \
		|| die "$subnet is not a valid subnet address."

	resolve_subnet $net | sort >$TMPFILE
	ping_subnet $net | sed -n '/is alive$/s/ .*$//p' | sort | \
	join -e - -a1 -a2 -o1.1,2.2,2.1 - $TMPFILE
done 

rm -f $TMPFILE

exit

