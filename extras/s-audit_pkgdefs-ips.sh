#!/bin/ksh

#=============================================================================
#
# s-audit_pkgdefs-ips.sh
# ----------------------
#
# Part of the support files for the s-audit interface. This script creates a
# PHP array which lets the s-audit interface single-server and comparison
# pages produce mouse-over tooltips properly namimg each installed package.
#
# To generate the array we require access to a Solaris IPS repository.
#
# The script takes one optional argument, which is the release name of this
# version of Solaris. For instance "Express". This has to match up with the
# "release" string produced by s-audit.sh.
#
# A range of pkg_def files are bundled with the s-audit interface, so no one
# other than me will probably ever use this. Takes for ever to create the
# file. That's IPS's fault, not mine.
#
# Part of s-audit. (c) 2011 SearchNet Ltd
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

[[ -f /bin/pkg ]] || die "pkg not supported on this system"

[[ -n $1 ]] && REL=$1 || REL=Solaris

uname -pr | read SVER ARCH

OUTFILE="pkg_defs-${REL}-${SVER}-${ARCH}.php"

cat <<-EOPHP >$OUTFILE
<?php

//============================================================================
//
// Package definition file for $ARCH SunOS ${SVER} $1
//
// Generated $(date) by ${0##*/}
//
//============================================================================

\$hover_arr = array(
EOPHP

pkg list -aH | while read name ver state flags
do
	# Packages from non-default publishers have the publisher name in
	# (brackets) afte the package name, so the read line above will be
	# broken. That's fine - we don't want those packages anyway. If a
	# package is not installed on the system, we have to get its summary
	# from a remote repository, which requires the -r flag.

	if [[ $state == "installed" ]]
	then
		unset opt
	elif [[ $state == "known" ]]
	then
		opt="-r"
	else
		continue
	fi

	# Get the package summary

	sum=$(pkg info $opt $name | sed -n '/Summary/s/^.*Summary: //p')
		
	# If we have a summary, write it to the PHP array
	
	[[ -n $sum ]] && print "  \"$name\" => \"$sum\","

done | sed '$s/,$/);/'>>$OUTFILE

# Now close off the file

print "\n?>" >>$OUTFILE

# Done
