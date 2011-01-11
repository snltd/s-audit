#!/bin/ksh

#=============================================================================
#
# s-audit_pkgdefs.sh
# ------------------
#
# Part of the support files for the s-audit interface. This script creates a
# PHP array which lets the s-audit interface single-server and comparison
# pages produce mouse-over tooltips properly namimg each installed package.
#
# To generate the array we require a Solaris_x/Product directory, like you
# find on an installation DVD or a Jumpstart server. My Jumpstart server has
# images sorted under sparc or x86 directories. This script probably won't
# work very well for you.
#
# The script takes a single argument, a path to a directory containing a
# Solaris installation image.  It writes to pkg_defs-Solaris-5.x-arch.php,
# in the current working directory. Copy the resulting file to the
# _lib/pkg_defs/ subdirectory of the PHP auditor interface.
#
# A range of pkg_def files are bundled with the s-audit interface, so no one
# other than me will probably ever use this.
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

if [[ $# != 1 ]]
then
	print -u2 "usage: ${0##*/} <dir>"
	exit 2
fi

[[ -d $1 ]] || die "$1 is not a directory."

# Get the proper location of the package directory

PROD="$(find $1 -name Solaris_[0-9]\* -prune)/Product"

[[ -d $PROD ]] || die "no Product directory [${PROD}]."

# Get the architecture by looking at the ARCH value in the core Solaris
# root package

ARCH=$(sed -n '/^ARCH=/s/^ARCH=//p' "${PROD}/SUNWcsr/pkginfo")

[[ -z $ARCH ]] && die "can't determinte architecture."

# Get the Solaris version from the package directory and make it 5.x style

SVER=${PROD%/Product}
SVER=${SVER##*_}

[[ $SVER == "2."* ]] && SVER="5${SVER#2}" || SVER="5.$SVER"

# Now we know what to call the file, and we can open it 

OUTFILE="pkg_defs-Solaris-${SVER}-${ARCH}.php"

cat <<-EOPHP >$OUTFILE
<?php

//============================================================================
//
// Package definition file for $ARCH SunOS ${SVER}.
//
// Generated $(date) by ${0##*/}
//
//============================================================================

\$hover_arr = array(
EOPHP

# There doesn't appear to be a safe way to parse all the pkginfo files in
# one shot, because they aren't of an entirely consistent form. This way is
# slower, but it's safer. Pull the NAME line from the pkginfo file, and
# escape soft quotes. Run all the output through sed to replace the final
# trailing comma with );

ls $PROD | egrep -v "^locale$" | while read pkg
do
	print "\t\"${pkg}\" => \"$(sed -n 's/\"//g;/^NAME=/s/^NAME=//p' \
	${PROD}/${pkg}/pkginfo)\","
done | sed '$s/,$/);/'>>$OUTFILE

# Now close off the file

print "\n?>" >>$OUTFILE

# That's it.

