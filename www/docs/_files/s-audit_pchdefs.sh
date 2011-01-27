#!/bin/ksh

#=============================================================================
#
# s-audit_pchdefs.sh
# ------------------
#
# Part of the support files for the s-audit interface. This script creates a
# PHP array which lets the s-audit interface single-server and comparison
# pages produce mouse-over tooltips briefly describing each installed patch.
#
# To generate the array we require a patchdiag.xref file, obtainable from
# Oracle. The script is able to download this file itself, provided either
# cURL or wget is available. You may have to tweak the CURL or WGET
# variables.
#
# You can also supply a path to an existing patchdiag.xref as a single
# argument. (This will override any instruction to download).

# The script writes a pch-defs file for each supported version (2.5 - 10) of
# each architecture (sparc and i386) of Solaris, in the current working
# directory, or another directory, specified with the -d option. You should
# copy the resulting files to the _lib/pkg_defs/ subdirectory of the PHP
# s-audit interface. (Or create them there in the first place.)
#
# A range of pch_def files are bundled with the s-audit interface, but
# obviously they'll get out of date pretty quickly. If you want to keep your
# patch definitions up-to-date, run this script through cron, with the
# download option, every few days.
#
# Part of s-audit. (c) 2011 SearchNet Ltd
#   see http://snltd.co.uk/s-audit for licensing and documentation
#
#=============================================================================

#-----------------------------------------------------------------------------
# VARIABLES

PATCHDIAGURL="https://getupdates.oracle.com/reports/patchdiag.xref"
	# Where to download the patchdiag.xref file from, if we're asked to

USER="username"
	# Oracle support username

PASS="password"
	# Oracle support password

DESTDIR=$(pwd)
	# Where to put temporary files. Overriden with -d option

DLPATCHDIAG="/var/tmp/patchdiag.xref"
	# Where to download patchdiag.xref to

CURL="/usr/local/bin/curl"
	# Path to curl binary

WGET="/usr/sfw/bin/wget"
	# Path to wget binary

PATH=/usr/bin
	# Always set your PATH

#-----------------------------------------------------------------------------
# FUNCTIONS

function die
{
	print -u2 "ERROR: $1"
	exit ${2:-1}
}

function usage
{
	cat<<-EOUSAGE

	usage:

	  ${0##*/} [-k] [-d dir] <-x|patchdiag>

	where
	  -d :     directory in which to write definition files

	  -k :     keep patchdiag.xref after download

	  -x :     download new patchdiag.ref file from
	           $PATCHDIAGURL
	
	If a path to patchdiag.xref is supplied as an argument, -x and -k
	options are ignored.

	EOUSAGE
	exit 2
}

#-----------------------------------------------------------------------------
# SCRIPT STARTS HERE

while getopts "d:kx" option 2>/dev/null
do

    case $option in

		"d")	DESTDIR=$OPTARG
				;;

		"k")	KEEPFILE=1
				;;

		"x")	DOWNLOAD=true
				PATCHDIAG=$DLPATCHDIAG
				;;

		*)		usage

	esac

done

shift $(($OPTIND - 1))
print

if (( $# == 0 ))
then

	if [[ -n $DOWNLOAD ]]
	then
		# Try to download patchdiag.xref with either cURL or wget

		if [[ -x $CURL ]]
		then
			print "Downloading patchdiag.xref with cURL.\n"

			$CURL \
				--location \
				-k -u${USER}:$PASSWORD \
				--retry 3 \
				-o $DLPATCHDIAG \
			$PATCHDIAGURL

		elif [[ -x $WGET ]]
		then
			print "Downloading patchdiag.xref with wget.\n"

			$WGET \
				--progress=bar \
				--user=$USER \
				--password=$PASSWORD \
				--no-check-certificate \
				-O $DLPATCHDIAG \
				-t 3 \
			$PATCHDIAGURL

		else
			die "No download mechanism found. (Tried cURL and wget.)"
		fi

	else
		die "No patchdiag.xref supplied."
	fi

elif (( $# == 1))
then
	PATCHDIAG="$1"
else
	usage
fi

[[ -f $PATCHDIAG ]] || die "No patchdiag.xref found. [${PATCHDIAG}]"

print "\nCreating files in ${DESTDIR}:"

# For now at least, we're only interested in O/S patches. We disregard
# firmware patches and stuff

for ver in 2.5 2.5.1 2.6 7 8 9 10
do
	[[ $ver == "2."* ]] && osn="5${ver#2}"  || osn="5.$ver"

	for sfx in "" _x86
	do
		[[ $sfx == "_x86" ]] && arch="i386" || arch="sparc"

		OUTFILE="${DESTDIR}/pch_def-${osn}-${arch}.php"
		print "  ${OUTFILE##*/}"

		# Open the file with PHP stuff

		cat <<-EOPHP >$OUTFILE
<?php

//============================================================================
//
// Patch definition file for $arch Solaris ${ver}.
//
// Generated $(date) by ${0##*/}
//
//============================================================================

\$hover_arr = array(
		EOPHP
	
		grep "|${ver}${sfx}|" $PATCHDIAG \
		| cut -d\| -f1,11 \
		| sed -e 's/"//g' -e "s/SunOS 5\..*: //" -e \
		"s/^\([0-9][0-9][0-9][0-9][0-9][0-9]\)|\(.*\)$/  \"\1\" => \"\2\",/" \
		| sed '$s/,$/);/' >>$OUTFILE

	# And close the file

	print "\n?>" >>$OUTFILE

	done

done

exit
