#!/bin/ksh

#=============================================================================
#
# s-audit_subnet_wrapper.sh
# -------------------------
#
# Simple wrapper to s-audit_subnet.sh. Create IP list file and copy it to a
# remote host.
#
# Part of s-audit. (c) 2011 SearchNet Ltd
#   see http://snltd.co.uk/s-audit for licensing and documentation
#
#=============================================================================

#-----------------------------------------------------------------------------
# VARIABLES

PATH=/usr/bin
	# Always set your PATH

AUD_NET_SCR="/usr/local/bin/s-audit_subnet.sh"
	# Path to s-audit_subnet.sh.

OUTFILE="/var/tmp/s-audit_subnet.${RAND}$$"
	# File that will be written

REMOTE_STR=$1
	# user@host

NETS="10.10.4.0 10.10.6.0 10.10.7.0 10.10.8.0"
	# List of subnets to scan, space separated

#-----------------------------------------------------------------------------
# FUNCTIONS

die()
{
	# Print an error and exit

	print -u2 "ERROR: $1"
	exit ${2:-1}
}

usage()
{
	# Print usage and exit

	print "usage: ${0##*/} user@host"
	exit 2
}

#-----------------------------------------------------------------------------
# SCRIPT STARTS HERE

# Make sure we can see the network audit script

[[ -x $AUD_NET_SCR ]] \
	|| die "can't execute subnet audit script. [${AUD_NET_SCR}]"

# Make sure we can write to the outfile directory

[[ -w ${OUTFILE%/*} ]] \
	|| die "can't write to ${OUTFILE%/*}."

# Start off with a header which says where and when the file was generated

print @@ $(uname -n) $(date "+%H:%M %d/%m/%Y") >$OUTFILE

# Run the script 

if $AUD_NET_SCR $NETS >>$OUTFILE
then
	scp -qCp $OUTFILE $REMOTE_STR >/dev/null 2>&1\
		|| die "failed to copy $OUTFILE to ${REMOTE_STR}."
		     
else
	die "failed to generate audit file at ${OUTFILE}."
fi

rm -f $OUTFILE

