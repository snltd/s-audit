#!/bin/ksh

#=============================================================================
#
# s-audit_secdefs.sh
# ------------------
#
# Part of the support files for the s-audit interface. This script creates a
# ini style file which lists the default users, cron jobs, and user_attrs on
# a clean install of a machine. The files it creates are used on the
# security audit page.
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

DIST=${1:-Solaris}

# Now we know what to call the file, and we can open it 

OUTFILE="sec_defs-$DIST-$(uname -r).data"

cat <<-EOPHP >$OUTFILE
;=============================================================================
;
; s-audit security definition file for $DIST $(uname -r).
;
; Generated $(date) by ${0##*/}
;
;=============================================================================

[users]
EOPHP

# Users are very easy

cut -d: -f1,3 /etc/passwd >>$OUTFILE

# Crontabs. Prefix every cron job with "username:"

print "\n[crontabs]" >>$OUTFILE

find /var/spool/cron/crontabs -type f -a ! -name \*.au | while read f
do
	sed "/^#/d;s/.*/${f##*/}:&/" $f
done >>$OUTFILE

# user attrs, if this system has them

if [[ -f /etc/user_attr ]]
then
	print "\n[user_attrs]"
	grep -v "^#" /etc/user_attr
fi >>$OUTFILE

# That's it.

