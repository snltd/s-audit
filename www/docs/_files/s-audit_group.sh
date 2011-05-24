#!/bin/ksh

#=============================================================================
#
# s-audit_group.sh
# -----------------
#
# This simple script creates, removes, and lists audit groups known to the
# server on which it is run.
# 
# You probably want to run it as root.
#
# EXAMPLES
#
# To create a group called "production", which will hold audits for
# production servers, with write permissions for the audit user:
#
#  $ s-audit_group.sh create -d "Production Servers" -u audit production
#
# To remove that group and all its audit files:
#
#  $ s-audit_group.sh remove production
#
# Part of s-audit. (c) 2011 SearchNet Ltd
#   see http://snltd.co.uk/s-audit for licensing and documentation
#
#=============================================================================

#-----------------------------------------------------------------------------
# VARIABLES

PATH=/usr/bin:/usr/sbin
        # Always set your PATH

AUDIT_DIR="/var/snltd/s-audit"
	# should match the AUDIT_DIR definition in s-audit_config.php. Can be
	# overridden with -R

#-----------------------------------------------------------------------------
# FUNCTIONS

function die
{
	# Print an error and exit

	print -u2 "ERROR: $1"
	exit ${2:-1}
}


function usage
{
    cat<<-EOUSAGE

	usage:

	  ${0##*/} create [-d description] [-u user] [-g group] [-R dir] group_name

	  ${0##*/} remove -r group_name

	  ${0##*/} lists

	EOUSAGE

	exit 2
}

function mk_group_dirs
{
	# Make the group directories.

	# $1 is the group name

	R=${AUDIT_DIR}/$1

	[[ -d $R ]] && die "group '$1' exists at ${R}."

	mkdir -p ${R}/hosts ${R}/network ${R}/extra 
}

function rm_group_dirs
{
	# Remove a group's directories

	# $1 is the group name

	R=${AUDIT_DIR}/$1

	[[ -d $R ]] || die "group directory does not exist. [$R]"

	rm -fr $R

	[[ -d $R ]] && return 1 || return 0
}

#-----------------------------------------------------------------------------
# SCRIPT STARTS HERE

# Need arguments

[[ $# -lt 1 ]] && usage

# Get options

CMD=$1

shift $(( $OPTIND ))

while getopts "d:g:u:R:" option 2>/dev/null
do

    case $option in
		
		"d")	MSG="$OPTARG"
				;;

		"g")	GROUP=$OPTARG
				;;

		"R")	AUDIT_DIR=$OPTARG
				;;

		"u")	USER=$OPTARG
				;;

		*)		usage

	esac

done

shift $(($OPTIND - 1))

[[ -w $AUDIT_DIR ]] || die "Can't write to AUDIT_DIR. [${AUDIT_DIR}]"

if [[ $CMD == "create" ]] 
then
	[[ $# == 1 ]] || usage

	[[ -n ${USER}$GROUP ]] && [[ $(id) != "uid=0(root)"* ]] \
		&& die "-u and -g options require root privileges."

	mk_group_dirs $1 || die "failed to create group directories."

	[[ -n $MSG ]] && print "$MSG" >>"${AUDIT_DIR}/${1}/info.txt"

	[[ -n $USER ]] && chown -R $USER "${AUDIT_DIR}/${1}"
	[[ -n $GROUP ]] && chgrp -R $GROUP $R "${AUDIT_DIR}/${1}"

elif [[ $CMD == "remove" ]]
then
	[[ $# == 1 ]] || usage

	rm_group_dirs $1 || die "failed to remove group directories."

elif [[ $CMD == "list" || $CMD == "ls" ]]
then
	[[ -d $AUDIT_DIR ]] || die "no audit directory. [${AUDIT_DIR}]"

	print "The following audit groups exist:"

	find ${AUDIT_DIR}/* -type d -prune | sed 's|^.*/|  |'
else
	usage
fi


