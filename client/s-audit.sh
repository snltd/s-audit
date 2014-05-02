#!/bin/ksh

#=============================================================================
#
# s-audit.sh
# ----------
#
# Audit a machine to find out information about the hardware and software
# it's running. Works on all Solaris versions from 2.6 up.
#
# Can be run as any user, but a full audit requires root privileges.
# Currently is not RBAC aware.
#
# For usage information run
#
#  s-audit.sh -h
#
# For documentation, license, changelog and more please see
#
#   http://snltd.co.uk/s-audit
#
# v3.3 (c) 2011-2012 SNLTD.
#
#=============================================================================

#-----------------------------------------------------------------------------
# VARIABLES

PATH=/bin:/usr/bin

WSP="	 "
	# A space and a literal tab. ksh88 won't accept \t

MY_VER="3.3"
	# The version of the script

typeset -R15 RKEY
	# Right alignment of LH column in disp() function

T_MAX=10
	# The maximum time, in seconds, any individual timed job is allowed to
	# take

C_TIME=0
	# The maximum time, in seconds, any audit class is allowed to take.
	# 0 means "for ever". Override with -T option

VARBASE="/var/tmp/s-audit"
    # The directory we keep our data in

LOCKFILE="${VARBASE}/s-audit.lock"
	# Lock file, to stop us running multiple instances

RUN_HERE=1
	# run checks in this zone, unless told otherwise

Z_OPTS="-CF"
	# options passed to script when it is run via zlogin

# Set a big PATH. This should try to include everywhere we expect to find
# software. SPATH is for quicker searching later.

SPATH="/bin /usr/bin /usr/sbin /usr/lib/ssh /usr/xpg6/bin /usr/sun/bin /etc \
	$(find /usr/*/*bin /usr/local/*/*bin /opt/*/*bin /usr/*/libexec \
	/usr/local/*/libexec /opt/*/libexec /usr/postgres/*/bin \
	/opt/local/*/bin /*/bin -prune \
	2>/dev/null) /usr/netscape/suitespot /opt/VirtualBox"

PATH=$(print "$SPATH" | tr " " : | tr "\n" ":")

# Get host information and cache it to save us running uname all over. Also
# strip the dot out of OSVER so we can use it in arithmetic comparisons.
# (And shorten it for 2.5.1.)

uname -pnrvim | read HOSTNAME OSVER KERNVER HW_PLAT HW_CHIP HW_HW
OSVERCMP=$(print $OSVER | tr -d .)

[[ $OSVERCMP == "251" ]] && OSVERCMP="25"

IPL_DIRS="/opt/SUNWwbsvr /opt/oracle/webserver7 /usr/netscape/suitespot \
	/sun/webserver7"
	# Directories where we look for iPlanet software

EXTRAS="/etc/s-audit_extras"
	# default path for extras file

# Paths to special tools

SCADM=/usr/platform/${HW_HW}/sbin/scadm

[[ -x /usr/sbin/prtdiag ]] \
	&& PRTDIAG=/usr/sbin/prtdiag \
	|| PRTDIAG=/usr/platform/${HW_HW}/sbin/prtdiag

IGNOREFS=" dev devfs ctfs mntfs sharefs tmpfs fd objfs proc lxproc "
	# Ignore these fs types in the fs audit

# On Nexenta, egrep is really ggrep, and the two have different flags.

egrep --version >/dev/null 2>&1 && EGS="ggrep -q" || EGS="egrep -s"

# DLADM STUFF. This is to handle the ever-changing way in which dladm is
# called. The commands are hard quoted because they're run through eval,
# with the variables filled in then.

# The first block is for "modern" Crossbow, the second for an older revision
# that had different output, the third for old Solaris 10.

[[ -f /usr/sbin/dladm ]] && HAS_DL=1

if [[ -n $HAS_DL ]] && dladm 2>&1 | $EGS "show-ether.*-o"
then

	if dladm show-link -p >/dev/null 2>&1
	then
		DL_DEVLIST_CMD='show-link -p | cut -d\" -f2'
		DL_TYPE_CMD='show-link -p $nic | cut -d\" -f4'
		DL_LINK_CMD='show-link -p $nic | cut -d\" -f8'
		DL_SPEED_CMD='show-ether -p $nic | cut -d\" -f10'
	else
		DL_DEVLIST_CMD='show-link -po link'
		DL_TYPE_CMD='show-link -po class $nic'
		DL_LINK_CMD='show-link -po state $nic'
		DL_SPEED_CMD='show-ether -pospeed-duplex $nic'
	fi

	DL_AO_CMD='show-link $nic -po over | tr " " ,'
	DL_AP_CMD='show-aggr -po policy,addrpolicy $a'
	DL_AM_CMD='show-aggr -xpo address $a | sed 1q'
	DL_ZONE_CMD='show-linkprop -c -pzone -o value $nic |
	sed "s/^.*=\"\([^\"]*\)\"/\1/"'

elif [[ -n $HAS_DL ]]
then
	DL_DEVLIST_CMD='show-link -p | cut -d" " -f1'
	DL_TYPE_CMD='show-link -p $nic | sed "s/^.*type=\([^ ]*\).*$/\1/"'
	DL_LINK_CMD='show-dev -p $nic | sed "s/^.*link=\([^ ]*\).*$/\1/"'
	DL_ZONE_CMD='show-linkprop -c $nic |
	sed -n "/Y=\"zone\"/s/^.*VALUE=\"\([^\"]*\).*$/\1/p"'
	DL_SPEED_CMD='show-dev -p $nic |
	sed "s/^.*speed=\([^ ]*\).*duplex=\(.*\)$/\1Mb:\2/"'
	DL_AO_CMD='show-aggr -p $a | cut -d= -f3 | sed "1d;s/ address//"
	| tr "\n" ,'
	DL_AP_CMD='show-aggr -p $a |
	sed -n "1s/^.*icy=\([^ ]*\) .*type=\(.*\)$/\1:\2/p"'
	DL_AM_CMD='show-aggr -p $a | sed -n "1s/^.*address=\([^ ]*\) .*$/\1/p"'
fi
#-- TEST LISTS ---------------------------------------------------------------

CL_LS=" platform os net fs app tool hosted security patch "
	# All the classes we support

# Here we define the lists of tests which make up the various audits. These
# lists are always bookended by "hostname" and "time".

G_PLATFORM_TESTS="hardware virtualization cpus memory sn obp lom disks
	optical lux_enclosures tape_drives mpath cards printers eeprom"
L_PLATFORM_TESTS="virtualization printers"

G_NET_TESTS="ntp name_service dns_serv domainname name_server nfs_domain
	snmp ports routes rt_fwd net"
L_NET_TESTS="name_service dns_serv domainname name_server snmp ports routes
	rt_fwd net"

G_OS_TESTS="os_dist os_ver os_rel kernel be hostid local_zone ldoms xvmdoms
	vboxes scheduler svc_count package_count patch_count pkg_repo uptime
	timezone"
L_OS_TESTS="os_dist os_ver os_rel kernel be hostid svc_count package_count
	patch_count pkg_repo uptime timezone"

L_APP_TESTS="apache coldfusion tomcat glassfish iplanet_web nginx squid
    mysql_s ora_s postgres_s mongodb_s redis_s svnserve sendmail exim
    cronolog mailman splunk sshd named ssp symon samba x vbox smc ai_srv
    networker_c chef_client puppet cfengine collectd"
G_APP_TESTS="powermt vxvm vxfs scs vcs ldm $L_APP_TESTS nb_c networker_s
	nb_s"

L_TOOL_TESTS="openssl rsync mysql_c postgres_c sqlplus svn_c git_c redis_c
    java perl php_cmd python ruby node cc gcc pca nettracker saudit scat
    explorer jass jet"
G_TOOL_TESTS="sccli sneep vts $L_TOOL_TESTS"

G_HOSTED_TESTS="site_apache site_iplanet db_mysql ai"
L_HOSTED_TESTS=$G_HOSTED_TESTS

G_PATCH_TESTS="patch_list package_list"
L_PATCH_TESTS=$G_PATCH_TESTS

G_SECURITY_TESTS="users uid_0 empty_passwd authorized_keys ssh_root
	RBAC root_shell dtlogin cron jass_appl"
L_SECURITY_TESTS=$G_SECURITY_TESTS

G_FS_TESTS="zpools vx_dgs metasets capacity root_fs fs exports"
L_FS_TESTS="zpools root_fs fs exports"

#-----------------------------------------------------------------------------
# FUNCTIONS

#-- GENERAL FUNCTIONS --------------------------------------------------------

function flush_json
{
	# JSON strings are built up in global variable J_DAT[], by disp() and
	# printed here once we start on a new key. So, with JSON output you're
	# always printing one check behind what's being tested. We have to
	# escape soft quotes, and replace literal tabs in here

	J_DAT=$(print $J_DAT | sed 's/"/\\"/g;s/	/\\t/g') # literal tab!

	if [[ -n $J_DAT && ${#J_DAT[@]} == 1 ]]
	then
		print "    \"$LAST_KEY\": \"$J_DAT\","
	else
		print "    \"$LAST_KEY\": ["
		typeset -i i=0
		rows=$(( ${#J_DAT[@]} - 1))

		while (( $i < $rows))
		do
	    	print "      \"${J_DAT[$i]}\","
		    (( i=i+1 ))
		done

		print "      \"${J_DAT[$rows]}\"\n    ],"
	fi

	J_C=0
	unset J_DAT
}

function disp
{
	# Print information in plain, machine-parseable or JSON form.
	# If SHOW_PATH is set, the path to the binary is printed, if it was
	# passed

	# $1 is the name of the thing, e.g. MySQL or the OBP @PATH
    # remaining arguments are the version strings

	# Remember the first argument, then do a shift and operate with all
	# remaining args -- that way we don't have to quote the second arg,
	# which can be a PITA in some cases.

	typeset key pth val sp

	key=${1%%@*}
	[[ $1 == *@/* ]] && pth=${1##*@}
    shift
	val=$*

	[[ -z $val ]] && return

	# Some things like patches or memory have multiple values, and for JSON
	# we need to group those together. Store the key we last saw in LAST_KEY
	# and the values so far in an array called J_DAT. We need to escape soft
	# quotes in JSON

	if [[ -n $OUT_J ]]
	then
		if [[ $key == "audit completed" ]]
		then
			flush_json
			print -n "    \"${key}\": \"${val}$sp\"\n"
			return
		fi

		[[ -n $SHOW_PATH && -n $pth ]] && sp="@=$pth"
		[[ -n $LAST_KEY && $key != $LAST_KEY ]] && flush_json

		J_DAT[$J_C]="${val}$sp"
		J_C=$(($J_C+1))
		LAST_KEY=$key
	elif [[ -n $OUT_P ]]
	then
		[[ -n $SHOW_PATH && -n $pth ]] && sp="@=$pth"
		print "${key}=${val}$sp"
	else
		[[ -n $SHOW_PATH && -n $pth ]] && sp=" [@$pth]"
		RKEY=$key
		print "$RKEY : ${val}$sp"
	fi
}

function find_bins
{
	# Searches the entire PATH for a list of unique binaries with the
	# name(s) given by the supplied args. Links are expanded and target
	# inodes compared, so only unique programs are found. When multiple
	# paths to the same file are found, the shortest is displayed

	typeset -i i=3

	for prog in $*
	do

		for d in $SPATH
		do
			PATH=$d
			whence $prog
		done

	done | while read pth
	do
		tgt=$pth
		tp=$pth

		while [[ -L $tp && $i -gt 0 ]]
		do
			LNK=$(ls -l $tp)
			LNKT=${LNK##* }
			[[ $LNKT != /* ]] && LNKT=${tp%/*}/$LNKT
			tgt=$LNKT
			tp=$tgt
			i=$(($i + 1))
		done

		ls -i $tgt | \
		sed "s|^\(.\)[$WSP]*\([0-9]*\)[$WSP]*\($tgt\)$|${#pth} $pth \2|"
	done | sort -n -k 3 | uniq -f2 | cut -d\  -f2
}

function find_config
{
	# This function helps you find configuration files which may be in
	# different places. It will report back the first file it finds

	# $1 is the filename to find
	# $2 is a list of directories to try - QUOTE IT! /etc is always searched
	# $3 is an optional process name

	for d in /etc $2
	do

		if [[ -f "${d}/$1" ]]
		then
			print ${d}/$1
			return
		fi

	done

	# Can't see it in any directories. If we've got $3, look for it in the
	# process table. Only root can do this, and then only if we've got the
	# ptools

	if [[ -n $3 ]] && is_root && can_has pargs && pgrep -x $3>/dev/null
	then
		F=$(pargs $(pgrep -x $3) | sed -n "1d;/$1$/s/^.* //p")
		[[ -f $F ]] && print $F
	fi
}

function is_root
{
	# Are we running as root? scadm appears to do some simplistic check that
	# you can't get around by granting a privilege, so we MUST be root.
	# Cached in IS_ROOT variable

	if [[ -n $IS_ROOT ]]
	then
		RET=$IS_ROOT
	else
		[[ $(id) == "uid=0(root)"* ]] && RET=0 || RET=1
		IS_ROOT=$RET
	fi

	return $RET
}

function log
{
    # Shorthand wrapper to logger, so we are guaranteed a consistent message
    # format

    # $1 is the message
    # $2 is the syslog level. If not supplied, defaults to info

	[[ -n $SYSLOG ]] && logger -p ${SYSLOG}.${2:-info} "${0##*/}: $1"
}

function die
{
	# Print a message to stderr, log it, and exit.
	# $1 is the message to print
	# $2 is an optional exit code, defaults to 1
	# $3 - if set, don't clean up

	print -u2 "ERROR: $1"
	log "$1" err
	[[ -z $3 ]] && clean_up
	exit ${2:-1}
}

function msg
{
	# Print a message to stderr and log it
	# $1 is the message
	# $2 is optional syslog level (default is notice)

	[[ x$2 == xwarn ]] && print -u2 -n "WARNING: "

	print -u2 "$1"
	log "$1" ${2:-notice}
}

function kill_children
{
	# $1 is the process whose children we want to kill.

	[[ -z $1 ]] && return

	# If we have access to ptree, individually kill everyting in the PID's
	# process tree. This might seem overkill (pun intended) but some things
	# (like sneep) don't always terminate properly. On Solaris < 8, get a
	# list of PIDs whose PPID is $1

	can_has ptree \
		&& pl=$(ptree $1 | sed -n "/ $1 /,\$s/^ *\([0-9]*\).*$/\1/p" \
			| sed 1d) \
		|| pl=$(ps -e -o pid,ppid | sed -n "/ $1$/s/^ *\([0-9]*\).*$/\1/p")

	[[ -n $pl ]] && kill $pl 2>/dev/null
}

function clean_up
{
	# clean up lock file, output directory, and temporary copies of ourself.
	# Kill any backgrounded child processes

	rm -f $LOCKFILE
	[[ -n $OD ]] && rm -fr ${OD}/$HOSTNAME
	[[ -n $zf && -f $zf ]] && rm $zf

	kill_children $$
}

function can_has
{
	# simple wrapper to whence, because I got tired of typing >/dev/null
	# $1 is the file to check

	whence $1 >/dev/null && return 0 || return 1
}

function my_pgrep
{
	# -o tells pgrep to print only the oldest matched process. If it's
	# available, use it. If it's not, emulate its behaviour by printing the
	# oldest matched PID, or returning 1. Cache the pgrep to use in the
	# USE_PGREP var. Pre 7 systems don't even have pgrep
	# USE_PGREP=1 means use pgrep -o
	# USE_PGREP=2 means use pgrep | sed
	# USE_PGREP=3 means use ps | sed

	# $1 is the command to pgrep for
	# $2 is set if you want to return ALL PIDs. Normally it just returns one

	typeset out

	if [[ -z $USE_PGREP ]]
	then

		if pgrep -o init >/dev/null 2>&1
		then
			USE_PGREP=1
		elif can_has pgrep
		then
			USE_PGREP=2
		else
			USE_PGREP=3
		fi

	fi

	# If we're using ps | sed, we have to escape backslashes

	(($USE_PGREP == 3)) && srch=$(print $1 | sed 's|/|\\/|g')

	if [[ $USE_PGREP -lt 3 && -n $2 ]]
	then
		out=$(pgrep $Z_FLAG -f)
	elif (($USE_PGREP == 1))
	then
		out=$(pgrep $Z_FLAG -f -o $1)
	elif (($USE_PGREP == 2))
	then
		out=$(pgrep -f $Z_FLAG $1 | sed 1q)
	elif [[ -z $2 ]]
	then
		out=$(ps -ef -opid,comm | sed -n "/$srch/s/^ *\([0-9]*\).*$/\1/p")
	else
		ps -ef -opid,comm | sed -n "/[ \/]$srch\$/{p;q;}" | read out j
	fi

	[[ -n $out ]] && print $out || return 1
}

function is_running
{
	# simple wrapper to pgrep, to see if something is running
	# $1 is the process name to look for

	my_pgrep ${1##*/} >/dev/null && return 0 || return 1
}

function is_run_ver
{
	# Nicely handles running processes for which we can't get a version, and
	# non-running programs whose version we can ascertain. If it's not
	# running and we can't get a version, we assume it's not there

	# $1 is the printable name of the service
	# $2 is the binary pattern to look up in the process table
	# $3 is the version string to display

	typeset RUN_STR VER_STR

	if [[ x$3 == xnone ]]
	then
		VER_STR=""
	elif [[ -n $3 ]]
	then
		is_running $2 || RUN_STR="(not running)"
		VER_STR=$3
	else
		is_running $2 && VER_STR="unknown version"
	fi

	disp "$1" $VER_STR $RUN_STR
}

function timeout_job
{
	# Run a job for a maximum specific length of time. If the job does not
	# complete in $T_MAX seconds, it is terminated,
	# $1 is the job to run -- quote it!
	# $2 is an optional timeout, in seconds

	typeset clear count=${2:-$T_MAX}

	$1 &
	BGP=$!

	while (($count > 1))
	do
		# If there are no backgrounded jobs, exit

		if [[ -z $(jobs) ]]
		then
			clear=1
			break
		fi

		count=$(($count - 1))
		sleep 1
	done

	# Kill all the children of the backgrounded job, then the job itself.
	# Return true if the job isn't in the process table

	if ps -p $BGP >/dev/null
	then
		kill_children $BGP
		kill $BPG 2>/dev/null
	else
		return 0
	fi
}

function expand_suff
{
	# Expand a suffix like M, G or T. We need to do floating point
	# arithmetic, which means an external command
	# $1 is the number

	typeset num=${1%%[A-Za-z]*}
	typeset -u sfx=${1#$num}
	typeset -i e=0

	if [[ $sfx == "K" ]]
	then
		e=1
	elif [[ $sfx == "M" ]]
	then
		e=2
	elif [[ $sfx == "G" ]]
	then
		e=3
	elif [[ $sfx == "T" ]]
	then
		e=4
	fi

	print "scale=2;$num * 1024 ^ $e" | bc
}

function num_suff
{
	# Take a raw number and break it down with a k/M/G/T suffix. Opposite of
	# expand_suff
	# $1 is the number. Must be an integer, but can't typeset -i on ksh93

	typeset num=${1%%.*}
	typeset -i l=${#num}

	if (($l < 3))
	then
		e="0:"
	elif (($l < 9))
	then
		e="1:k"
	elif (($l < 10))
	then
		e="2:M"
	elif (($l < 12))
	then
		e="3:G"
	else
		e="4:T"
	fi

	print -- $(print "scale=1;$1 / 1024 ^ ${e%:*}" | bc)${e#*:}
}

function class_head
{
	# Print the preamble to a class audit.
	# $1 is the zone
	# $2 is the class

	if [[ -n $OUT_J ]]
	then
		unset LAST_KEY
		print "  \"$2\": {"
	elif [[ -n $OUT_P ]]
	then
		print "BEGIN $2@$1"
	elif [[ -z $TO_FILE ]]
	then
		tput bold 2>/dev/null
		print "\n'$2' audit on $1\n"
		tput sgr0 2>/dev/null
	fi
}

function class_foot
{
	# Print the information that goes after a class audit
	# $1 is the zone
	# $2 is the class

	if [[ -n $OUT_J ]]
	then
		[[ $2 != ${CL##* } ]] && print "  }," || print "  }"
	elif [[ -n $OUT_P ]]
	then
		print "END $2@$1"
	elif [[ -z $TO_FILE ]]
	then
		print "\n$(prt_bar)"
	fi
}

function run_class
{
	# Run all the tests belonging to a class
	# $1 is the class to run

	typeset -u cl=$1

	is_global && TPFX="G" || TPFX="L"
	eval TESTS='$'"${TPFX}_${cl}_TESTS"

	RUN_TESTS="hostname $TESTS extras time"

	for get in $RUN_TESTS
	do
		[[ -n $VERBOSE ]] && print -u2 "  running '$get'"
		[[ $OMIT_TESTS == *" $get "* ]] || get_$get
	done
}

function prt_bar
{
	cat <<-EOSEP
------------------------------------------------------------------------------

	EOSEP
}

function nr_warn
{
	# Warnings for checks not run as a non-root user
	# $1 is the audit class

	if [[ $1 == "platform" ]]
	then
		print "Many tests, including LOM, FC enclosure, virtualization \
		will not be run"
	elif [[ $1 == "net" ]]
	then
		print "NIC and routing/forwarding information will be limited"
	elif [[ $1 == "os" ]]
	then
		print "There will be no zone type information, and LDOM \
		information may be limited"
	elif [[ $1 == "security" ]]
	then
		print "There will be no information on blank passwords, authorized \
		keys or cron jobs, and poorer identification of open ports."
	elif [[ $1 == "app" ]]
	then
		print "There may be no information on loaded Apache modules, \
		Tomcat information may be incomplete, and Veritas and \
		SunONE/iPlanet applications may not be audited."
	elif [[ $1 == "tool" ]]
	then
		print "If sccli is installed it will not be audited"
	fi
}

function show_checks
{
	# List what we can do

	typeset -u cn

	for cl in platform os net app tool hosted fs patch security
	do
		cn=$cl
		print "\nGlobal zone '$cl' audit tests"
		eval print '$'"G_${cn}_TESTS" | fold -sw 65 | sed "s/^/  /"
		print "\nLocal zone '$cl' audit tests"
		eval print '$'"L_${cn}_TESTS" | fold -sw 65 | sed "s/^/  /"
	done
}

function is_global
{
	# Are we running in the global zone? True if we are, false if we're not.
	# True also if we're on something that doesn't know about zones. Caches
	# the value in the IS_GLOBAL variable. If init has pid 1, we're in a
	# global zone. If not, we're not. init is in /sbin as of 5.10

	RET=0

	if [[ -n $IS_GLOBAL ]]
	then
		RET=$IS_GLOBAL
	else
		(($OSVERCMP > 59)) && ID="sbin" || ID="etc"
		[[ x$(my_pgrep "/${ID}/init") == x1 ]] || RET=1
		IS_GLOBAL=$RET
	fi

	return $RET
}

function get_disk_type
{
	# Try to work out what type of disk (IDE, SCSI, USB etc.) a given cxtx
	# device is. PowerPath disks aren't in /dev/dsk

	if [[ -n $PPDSKS ]] && [[ $PPDSKS == *" ${1}s"* ]]
	then
		print "PowerPath"
		return
	fi

	if [[ ! -a /dev/dsk/${1}s2 ]]
	then
		print "untraceable"
		return
	fi

	s=$(ls -l /dev/dsk/${1}s2)

	if [[ $s == *ide@* ]]
	then
		print "ATA"
	elif [[ $s == *scsi_vhci* ]] # Can also be iSCSI on 5.11
	then

		iostat -En | sed -n "/$1/{n;p;}" | $EGS COMSTAR \
			&& print "COMSTAR iSCSI" \
			|| print "SCSI VHCI"

	elif [[ $s == *"lpfc"* ]]
	then
		print "Emulex SAN"
	elif [[ $s == *scsi@* || $s == *esp@* ]]
	then
		print "SCSI"
	elif [[ $s == *sbus@* ]]
	then
		print "SBUS/SCSI"
	elif [[ $s == *sas@* ]]
	then
		print "SAS"
	elif [[ $s == *device@* || $s == *storage@* ]] # not sure this is 100% right
	then
		print "USB"
	elif [[ $s == */iscsi/* ]]
	then
		print "iSCSI"
	elif [[ $s == *virtual-devices@* ]]
	then
		print "virtual"
	elif (($OSVERCMP >= 510)) && iostat -En $dev | $EGS "VBOX"
	then
		print "VBOX"
	elif [[ $s == */xpvd/* ]]
	then
		print "xVM"
	else
		print "unknown"
	fi
}

usage()
{
	cat<<-EOUSAGE
	Usage: ${0##*/} [-f [dir]] [-z zone,...|all] [-qjpPMFlV] [-D sec] [T sec]
	       [-L facility] [-o test,..] [-R user@host:dir ] [-e file] audit_type

	where
	  -f   write files to an (optionally) supplied local directory.
	  -z   specify zone(s) to audit. Comma-separated list, or 'all' to run
	       audit on all running zones
	  -M   in network audits, plumb and unplumb uncabled NICs to obtain MAC
	       address (potentially destructive!)
	  -o   omit tests (comma-separated list)
	  -j   write output in JSON format
	  -p   write machine-parseable output
	  -P   print paths to tools and applications. (Implied by -f.)
	  -q   be quiet
	  -R   string used to scp audit files to remote host. 'user@host:directory'
	  -D   delay this many seconds before beginning the audit
	  -e   full path to "extras" file
	  -L   syslog facility - must be lower case
	  -T   maximum time, in s, for any audit class
	  -v   be verbose
	  -Z   if parseable or JSON file is written, compress it (requires gzip)
	  -F   if lock file exists, ignore and remove it
	  -l   list tests in each audit type
	  -V   print version and exit

	The audit_type argument tells the script which of the following audits
	to perform.

    platform : describes the physical or virtual machine
         net : shows network devices, connections and configuration
          os : shows the OS and virtualizations
         app : reports paths and versions of selected application software
        tool : reports paths and versions of tools and programming languages
      hosted : looks at some DB and websites running on the host
          fs : local and remote filesystem information
       patch : lists installed patches and packages
    security : shows some potential security issues, cron jobs, and  RBAC

     machine : all of the above, for all zones
         all : all audit types for the current zone

	EOUSAGE
	exit 2
}

#-- GENERAL AUDITING FUNCTIONS -----------------------------------------------

function get_hostname
{
	disp "hostname" $HOSTNAME
}

function get_extras
{
	# Looks at the extras file and inserts data into the audit

	if [[ -n $EXTRAS ]]
	then

		grep "^${class}$WSP" $EXTRAS | while read cl k v
		do
			disp "$k" $v
		done

	fi
}

function get_time
{
	# Just return a nice date/time string

	disp "audit completed" $(date "+%H:%M:%S %d/%m/%y")
}

function get_hostid
{
	# I don't need to comment this, do I?

	disp "hostid" $(hostid 2>/dev/null)
}

function get_printers
{
	# Get a list of the printers this box can use.

	if can_has lpstat
	then
		defp=$(lpstat -d 2>/dev/null | sed 's/^.*: //')

		lpstat -s 2>/dev/null | \
		sed -n '/^system /s/^system for \([^:]*\):.*$/\1/p' | \
		while read pr
		do
			[[ $defp == $pr ]] && e=" (default)" || e=""
			disp "printer" "${pr}$e"
		done
	fi
}

#-- PLATFORM AUDITING FUNCTIONS ----------------------------------------------

function get_hardware
{
	# Get a string which identifies the hardware, and pretty it up a bit.
	# This gives the name of the hardware platform, which doesn't always
	# exactly tally with what's printed on the front of the box.


	can_has isainfo && BITS=$(isainfo -b)

	if [[ $HW_CHIP == "sparc" ]]
	then
		HW_OUT=$($PRTDIAG | sed "1s/^.*$HW_PLAT //;q")
		CH="SPARC"
	else
		HW_OUT=$HW_CHIP
		is_running akd && HW_OUT="${HW_OUT} ZFS appliance"
	fi

	# Is this part of a cluster?

	if can_has cluster
	then
		cnm=" [member of SC $(cluster list 2>/dev/null)]"
	elif can_has scconf
	then
		cnm=" [member of SC $(scconf -p | sed -n '/Cluster name/s/^.* //p')]"
	elif can_has haclus && my_pgrep hashadow
	then
		cnm=" [member of VCS $(haclus -value ClusterName)]"
	fi

	disp hardware "$HW_OUT (${BITS:-32}-bit ${CH:-x86})$cnm"
}

function get_sn
{
	# Get the serial number of the machine, using SNEEP, if it's available

	can_has sneep && SN=$(timeout_job sneep)
	disp "serial number" $SN
}

function get_obp
{
	# Display the OBP version

	OBP_VER=$(prtconf -V)
	OBP_VER=${OBP_VER#* }

	disp "OBP" ${OBP_VER%% *}
}

function get_memory
{
	# Pull the amount of physical memory out of prtconf and get the swap,
	# which is given in 512k blocks. The output is subtly different in
	# Solaris 10, hence the nasty chain of commands

	disp "memory" "$(timeout_job prtconf \
		| sed -n '/Memory*/s/^.*: \([0-9]*\).*$/\1/p')Mb physical"

	# Can have multiple swap devices

	ss=0

	swap -l 2>/dev/null | sed 1d | while read a b c d e
	do
		ss=$(print "$ss + $d" | bc)
	done

	[[ $ss == 0 ]] \
		&& disp "memory" "no swap space" \
		|| disp "memory" "$(num_suff $(print "$ss * 512" | bc))b swap"
}

function get_cpus
{
	# Get CPU information. Solaris 10 has the -p option, and understands the
	# concept of physical and virtual processors. 11 can distinguish cores
	# and threads on T-series

	C0=$(psrinfo | sed 1q | cut -f1)

	if (($OSVERCMP >= 510))
	then
		CPUN=$(psrinfo -p)
		CPUL=$(psrinfo -vp $C0 | sed 1q)

		if [[ $CPUL == *cores* ]]
		then
			print $CPUL | cut -d\  -f 5,8 | read c v
			CPUX="x $c cores ($v virtual)"
		elif [[ $CPUL == *virtual* ]]
		then
			print $CPUL | cut -d\  -f 5,8 | read c v
			CPUX="x $c cores"
		else
			CPUC=$(print $CPUL | sed '/physical/!d;s/^.*has \([0-9]*\).*$/\1/')
			(($CPUC > 1)) && CPUX="x $CPUC virtual"
		fi

	else
		CPUN=$(psrinfo | wc -l)
	fi

	disp "CPU" $CPUN $CPUX @ $(psrinfo -v $C0 \
	| sed -n '/MHz/s/^.*at \([0-9]*\) .*$/\1/p')MHz
}

function get_lom
{
	# Can we get the RSC/ALOM/ILOM/XSCF version? We need to be root to even
	# try, and some machines, like T-series, can't provide it anyway.

	is_root || return

	RSC="/usr/platform/${HW_HW}/rsc/rscadm"
	DSCP="/usr/platform/${HW_HW}/prtdscp"

	if [[ -x $SCADM ]]
	then
		lfw="$($SCADM version | sed -n '/Firmware/s/^.* //p') (ALOM)"
		lip=$($SCADM show netsc_ipaddr | cut -d'"' -f2)
	elif [[ -x $DSCP ]]
	then
		lip="$($DSCP -s) (XSCF DSCP SP)"
	elif [[ -x $RSC ]]
	then
		lfw="$($RSC version | sed -n '/^RSC Ver/s/^.* //p') (RSC)"
		lip=$($RSC shownetwork | sed -n '/^IP/s/^.* //p')
	fi

	[[ -n $lfw ]] && disp "LOM f/w" $lfw
	[[ -n $lip ]] && disp "LOM IP" $lip
}

function get_optical
{
	# Counts CD/DVD drives, identifies their type, and says whether or not
	# they have a disk in. eject -q works differently on 2.6

	typeset -i cdc=0

	iostat -En | sed -e'/^c[0-9]/{N;s/\n/ /;}' -e '/Vend/{N;s/\n/ /;}' \
	| egrep "^c" | egrep "DV|CDR|CD-R|COMBO" | while read dev junk
	do
		line=$(get_disk_type $dev)

		if df 2>/dev/null | $EGS "/dev/dsk/$dev"
		then
			x="(mounted)"
		elif eject -q /dev/dsk/${dev}s2 >/dev/null 2>&1
		then
			x="(loaded)"
		elif (($OSVERCMP > 56))
		then
			x="(empty)"
		fi

		print "$line $x"
	done | sort -n | uniq -c | while read no info
	do
		disp "storage" "CD/DVD: $no x $info"
	done
}

function get_disks
{
	# Get the sizes of the disks on the system, ignoring optical drives.

	# If we're running PowerPath, get a list of all the disks it controls
	# for the use of get_disk_type()

	can_has powermt \
		&& PPDSKS=" $(powermt display dev=all | grep "c[0-9]*t[0-9]" \
		| cut -d\  -f3 | tr "\n" " ") "

	# If we have a vendor line, tag the following "size" line on to it. This
	# makes the lines look similar on SPARC and x86, with or without a
	# Vendor: string.

	typeset -i i=0

	iostat -En | sed -e'/^c[0-9]/{N;s/\n/ /;}' -e '/Revision/{N;s/\n/ /;}' \
		-e '/Size/!d' | egrep -v "DV|CD|Size: 0" \
		| sed 's/^\([^ ]*\).*Size: \([^ ]*\).*$/\1 \2/;s/\.[0-9]*//' | \
	while read dev size
	do
		[[ $dev == c[0-9]* ]] && print "$size $(get_disk_type $dev)"
	done | sort -n | uniq -c | while read no info
	do
		((i = $i + 1))
		disp "storage" "disk: $no x $info"
	done

	# If we can't get anything from iostat, which happens on old intel
	# Solarises and domUs, fall back to format(1). I don't think you can get
	# the disk size. On a vbox, only the CD-ROM shows up

	if (($i == 0))
	then
		print | format 2>&1 | sed -e'/ cyl /{N;s/\n/ /;}' -e '/ cyl /!d' \
		| while read no dev junk
		do
			get_disk_type $dev
		done | sort -n | uniq -c | while read no info
		do
			disp "storage" "disk: $no x $info (unknown size)"
		done
	fi

	# If there's raidctl hardware mirroring, things get complicated. I'll
	# just count the volumes. First line is 5.10, second 5.9

	if can_has raidctl
	then

		raidctl 2>&1 | $EGS ^Controller \
			&& v=$(raidctl -l 2>/dev/null | grep -c Volume:) \
			|| v=$(raidctl 2>/dev/null | grep -c ^c)

		(($v > 0)) && disp storage "RAID vol: $v"
	fi

}

function get_eeprom
{
	# Get selected EEPROM data and device aliases

	eeprom | egrep \
	"scsi-initiator|use-nvramrc|diag-level|auto-boot|boot-device|local-mac" | \
	while read e
	do
		disp "EEPROM" $e
	done

	eeprom | grep devalias | while read d
	do
		disp "EEPROM" "devalias ${d#*devalias }"
	done
}

function get_lux_enclosures
{
	# Prints a string of the form "Vendor Product-ID (fw REV)" where REV is
	# the firmware revision, for each FC attached device that luxadm can
	# find.

	is_root || return

	# Get unique node WWNs, which should identify each attached device.

	luxadm probe 2>/dev/null \
		| sed -n '/Node /s/^.*WWN:\([^ ]*\).*$/\1/p' \
		| while read node
	do

		if luxadm display $node | $EGS Vendor:
		then

			# Get the vendor, moedl ID and evice type. Take the first match
			# of each. Sloooow

			luxadm display $node | grep Vendor: | sed 1q | read a vnd
			luxadm display $node | grep Type: | sed 1q | read a b typ
			luxadm display $node | grep ID: | sed 1q | read a b id

			#luxadm display $node | sed -n -e "/Vendor/s/^.*:[$WSP]*//p" \
				#-e "/Product/s/^.*:[$WSP]*//p" \
				#-e "/Revision/{s/^.*:[$WSP]*\(.*\)/(fw \1)/p;q;}" \
				#| tr '\n' ' ' | tr -s ' '
			#print
			print "$vnd $id ($typ)"
		else
			print "unidentified (WWN ${node})"
		fi

	done | sort | uniq -c | while read COUNT LUX
	do
		disp "storage" "FC device : $COUNT x $LUX"
	done

}

function get_tape_drives
{
	# Report on connected tape drives as best we can. We count them through
	# cfgadm, then try to query them with mt(1). Note, this isn't 100%
	# bullet proof. I've seen "phantom" tape drives reappear on Solaris
	# systems long after they've been swapped for new ones.

	# There's a rough fallback to scanning /dev/rmt for machines without
	# cfgadm. The cfgadm check is necessary for LDOMs - you can't run it in
	# those. (Currently.)

	if can_has cfgadm && cfgadm >/dev/null 2>&1
	then
		TPLST=$(cfgadm 2>/dev/null -al | \
		sed -n "/tape.*connected/s/^.*\(rmt\/[^ ]\).*$/\/dev\/\1/p")
	else
		TPLST=$(ls /dev/rmt/* 2>/dev/null | sed 's/[a-z]*$//' | sort -u)
	fi

	for dev in $TPLST
	do
		mt -f $dev config 2>/dev/null || print '"", "unknown tape drive"'
	done | egrep "^\"" | cut -d\" -f4 \
	| sort | uniq -c | while read COUNT DRIVE
	do
		disp "storage" "tape: $COUNT x $DRIVE"
	done
}

function get_mpath
{
	# Are we multipathing? Currently we can only do native Solaris and EMC
	# PowerPath

	if is_root && can_has mpathadm
	then

		mpathadm list LU | grep Total | sort | uniq -c | while read num pths
		do
			disp "multipath" "$num mpxio devices (${pths##* } paths)"
		done

	fi

	if is_root && can_has powermt
	then

		powermt display hba_mode | sed -n \
		'/=[0-9]\{1,\}$/s/^\([^=]*\)=\([0-9]*\)$/\2 \1/;s/ log.*$//p' | \
		while read num typ
		do

			[[ $num != 0 ]] \
				&& disp "multipath" "powerpath ($num $typ devices)"

		done

	fi
}

function get_cards
{
	# Have a go at finding cards

	if [[ -x $PRTDIAG ]]
	then
		# SBUS first. Works on the Ultra 2, YMMV.  I'm not very confident
		# about only checking below slot 14

		if [[ $HW_HW != "i86"* ]]
		then

			$PRTDIAG | awk '{ if ($2 == "SBus" && $4 < 14) print $4,$5 }' | \
			sort -u | while read slot type
			do
				disp "card" "$type (SBUS slot $slot)"
			done

		fi

		# Get PCI information from prtdiag. We don't process it any more -
		# it was getting out of hand as every machine presents the info
		# differently. The PHP interface knows lots of machines though.
		# Compress whitespace if we're not machine parseable

		[[ -n $OUT_P ]] && x_sed='' || x_sed="s/[${WSP}]\{1,\}/ /g"

		$PRTDIAG -v 2>/dev/null | grep -i pci | egrep -v "^[$WSP]+/pci@" \
		| sed "$x_sed" | while read line
		do
			disp "card" "PCI ($line)"
		done

	fi
}

function get_virtualization
{
	# Get the virtualization, if any, that's going on here. First we do the
	# "machine level" virtualization -- xVM, VirtualBox, LDOM etc. Almost
	# none of this can be done without being root so we'll skip the check if
	# we aren't

	is_root || return
	typeset VIRT="none"

	if is_global
	then

		if [[ $HW_HW == "i86xpv" ]] && prtconf | $EGS xpvd,
		then
			is_running xenconsoled && VIRT="xVM dom0" || VIRT="xVM domU"
		elif [[ $HW_HW == "i86pc" ]]
		then
			# Are we a KVM guest?

			if $PRTDIAG 2>/dev/null | $EGS Bochs
			then
				VIRT="KVM guest"

			# Prtdiag is not supported on x86 Solaris < 10u2. At the moment
			# I can't work out a bulletproof way to tell whether those old
			# OSes are running on a physical machine or in a virtualbox

			elif (($OSVERCMP > 59)) && $PRTDIAG >/dev/null 2>&1
			then
				sc=$($PRTDIAG 2>/dev/null | sed 1q)

				if [[ $sc == *VirtualBox* ]]
				then
					VIRT="VirtualBox"
				elif [[ $sc == *VMware* ]]
				then
					VIRT="VMware"
				fi

			else
				# Running out of options now. Look to see if the string VBOX
				# is in the iostat output. It will be if there's a CD-ROM
				# device. You can do the same trick for VMWare

				if iostat -E | $EGS VBOX
				then
					VIRT="VirtualBox"
				elif iostat -E | $EGS VMware
				then
					VIRT="VMware"
				else
					VIRT="undetermined"
				fi

			fi

			# Get the versions of VMWare tools or VirtualBox additions

			if [[ $VIRT == "VirtualBox" ]]
			then
				VBGVER=$(pkginfo -l SUNWvboxguest 2>/dev/null | sed -n \
				'/VERSION/s/VERSION:.* \([^,]*\).*$/\1/p')

				[[ -n $VBGVER ]] && VIRT="${VIRT} (guest add. $VBGVER)"
			elif [[ $VIRT == "VMware" ]]
			then
				can_has vmware-toolbox-cmd && \
					VIRT="$VIRT (tools $(vmware-toolbox-cmd -v \
					| sed 's/ .*//'))"
			fi

		elif [[ $HW_PLAT == "sun4v" ]]
		then

			# Now we look at logical domains. There's only any point doing
			# this if we're on sun4v hardware. If we have the ldm binary,
			# and that can list the primary, then we must be in the primary
			# domain. If we can't see the power supply, assume we're a guest
			# LDOM

			if can_has virtinfo
			then

				virtinfo | $EGS guest \
					&& VIRT="guest LDOM" \
					|| VIRT="primary LDOM"

			elif can_has ldm && is_root && ldm ls primary >/dev/null 2>&1
			then
				VIRT="primary LDOM"
			elif ! $PRTDIAG -v | $EGS "PS0"
			then
				VIRT="guest LDOM"
			fi

		else
			modinfo | $EGS drmach && VIRT="hardware domain"
		fi

		can_has zonename && VIRT="$VIRT (global zone)"
	else
		# We're not a global zone. We could be in a branded zone. They have
		# to be whole root

		if (($OSVERCMP == 58))
		then
			VIRT="zone (whole root/solaris8)"
		elif (($OSVERCMP == 59))
		then
			VIRT="zone (whole root/solaris9)"
		else

			# Find out whether the zone is whole root or sparse

			TFILE="/usr/tfile$$"
			touch $TFILE 2>/dev/null \
				&& ZI="whole root" || ZI="sparse"

			rm -f $TFILE

			# See if we're a brand other than native. If we don't have
			# zoneadm, go with the OS revision

			if can_has zoneadm
			then
				br=$(zoneadm list -cp | cut -d: -f6)
				VIRT="zone (${ZI}/${br:-native})"
			else
				VIRT="zone (${ZI}/$OSVER)"
			fi

		fi

	fi

	disp "virtualization" $VIRT
}

#-- O/S AUDITING FUNCTIONS ---------------------------------------------------

function get_kernel
{
	disp "kernel" ${KERNVER#*_}
}

function get_uptime
{
	# I used to use a kstat call here, which worked beautifully on 5.10, but
	# broke with 5.11 zones, and with non-GMT timezones. So we try to use
	# uptime(1), which is a horrible, inconsistent interface. It changes
	# format, and, if you query it inside a minute of a reboot, doesn't even
	# report a time at all!

	ut=$(uptime | sed 's/^.* up \([^,]*\).*$/\1/')

	[[ $ut == *user* ]] && ut="unknown"

	disp uptime $ut
}

function get_timezone
{
	disp timezone $(date "+%Z")
}

function get_xvmdoms
{

	# A list of xVM domians, with their state, CPU and memory

	if is_root && can_has virsh
	then

		virsh list 2>/dev/null | sed '1,2d;/^$/d' | while read id nm st
		do
			print $(virsh dominfo $nm | egrep "^CPU\(|^Max|^OS" | \
			sed 's/^.*: *//') | read o c m

			disp VM "xVM: $nm (${o}:$st) [$c CPU/$m]"
		done

	fi

}

function get_vboxes
{
	if is_root && can_has vboxshell.py
	then

		VBoxManage list vms | cut -d\" -f2 | while read vb
		do
			st=$(VBoxManage list -l vms | egrep  "^State|^Name" \
			| sed 's/^[^:]*: *//;s/ (since.*$//' | sed -n "/$vb/{n;p;}")

			print $(print "info '$vb'" | vboxshell.py \
			| egrep " CPUs | RAM | OS Type" | sed '1!G;h;$!d' \
			| sed 's/^.*: //') | read m c os

			disp VM "VBox: $vb (${os}:$st) [$c CPU/$m]"
		done

	fi
}

function get_os_dist
{
	# Try to get the "distribution". Could be "proper" Solaris, SXCE,
	# OpenSolaris, BeleniX, whatever. This needs to be run before the other
	# get_os functions

	# Anything prior to 5.11 is standard Solaris

	r="/etc/release"

	if (($OSVERCMP < 511))
	then
		OS_D="Solaris"
	elif $EGS "illumian" $r
	then
		OS_D="Illumian"
	elif $EGS "SmartOS" $r
	then
		OS_D="SmartOS"
	elif $EGS Nevada $r
	then
		OS_D="Nevada"
	elif $EGS Community $r
	then
		OS_D="SXCE"
	elif [[ $KERNVER == omni* ]]
	then
		OS_D="OmniOS"
	elif [[ $KERNVER == Belen* ]]
	then
		OS_D="BeleniX"
	elif [[ $KERNVER == Nexenta* ]]
	then
		OS_D="Nexenta"
	elif $EGS OpenSolaris $r
	then
		OS_D="OpenSolaris"
	elif $EGS "Solaris 11 Express" $r
	then
		OS_D="Solaris 11 Express"
	elif $EGS "OpenIndiana" $r
	then
		OS_D="OpenIndiana"
	elif $EGS "^Tribblix" $r
	then
		OS_D="Tribblix"
	elif $EGS "Solaris 11" $r
	then
		OS_D="Oracle Solaris"
	fi 2>/dev/null

	disp "distribution" ${OS_D:-unknown}
}

function get_os_ver
{
	# Get the version of the operating system. Not everything gives itself a
	# version

	if (($OSVERCMP < 511))
	then
		MV=${OSVER#*.}
		(($OSVERCMP < 57)) && MV="2.$MV"
		OS_V="$MV (SunOS $OSVER)"
	elif [[ $OS_D == "Oracle Solaris" ]]
	then
		OS_V="11 (SunOS 5.11)"
	elif [[ $OS_D == "OmniOS" ]]
	then
		OS_V=$(sed '1!d;s/^.*v\([^ ]*\) .*$/\1/' /etc/release)
	elif [[ $OS_D == "Illumian" ]]
	then
		OS_V=$(sed 's/illumian \([^ ]*\).*$/\1/' /etc/issue)
	else
		OS_V="SunOS $OSVER"
	fi

	disp "version" $OS_V
}

function get_os_rel
{
	# Get the release of the operating environment/distribution

	r="/etc/release"

	if [[ $OSVERCMP == "56" ]] && $EGS "Maintenance" $r
	then
		OS_R="Maintenance Update 2"
	elif (($OSVERCMP < 511))
	then
		OS_R=$(sed '1!d;s/^.*Solaris [^ ]* \([^_ ]*\).*$/\1/' $r)
	elif [[ $OS_D == SXCE || $OS_D == "Nevada" ]]
	then
		OS_R=$(sed '1!d;s/^.* \(snv[^ ]*\).*$/\1/' $r)
	elif [[ $OS_D == "Oracle Solaris" ]]
	then
		OS_R=$(sed '1!d;s/^.*is \(.*\) .*/\1/' $r)
		OS_R=${OS_R#11 }
	elif [[ $OS_D == "OpenIndiana" ]]
	then
		OS_R=$(sed '1!d;s/^.*oi_\([^ ]*\).*$/\1/' $r)
	elif [[ $OS_D == "OpenSolaris" ]]
	then
		[[ -f /etc/release ]] \
			&& OS_R=$(sed '1!d;s/^.*aris \([^ ]*\) .*$/\1/' $r) \
			|| OS_R="unknown"
	elif [[ $OS_D == "Illumian" ]]
	then
		OS_R=$(cut -d\( -f2 /etc/issue | tr -d \))
	elif [[ $OS_D == "OmniOS" ]]
	then
		OS_R=$(sed '1!d;s/^.* //' $r)
	elif [[ $OS_D == "SmartOS" ]]
	then
		OS_R=$(sed '1!d;s/^.*OS \([^ ]*\).*$/\1/' $r)
	elif [[ $OS_D == "BeleniX" ]]
	then
		OS_R=$(sed '1!d;s/^.*iX \([^ ]*\) \(.*\)$/\1 \(\2\)/' $r)
	elif [[ $OS_D == "Nexenta" ]]
	then
		OS_R=$(sed '1!d;s/NexentaCore //' /etc/issue)
	fi

	disp "release" $OS_R
}

function get_be
{
	# Get beadm, LiveUpgrade and failsafe boot environments

	if (($OSVERCMP > 510 )) && can_has beadm
	then
		# show the name, root, active flags and mountpoint. There are
		# different beadms, but give them both the same output here

		beadm list 2>/dev/null | sed 1q | $EGS Policy && newbea=1

		beadm list 2>/dev/null | grep "[0-9]" | while read be f2 f3 f4 junk
		do

			if [[ -n $newbea ]]
			then
				fl=$f2
				r=$f3
			else
				[[ $f2 == "yes" ]] && fl=N
				[[ $f3 == "yes" ]] && fl=${fl}R
				r=$f4
			fi

			disp "boot env" "beadm: $be ($r) [$fl]"
		done

	elif is_global && is_root && can_has lustatus
	then

		lustatus 2>/dev/null | sed '1,3d' | while read nm c an ar a
		do
			# Make the active flags match the beadm output

			[[ $an == "yes" ]] && fl=N
			[[ $ar == "yes" ]] && fl=${fl}R
			[[ $c != "yes" ]] && x="in"

			disp "boot env" "LU: $nm (${x}complete) [$fl]"
		done
	fi

	if [[ $OSVERCMP -gt 59 && -d /boot ]]
	then

		[[ -f /boot/grub/menu.lst ]] && GM=/boot/
		[[ -f /rpool/boot/grub/menu.lst ]] && GM="/rpool/boot"

		find /boot -name \*miniroot\* | while read f
		do
			unset i

			grep -v "^#" ${GM}/grub/menu.lst | sed '1!G;h;$!d' | while read l
			do
				if [[ $l == "module $f" ]]
				then
					i=1
				elif [[ -n $i && $l == "title"* ]]
				then
					disp "boot env" "failsafe: '${l#* }' ($f)"
					break;
				fi

			done

		done

	fi
}

function get_scheduler
{
	# Get the default scheduling class, if supported

	if can_has dispadmin && is_global && dispadmin -h 2>&1 | $EGS -- -d
	then
		SCL=$(dispadmin -d 2>&1)
		[[ $SCL != *"class is not set"* ]]  && disp "scheduler"  $SCL
	fi
}

function get_svc_count
{
	# Count services

	if can_has svcs
	then
		m=$(svcs -x | grep -c "State:")
		(( $m > 0 )) && x=", $m in maintenence"

		disp "SMF services" "$(svcs -aH | sed -n '$=') installed ($(svcs -H \
		| sed -n '$=') online${x})"
	fi
}

function get_package_count
{
	# A simple count of installed packages

	can_has dpkg && disp "packages" $(dpkg -l | wc -l) "[dpkg]"

	if can_has pkgin	# SmartOS
	then
		disp "packages" $(pkgin list | wc -l) "[pkgsrc]"
	elif can_has pkg	# Solaris 11
	then
		disp "packages" $(pkg list -Hs | wc -l) "[ipkg]"
	fi

	if can_has pkginfo
	then
		PARTIAL=$(pkginfo -p | wc -l)
		PKGS=$(pkginfo | wc -l)

		(($PARTIAL > 0)) && PKGS="$PKGS (${PARTIAL## * } partial)"

		# If we can, get the Solaris cluster that was installed

		[[ -f /var/sadm/system/admin/CLUSTER ]] \
			&& CST=/$(sed 's/^.*=//' /var/sadm/system/admin/CLUSTER)

		disp "packages" $PKGS "[SVR4${CST}]"
	fi

}

function get_pkg_repo
{
	# Get package repositories for IPS and pkgsrc systems

	if [[ -f /opt/local/etc/pkgin/repositories.conf ]]
	then

		while read a
		do
			disp "repository" $a
		done < /opt/local/etc/pkgin/repositories.conf

	elif can_has dpkg
	then

		apt-cache policy | grep http | while read a b c d
		do
			disp "repository" "$c ($b)"
		done

	elif can_has pkg
	then

		pkg publisher -H | while read a b c d e
		do
			[[ -n $e ]] \
				&& str="$a ($e) $b" \
				|| str="$a ($d)"

			disp "repository" $str
		done

	fi

}

function get_patch_count
{
	# A simple count of the installed patches

	can_has patchadd \
		&& PATCHES=$(patchadd -p 2>/dev/null | egrep -c ^Patch)

	disp "patches" $PATCHES
}

function get_local_zone
{
	# A list of the zones this server knows about. Display the brand, the
	# state, any CPU or memory caps, and the zone root

	if can_has zoneadm
	then

		zoneadm list -cp | cut -d: -f2,3,4,6 | sed '1d;s/:/ /g' | sort | \
		while read zn zstat zpth zbrand
		do

			sed="s/\[//;s/\]//;/^[^a-z]/s/^[$WSP]*\([^:]*\): \(.*\)$/\1=\2/p"

			# Not all versions support resource capping, or brands

			if zonecfg help | $EGS "capped-memory"
			then
				zmem=$(zonecfg -z $zn info capped-memory | sed -n "$sed")
				zcpu=$(zonecfg -z $zn info capped-cpu | sed -n "$sed")
				zdcpu=$(zonecfg -z $zn info dedicated-cpu | sed -n "$sed")
				rc=$(print "${zcpu},${zdcpu},${zmem}" | tr "\n" , | tr -s ,)
				rc=${rc#,}
			fi

			disp VM "local zone: $zn (${zbrand:-native}:${zstat}) [${rc%,}] $zpth"
		done

	fi
}

function get_ldoms
{
	# A list of logical domains on this server

	if is_root && can_has ldm
	then

		ldm ls 2>/dev/null | sed 1d | while read n st fl co cp m x
		do
			disp VM "LDOM: $n (port $co:$st) [${cp}vCPU/${m}]"
		done

	fi
}

#-- NETWORK AUDITING FUNCTIONS -----------------------------------------------

function mk_nic_devlist
{
	# Create a list of all network devices on the box. Used by get_nic().
	# Used to be used by get_mac() which no longer exists. Could be
	# reincorporated into get_net() at some point

	# Get the plumbed interfaces. This'll work on anything, even a zone.

	DLST="$(ifconfig -a | sed '/^[a-z]/!d;/^lo/d;s/: .*//')"

	# Now we need a full interface list. This'll show up things like
	# unused QFE cards. We'll use dladm if we have it. If not, we're going
	# to guess what cards we might have from the contents of /dev. There
	# might be a better way to do it on older revisions, but I don't know
	# what it is. Don't do this in local zones.

	if is_global && is_root && [[ -n $HAS_DL ]]
	then
		DLST2=$(eval dladm $DL_DEVLIST_CMD)
	elif is_global
	then
		DLST2=$(ls /dev | egrep "e1000g|bge|qfe|hme|ce|pcn|ge|dmfe|iprb" \
		| egrep "[0-9]$")
	fi

	for d in $DLST $DLST2
	do
		print $d
	done | grep -v / | sort -u
}

function get_llt_net
{
	# if we're a VCS node, get LLT interfaces. Same format as get_net, and
	# called by it

	LLT_LS=$(hasys -display $HOSTNAME -attribute LinkHbStatus \
	| sed '1d;s/^.*Status *//;s/ [A-Z]*/ /g')

	[[ -z $LLT_LS ]] && return

	over=$(print $LLT_LS | sed 's/ \{1,\}/,/g')

	[[ -n $OUT_P ]] \
		&& disp net "LLT|||||$over|||" || disp net "LLT link over $over"

	for nic in $LLT_LS
	do

		[[ -n $OUT_P ]] \
			&& disp net "$nic|LLT||||||" || disp net "$nic LLT link"

	done
}

function get_net
{
	# Get information on all the network interfaces, whether they're cabled,
	# plumbed, or whatever.

	DEVLIST=$(mk_nic_devlist)

	# If we're a primary domain, get a list of vswitched interfaces

	if is_root && can_has ldm
	then
		VSW_IF_LIST=" $(ldm list-domain -p -o network primary 2>/dev/null \
		| sed -n '/^VSW/s/^.*net-dev=\([^|]*\).*$/\1/p' | tr '\n' ' ') "
	fi

	for nic in $DEVLIST
	do
		unset type addr mac hname ipmp dhcp over xtra out ipzone speed \
		o_over o_mac o_speed

		# First, get the type.

		if [[ $nic == *[0-9]:[0-9]* ]]
		then
			type=virtual
		elif [[ $nic == dman* ]]
		then
			type="domain meta-interface"
		elif [[ $nic == clprivnet* ]]
		then
			type="clprivnet"
		elif (($OSVERCMP < 510 ))
		then
			type=phys
		else
			type=$(eval dladm $DL_TYPE_CMD 2>/dev/null)
		fi

		# S10 brands have dladm, but can't use it

		[[ -z $type ]] && type="unknown"

		# Old dladms can't show the class

		if [[ $type == "non-vlan" ]]
		then
			if [[ $nic == vsw* ]]
			then
				type="vswitch"
			elif [[ $nic == aggr* ]]
			then
				type="aggr"
			else
				type=phys
			fi

		fi

		# Now get extra info depending on the type

		if [[ $type == "vnic" ]]
		then

			# Get the underlying NIC, the speed, and the MAC address I
			# believe VNICs are always full duplex. Over is a ? in a zone

			mac=$(dladm show-vnic $nic -po macaddress)
			dladm show-vnic $net -po over,speed | tr : \  | read over s

			[[ $s == 1000 ]] && speed="1G-f"
			[[ $s == 100 ]] && speed="100M-f"

		elif [[ $type == "etherstub" ]]
		then
			:
		elif [[ $type == "aggr" ]]
		then

			# For aggregates, get the physical links, policy, and MAC

			a=${nic#aggr}
			over=$(eval dladm $DL_AO_CMD)
			over=${over%,}
			xtra=$(eval dladm $DL_AP_CMD)
			mac=$(eval dladm $DL_AM_CMD)
		elif [[ $type == "clprivnet" ]]
		then
			# For cluster private interconnects, get the underlying NICs

			if can_has clintr
			then
				over=$(clintr show -n $HOSTNAME \
				| sed -n '/Transport Adapter:/s/^.* //p')
			elif can_has scstat
			then
				over=$(scstat -W -h $HOSTNAME | sed -n \
				"/$HOSTNAME/s/^.*$HOSTNAME:\([a-z0-9]*\).*$/\1/p")
			fi

			over=$(print $over | tr " " ,)
		elif [[ $type == "vswitch" ]]
		then

			# If we can run the ldm program, we can find out which device
			# this vswitch is bound to

			if is_root && can_has ldm
			then
				over="$(ldm ls-domain -p -o network primary | sed -n \
				"/dev=switch@${nic#vsw}/s/^.*net-dev=\([^|]*\).*$/\1/p")"
			fi

		fi

		if [[ $type == "phys" || $type == "vnic" || $type == "virtual" \
		|| $type == "legacy" || $type == "unknown" ]]
		then

			# Ask ifconfig for the IP address

			addr="$(ifconfig $nic 2>/dev/null \
				| sed -n '/inet/s/^.*inet \([^ ]*\).*$/\1/p')"

			if [[ -n $addr ]]
			then

				# This interface has an address. Get the name of the zone or
				# host to which it belongs

				if ifconfig $nic | $EGS zone
				then
					hname=$(ifconfig $nic \
					| sed -n 's/^.*zone \([^ ]*\).*$/\1/p')
				elif [[ $addr == "0.0.0.0" ]]
				then
					hname="unconfigured"

					is_global && hname="$hname in global"
				else

					[[ -s /etc/hostname.$nic ]] \
						&& hname=$(sed '1!d;s/[  ].*$//' /etc/hostname.$nic)

					hname=${hname:-$HOSTNAME}
				fi

				# Get the MAC address

				mac=$(ifconfig $nic 2>/dev/null | sed -n "/ether/s/^.*r //p")

				# If the interface is down, we can quickly plumb it to get
				# its MAC.  This is a "destructive" action, and is only done
				# if the DO_PLUMB variable is defined. We skip aliased NICs

				if [[ $type == phys ]] && [[ -n $DO_PLUMB && -z $mac ]]
				then

					# ifconfig exits 0 even if it can't plumb an interface,
					# though it does complain

					tstr=$(ifconfig $nic plumb 2>&1)

					if [[ -z $tstr ]]
					then
						mac=$(ifconfig $nic 2>/dev/null | \
						sed -n "/ether/s/^.*r //p")
						ifconfig $nic unplumb
					fi

					mac=${mac:-unknown}
				fi

				# Get the link speed. Use dladm if we can. If not, try
				# kstat, then ndd

				if [[ $type == "phys" ]] && is_root && is_global
				then
					KDEV=${nic%[0-9]*}

					if [[ -n $HAS_DL ]] && eval dladm $DL_DEVLIST_CMD \
					| $EGS "^$nic"
					then
						speed=$(eval dladm $DL_SPEED_CMD)
					elif can_has kstat
					then

						if [[ -a /dev/$KDEV ]] && ! ifconfig $nic \
						| $EGS FAILED
						then
							KI=${nic#$KDEV}
							DUP=$(kstat -m $KDEV -i $KI \
							| sed -n '/link_duplex/s/^.* //p' | sed 1q)

							[[ $DUP == 2 ]] && SD=f || SD=h

							speed="$(kstat -m $KDEV -i $KI \
							| sed -n '/ifspeed/s/^.* //p')-$SD"
						fi

					else
						nds=$(ndd -get /dev/$KDEV link_speed 2>/dev/null)
						nds=$(ndd -get /dev/$KDEV link_mode 2>/dev/null)

						[[ $nds == 0 ]] && speed=10M
						[[ $nds == 1 ]] && speed=100M
						[[ $nds == 1000 ]] && speed=1G

						[[ $nds == 0 ]] && speed="${speed}-h"
						[[ $nds == 1 ]] && speed="${speed}-f"
					fi

					[[ $speed == 0-h ]] && unset speed

				fi

			else
				# ifconfig couldn't get an address for this interface.
				# Solaris 10 may be using it. Older Solarises aren't

				if is_root && is_global && [[ -n $HAS_DL ]] \
					&& dladm show-link $nic >/dev/null 2>&1
				then

					# If the interface is "up" then we can assume it's doing
					# something. If not, call it "uncabled" and give up

					if [[ $(eval dladm $DL_LINK_CMD) != "up" ]]
					then
						addr="uncabled" # interface is down
					else
						# Is the interface owned by a zone? If it is, it
						# must be for an exclusive IP instance

						dladm -h 2>&1 | $EGS show-linkprop \
							&& ipzone=$(eval dladm $DL_ZONE_CMD)

						if [[ -n $ipzone ]]
						then
							addr="exclusive IP"
							hname=$ipzone
							out="$addr zone=$hname"
						else # Not in use, or VLANned
							addr="unconfigured"
						fi

					fi

				else
					addr="uncabled"
				fi

				[[ -z $out ]] && out=$addr

			fi

		fi

		# The following tests only apply to interfaces that are plumbed.

		if ifconfig -a | $EGS "^${nic}:"
		then

			# Is this NIC part of an IPMP group?

			ipmp=$(ifconfig $nic | grep -w groupname)

			if [[ -n $ipmp ]]
			then
				ipmp=${ipmp##* }
				out="$out (IPMP group $ipmp)"
			fi

			# Is this NIC under the control of DHCP?

			if ifconfig $nic | $EGS DHCP
			then
				xtra=DHCP
				out="$out (DHCP assigned)"
			fi

		fi

		# Did the interface have a vswitch on it?

		[[ $VSW_IF_LIST == *" $nic "* ]] && xtra="+vsw"

		# Process the data for output

		if [[ -n $OUT_P ]]
		then
			dispdat="$nic|$type|$addr|$mac|$hname|$speed|$over|$ipmp|$xtra"
		else
			[[ -n $over ]] && o_over=" over $over"
			[[ -n $mac ]] && o_mac=" [${mac% *}]"
			[[ -n $speed ]] && o_speed=" ($speed)"
			dispdat="$nic ${type}${o_over} ${addr} ${hname}${o_speed}${o_mac} $xtra"
		fi

		disp net "$dispdat"
	done
}

function get_dns_serv
{
	# If we are using DNS for name resolution, get the server addresses

	[[ -f /etc/resolv.conf ]] && \
	grep -w nameserver /etc/resolv.conf | while read a b
	do
		disp "DNS server" $b
	done
}

function get_name_service
{
	# What are we using to look up users and hosts?

	egrep "^hosts|^passwd|^[ap].*attr" /etc/nsswitch.conf | while read a
	do
		disp "name service" $(print $a | sed 's/\[NOTFOUND=return\]//')
	done
}

function get_domainname
{
	can_has domainname && disp "domainname" $(domainname)
}

function get_routes
{
	# Routing table. Flag up default routes not in defaultrouters and non
	# .0 routes which are persistent

	route -p show >/dev/null 2>&1 && HAS_PER=1

	netstat -nr | egrep -v '127.0.0.1|224.0.0.0' | sort | \
	while read dest gw fl ref use int
	do
		unset X I
		[[ $gw == [0-9]* ]] || continue

		if [[ $dest == "default" ]]
		then
			$EGS "$gw" /etc/defaultrouter 2>/dev/null \
				|| X="(not in defaultrouter)"
		fi

		[[ -n $int ]] && I="($int) "

		[[ -n $HAS_PER ]] \
			&& route -p show | $EGS "$dest $gw$" && X="(persistent)"

		disp "route" "$dest $gw ${I}$X"

	done
}

function get_rt_fwd
{
	# Get IP routing and forwarding info. Use routeadm if we have it, fall
	# back to ndd (which doesn't work in branded zones)

	if can_has routeadm && is_root
	then

		routeadm | grep "IPv.*abled" | while read a b c d
		do
			[[ $c == enabled || $d == enabled ]] \
				&& disp routing "$a $b ${c}/$d"
		done

	elif is_root
	then

		[[ $(ndd -get /dev/ip ip_forwarding 2>/dev/null ) == 1 ]] \
			&& disp routing "IPv4 forwarding enabled"

		# IPv6 if it's there

		[[ $(ndd -get /dev/ip6 ip_forwarding 2>/dev/null) == 1 ]] \
			&& disp routing "IPv6 forwarding enabled"

		is_running "in.routed" && disp routing "IPv4 routing enabled"
	fi
}

function get_name_server
{
	# Find out what name services (DNS, NIS, LDAP etc) we're serving up.
	# Output of the form
	#  service_type (master/slave) domain

	# first, DNS

	CF=$(find_config named.conf "/var/named/etc /usr/local/bin/etc" named)

	if [[ -n $CF ]]
	then
		egrep "^[$WSP]*zone[$WSP]|type" $CF | sed -e :a \
			-e "s/zone[$WSP]*\"//;s/\".*$//;\$!N;s/\n.*type/ /;ta" \
			-e 's/;//' -e 'P;D' |  grep -v 'in-addr.arpa' | sort -k2 | \
		while read a b
		do
			[[ $b == "hint" ]] || disp "name server" "DNS ($b) $a"
		done

	fi

	# NIS

	if is_running ypxfrd
	then
		disp "name server" "NIS (master) $(domainname)"
	elif is_running ypserv
	then
		disp "name server" "NIS (slave) $(domainname)"
	fi

}

function get_ntp
{
	# NTP servers we're using. Also say if we appear to be broadcasting NTP

	F="/etc/inet/ntp.conf"

	if [[ -f $F ]]
	then
		is_running xntpd || is_running ntpd || X=" (not running)"

		grep ^server $F | while read a b c
		do
			[[ $c == "prefer" ]] && P="preferred " || P=""
			disp "NTP" "$b (${P}server)$X"
		done

		$EGS "broadcast" $F && disp "NTP" "acting as server" $X
	fi
}

function get_nfs_domain
{
	# If it's been changed (and is supported) get the NFSv4 domain name

	[[ -f /etc/default/nfs ]] && \
		disp "NFS domain" \
		$(sed -n "/^[$WSP]*NFSMAPID/s/^.*NFSMAPID_DOMAIN=//p" /etc/default/nfs)
}

#-- FILESYSTEM AUDIT FUNCTIONS -----------------------------------------------

function get_capacity
{
	# Get the available and used disk space on the server. Here, "available"
	# means the raw capacity of the disk, not the unused space.  Typesetting
	# avail and used to -i causes problems on ksh93 with large disks

	typeset avail=0 used=0

	# If there are zpools, get those

	if [[ -n $HAS_ZFS ]]
	then
		zpool list -H | grep -w ONLINE | while read nm sz usd av cap hlth altr
		do
			((avail = $avail + $(expand_suff $sz)))
			((used = $used + $(expand_suff $usd)))
		done
	fi

	# work through other local data filesystems

	for fstyp in ufs vxfs
	do
		df -kF$fstyp 2>/dev/null | sed '1d'
	done | while read dev sz usd av cap mpt
	do
		((avail = $avail + $sz * 1024))
		((used = $used + $usd * 1024))
	done

	pcu=$(print "scale=2;$used / $avail * 100" | bc)

	disp "capacity" "$(num_suff $avail)b ($(num_suff $used)b used) [${pcu}%]"
}

function get_zpools
{
	# Get a list of zpools, their versions, and capacities. There's no
	# "zpool get" in early ZFS releases. Output:
	# name status (last scrub:) [ver/max_ver]
	# Now does parseable output
	# pool|status|size|%full|scrub|ver|supp_ver|layout|devs|clustered

	if [[ -n $HAS_ZFS ]]
	then

		# First get the current highest supported zpool version, so we can
		# flag pools running something different. Can't always get this.

		zpool help 2>&1 | $EGS get && zsup=$(zpool upgrade -v \
			| sed '1!d;s/^.*version \([0-9]*\).*$/\1/')

		# Illumos now has feature flags.

		[[ $zsup == *feature* ]] && zsup="feature flags"

		# Get all the zpools under cluster control

		if can_has clresource && clresource list | $EGS HAStoragePlus
		then
			zpclus=" $(clresource show -v -t HAStoragePlus | sed -n \
			'/Zpools:/s/^.* //p' | tr '\n' ' ') "
		fi

		# zpool's get command doesn't have the -H and -o options, so this is
		# harder than it need be.

		zpool list -Ho name,health | while read zp st
		do

			if [[ $st == "FAULTED" ]]
			then
				[[ -n $OUT_P ]] && disp zpool "$zp|$st" || disp zpool $zp $st
			else

				# get the last scrub

				zls=$(zpool status $zp | egrep " scrub: | scan: " | \
				sed 's/^.*s on //')

				[[ -z $zls || $zls == *"none requested"*
				|| $zls == *cancel* || $zls = *resilver* || $zls == \
				*progress* ]] && zls="none"

				zpext="(last scrub: $zls)"

				# Get the version, the size, and capacity, if we can

				if [[ -n $zsup ]]
				then
					zpool get version,size $zp | sed '1d;$!N;s/\n/ /' \
					| read a b zv x
					zpext="$zpext [${zv}/${zsup}]"

					zpool get capacity $zp | sed 1d | read a b zc x
					zpool get size $zp | sed 1d | read a b zsz x
				fi

				# Get the pool layout - this is hard and might change

				zpool iostat -v $zp | sed -e '1,/^--/d' -e '/^--/,$d' \
				-e 's/ *//' | egrep -v "^c[0-9]+[td][0-9]+|^$zp " \
				| read zt junk

				[[ -z $zt ]] && zt="concat"

				zd=$(zpool iostat -v $zp | egrep -c "c[0-9]+[dt][0-9]+")
                zpool status | $EGS "logs" && zx=" log"
                zpool status | $EGS "cache" && zx="$zx cache"

				# Is it under cluster control?

				[[ $zpclus == *" $zp "* ]] && cl=" CLUSTERED" || cl=""

				[[ -n $OUT_P ]] \
					&& d="$zp|$st|$zsz|$zc|$zls|$zv|$zsup|$zt|$zd|$cl|$zx" \
					|| d="$zp $st $zsz/$zc $zpext ($zt: $zd devices$zx)$cl"

		 		disp "zpool" $d
			fi

		done

	fi
}

function get_vx_dgs
{
	# List VxVM disk groups, along with their status, and the number of
	# disks and volumes they contain

	if is_root && can_has vxdg
	then

		vxdg list | sed '1d' | while read dg st id
		do
			# Get errored disks and plexes

			errs=" [ERRS:$(vxprint -dg $dg | egrep -c \
			"NODEVICE|FAIL") disk/$(vxprint -pg $dg | egrep -c \
			"NODEVICE|IOFAIL") plex]"

			[[ $errs == *":0 disk/0 p"* ]] && unset errs

			vxprint -S -g$dg | sed '1d' | read v p s pf sf d rv rl stp vs c
			disp "disk group" \
			"$dg ($st) [${d} disk/${s}+$sf subdisk/$v vol/${p}+$pf plex]$errs"
		done

	fi
}

function get_root_fs
{
	# Get the filesystem type for / and say if it's mirrored or not

	RFS=$(df -n / | sed 's/^.* : \([^ ]*\).*$/\1/')
	RDEV=$(df -e / | sed '1d;s/ .*//')

	if [[ $RFS == "ufs" ]]
	then

		if [[ $RDEV == "/dev/md/"* ]]
		then
			metastat ${RDEV##*/} | $EGS "^${RDEV##*/}: Mirror" \
				&& FSM="(mirrored)"

		elif [[ $RDEV == "/dev/vx/"* ]]
		then
			FSM="(encapsulated"

			# Work out if it's mirrored by counting the plexes in the
			# rootvol

			rv=$(df -k / | sed -n '2s/ .*//p')

			[[ $(vxprint ${rv##*/} | grep -c ^pl) -gt 1 ]] \
				&& FSM="$FSM and mirrored"

			FSM="${FSM})"
		elif can_has raidctl
		then
			# Look to see if this disk is in raidctl output.

			RDSK=${RDEV%s*}
			RDSK=${RDSK##*/}
			raidctl -l 2>/dev/null | $EGS "^${RDSK}[$WSP]|Volume:$RDSK$" \
				&& FSM="(HW RAID)"
		fi

	elif [[ $RFS == "zfs" ]] && is_global
	then
		zpool status ${RDEV%%/*} | $EGS "^\[$IFS\]*mirror[ -]" \
			&& FSM="(mirrored)"
	fi

	disp "root fs" $RFS $FSM
}

function get_fs
{
	# Get a list of filesystems on the box. We ignore anything "system"
	# related, so we're only showing mounted filesystems which hold static
	# data. Format is
	#  mount_point fs_type (device:options) [warning]

	# Get the current highest supported zfs version if possible, then a list
	# of devices in vfstab and a list of zone roots.

	if [[ -n $HAS_ZFS ]]
	then
		ZPL="compression,quota"
		testfs=$(zfs list -H -oname | sed 1q)

		for xp in dedup zoned encryption
		do
			zfs get -H -o property all $(zfs list -H -oname | sed 1q) \
			| $EGS $xp && ZPL="${ZPL},$xp"
		done

		if zfs help 2>&1 | $EGS upgrade
		then
			zsup="/$(zfs upgrade -v | sed '/^  *[0-9]/!d' \
			| sed -n '$s/^  *\([0-9]*\).*$/\1/p')"
			ZPL="${ZPL},version"
		fi

	fi

	dftab="$(df -k 2>/dev/null)"
	vfstab=" $(grep "^/" /etc/vfstab | cut -f1 | tr "\n" " ") "

	is_global && can_has zoneadm && \
		ZONEROOTS=" $(zoneadm list -cp | egrep -v ^0:global | cut -d: -f4) "

	# Run through everything that's mounted

	mount -p | sort -k 3 | while read mdv fdv mpt typ fp mab mo
	do
		unset extra vf brk zx

		# ignore some fs types

		[[ $IGNOREFS == *" $typ "* || $mpt == "platform" || \
		$mpt = *libc.so* ]] && continue

		print "$dftab" | grep "^$mdv " | read d k u a c m

		[[ -n $a ]] && dfs="${u}/$k ($c) used" || dfs="unknown capacity"

		# we get extra info for ZFS filesystems. ZFS roots in zones can't be
		# examined - you can see the dataset name, but can't "get" it

		if [[ $typ == "zfs" && $mdv != "/" ]] && zfs list $mdv >/dev/null 2>&1
		then

			zfs get -Hp -o property,value $ZPL $mdv | while read p v
			do
				zx="${zx}${p}=${v},"
			done

			extra="${extra};${zx%,}$zsup;$(zfs list -Hrt snapshot $mdv | \
			grep -c ${mdv}@) snapshots"

		# If we're in a global zone, don't report NFS, SMBFS or LOFS
		# filesystems mounted under zone roots

		elif is_global && [[ -n $ZONEROOTS ]]
		then

			for zr in $ZONEROOTS
			do
				[[ $mpt == "$zr"* ]] && brk=1
			done

			[[ -n $brk ]] && continue

		fi

		if [[ $typ != "zfs" && $typ != "lofs" ]]
		then
			[[ $vfstab == *" $mdv "* ]] || vf=" [not in vfstab]"
		fi

		disp "fs" "$mpt $typ [$dfs] (${mdv};${mo}$extra)$vf"
	done

	# List unmounted ZFS filesystems

	if [[ -n $HAS_ZFS ]]
	then

		zfs list -Ho mounted,name,referenced | grep -v ^yes | \
		while read m n r
		do
			disp fs "unmounted zfs [$r] ($n)"
		done

	fi
}

function get_exports
{
	# Get exported filesystems and disks
	# Format is of the form
	#   path/dataset (share type) [options] "description"

	# NFS and ZFS shared SMB first. Only globals can share for now, but I
	# hear this will change. share's output changed in Solaris 11

	if share -h 2>&1 | $EGS proto
	then

		share | grep -v "Remote IPC" | while read a dir fstyp opts desc
		do
			unset x

			if [[ $fstyp == "nfs" ]]
			then
				x="[$opts]"
				[[ -n $desc ]] && x="$x '$desc'"
			elif [[ $fstyp == "smb" ]]
			then
				fstyp="smb/ZFS"
				x="[$a]"
			fi

			disp "export" "$dir ($fstyp) $x"
		done

	else

		if is_global
		then

			share | while read a dir opts desc
			do
				txt="$dir (nfs) [$opts]"
				[[ $desc != '""' ]] && txt="$txt $desc"
				disp "export" $txt
			done

		fi

		if [[ -n $HAS_ZFS ]] && zfs get all $(zfs list -Ho name | sed 1q) \
			| $EGS sharesmb
		then

			zfs get -H -po name,value sharesmb | sed '/@/d;/off$/d;/-$/d' | \
			while read fs st
			do
				disp export "$fs (smb/ZFS) [$st]"
			done

		fi

	fi

	# iSCSI. sdbadm if available, with [size]. If not, the ZFS shareiscsi
	# property with [shareiscsi value].

	if can_has sbdadm
	then

		sbdadm list-lu 2>/dev/null | sed '1,/^-/d' | while read guid size src
		do
			disp export "$src (iscsi) [${size}b]"
		done

	elif [[ -n $HAS_ZFS ]] && zfs get all $(zfs list -Ho name | sed 1q) \
		| $EGS shareiscsi
	then

		zfs get -H -po name,value shareiscsi | sed '/@/d;/off$/d' | \
		while read fs st
		do
			disp export "$fs (iscsi) [$st]"
		done

	fi

	# Samba via traditional smbd. Query the config file, because smbclient
	# may not be available, or may need a password

	if can_has smbd
	then
		SCF=$(smbd -b | sed -n '/CONFIGFILE/s/^.*: //p')

		if [[ -f $SCF ]]
		then
			sed -e '/\[global\]/d' -e '/^\[/b' -e '/path/b' -e d $SCF \
			| sed -e :a -e '/\]$/N; s/\]\n//; ta' \
			| sed 's/^\[//;s/path *= */ /' | while read name dir
			do
				disp export "$dir (smb/daemon) [$name]"
			done
		fi

	fi

	# Virtual disks in an LDOM primary

	if can_has ldm && is_root
	then
		LDMS=$(ldm ls 2>/dev/null | sed '1,2d;s/ .*$//')

		ldm ls-services -p 2>/dev/null \
		| sed -n '/vol=/s/^|vol=\([^|]*\).*dev=\([^|]*\).*$/\1 \2/p' | \
		while read vol dev
		do
			own=unassigned

			for ldm in $LDMS
			do
				ldm ls-constraints -p $ldm | $EGS "^VDISK\|name=${vol}\|" \
					&& own=$ldm
			done

			disp export "$vol (vdisk) [${dev} bound to ${own}]"
		done

	fi

	# VBox disks

	if can_has VBoxManage && is_root
	then

		VBoxManage list hdds | egrep  "^Location|^Usage" | sed \
		'/^Usage/s/Usage: *\(.*\) (.*/=\1/;/Location/s/Location: *//' | \
		sed '$!N;s/\n//' | while read l
		do
			disp export "${l%=*} (vbox disk) [on ${l#*=}]"
		done

	fi

	# Virtual disks in an xVM dom0

	if can_has virsh && is_root
	then

		virsh list 2>/dev/null | sed '1,3d;/^$/d' | while read n dom s
		do
			disp export $(virsh dumpxml $dom | sed -n \
			"/source dev/s/^.*dev='\([^']*\)'.*$/\1/p") "(xVM disk) [on $dom]"
		done

	fi
}

function get_metasets
{
	# Get metasets, number of disks, number of hosts, and say if we own it

	if can_has metaset
	then
		metaset 2>/dev/null \
		| sed -n '/^Set/s/^.*name = \([^,]*\),.*$/\1/p' | while read set
		do
			metaset -s $set | $EGS "$HOSTNAME .*Yes" && x=" [owner]" || x=""
			snh=$(metaset -s $set | sed -n '/^Host/,/^$/p' | egrep -c " +[a-z]")
			snd=$(metastat -s $set -p | grep -c /rdsk/)

			disp metaset "$set ($snd disks/$snh hosts)$x"
		done

	fi
}

#-- APPLICATION AUDITING FUNCTIONS -------------------------------------------

function get_apache
{
	# Get lots of information about Apache. Now calls other functions

	for BIN in $(find_bins httpd)
	do
		APVER=$($BIN -v | sed -n '1s/^.*\/\([^ ]*\).*$/\1/p')
		APDIR=${BIN%/bin/httpd}
		is_run_ver "Apache@$BIN" $BIN $APVER
		get_apache_mods $BIN $APVER
		get_php_mod $APDIR apache $APVER
	done
}

function get_apache_mods
{
	# Get the shared modules apache is running. Call this only from other
	# functions.
	#
	# $1 is the Apache 'httpd' binary
	# $2 is the Apache version

	# Apache 2.2 can tell you what modules it's running. Rather than check
	# the version, just see if -M is supported

	if [[ $2 == 2.2* ]]
	then
		AMODS=$($1 -M 2>/dev/null | \
		sed -n '/shared/s/ \([^ ]*\).*$/\1/p' | sort -u)
	elif is_running httpd && is_root && can_has pldd
	then

		# -M isn't available, but if httpd is running, we're root, and we
		# have ptools, we can get a list of the loaded modules This will
		# work for version 1 and 2

		AMODS=$(pldd $(my_pgrep httpd) | sed -n '/so$/s/^.*\///p' | sort -u)
	else

		# In this case there's not much we can do other than read the
		# configuration file. We'll look for an httpd.conf file in
		# $1/conf, and have a stab at Sun's /etc/apache /etc/apache2

		[[ "$2" == 2* ]] && APETCD="apache2" || APETCD="apache"

		for d in ${2}/conf /etc/$APETCD
		do
			[[ -f $d/httpd.conf ]] \
				&& AMODS=$(egrep -i "^[$IFS]*Loadmodule" $d/httpd.conf \
				| sed 's/^.*\///'| sort -u)
		done

	fi

	[[ -n $AMODS ]] && for mod in $AMODS
	do
		disp "apache so" "$mod ($APVER)"
	done
}

function get_nginx
{
	# Get Nginx stuff

	for BIN in $(find_bins nginx)
	do
		NXVER=$($BIN -v 2>&1)
		is_run_ver "Nginx@$BIN" $BIN ${NXVER##*/}
	done

}

function get_php_mod
{
	# Get the version of a webserver PHP module. We just try to pull the
	# version number out of the raw library with strings. Not nice, but it
	# works.
	# YOU DON'T CALL THIS IN THE NORMAL WAY! IT'S CALLED FROM A
	# get_webserver() FUNCTON AND REQUIRES AN ARGUMENT
	# $1 is the directory we expect to hold libphp
	# $2 is the web server that's loaded the module
	# $3 is the Apache version loading the module (if applicable)

	find $1 -follow -type f -a -name libphp\?.so | while read pl
	do
		# First look for the X-Powered string. This is in version 4+, and
		# IIRC, late version 3. If that fails, do a dodgy pattern match that
		# appears to work for everything

		pv=$(strings $pl 2>/dev/null | sed -n '/^X-Powered/s/.*\///p')

		[[ -z $pv ]] && pv=$(strings $pl 2>/dev/null \
		| egrep "^[3-5]\.[0-9]\.[0-9]*$" | head -1)

		[[ -z $pv ]] && pv="unknown"

		disp "mod_php" "$pv ($2 module) ($3)"
	done
}

function get_tomcat
{
	# Displays the version and status of Tomcat. Tomcat's quite hard to find
	# and query, and has changed a lot over the years. We need a JVM too.

	can_has java || return
	J=$(which java)

	# Look for likely tomcat binaries, and use them to generate a list of
	# possible Tomcat directories

	for BIN in $(find_bins tomcat catalina.sh startup.sh)
	do
		print $BIN
	done | sed 's|/bin/[a-z.]*$||' | sort -u | while read TC_BASE
	do
		# If we have a version.sh script, use it.  Older versions we have to
		# have to guess.

		if [[ -f ${TC_BASE}/bin/version.sh ]]
		then
			TC_VER=$(JAVA_HOME=${J%/bin/java} sh ${TC_BASE}/bin/version.sh \
			| sed -n '/^Server version:/s/^.*\///p')
		else
			[[ -f ${TC_BASE}/bin/tomcat ]] && TC_VER="3.x"
			[[ -f ${TC_BASE}/bin/startup.sh ]] && TC_VER="4.x"
		fi

		# If there's no Java process, Tomcat's not running. If there is, we
		# need to examine it using pargs, because the command line is so
		# long that the bit we want doesn't show with pgrep. We need to be
		# root to run pargs, hence the "possibly running" message.

		if my_pgrep java >/dev/null
		then

			if is_root
			then
				pargs $(my_pgrep java 1) | $EGS \
				"Dcatalina.home=|Dtomcat.home=" && TC_R=1
			else
				TC_EXTRA="(possibly running)"
				TC_R=1
			fi

		fi

		[[ -z $TC_R ]] && TC_EXTRA="(not running)"

		disp "Tomcat" $TC_VER $TC_EXTRA
	done

}

function get_glassfish
{
	# Get the version of Glassfish. It tries to get the version from the
	# running server. May need to add --local to the version cmd.

	for BIN in $(find_bins asadmin)
	do
		VSTR=$(print version | $BIN | grep "Version =")

		[[ $VSTR == *Open* ]] && GFT="Open Source" || GFT=Oracle

		# I'm not sure about this. It makes a strong assumption about the
		# glassfish command line, which may not be safe

		[[ $(ps -ef | grep -c "java -cp ${BIN%%/bin*}") == 1 ]] \
			&& xtra=" (not running)" || xtra=''

		disp "Glassfish@$BIN" "$(print $VSTR | \
		sed 's/^[^0-9]*//') [$GFT version]$xtra"

	done
}

function get_iplanet_web
{
	# Find Sun/iPlanet installs. This is hard because we can't properly
	# know where it's installed without searching the entire filesystem.
	# It's further complicated by the commands, name, and output changing
	# with each release, and the fact the commands can only be run as root.

	is_root || return

	for d in $IPL_DIRS
	do

		# If the base directory exists, count the server directories inside
		# it, look for an admin instance, count the running processes and
		# summarise

		unset wsc ha

		# 6.1 admin (running) 3 x http (2 running)

		[[ -d $d ]] || continue

		wsc=$(ls $d | grep -v https-admserv | egrep -c "^https-")

		if [[ -f ${d}/bin/https/prodinfo ]]
		then # 3
			wpr="ns-httpd"
			asb="ns-admin"
			wsv=$(sed -n '/^ServerVer/s/^.* //p' ${d}/bin/https/prodinfo)
			[[ -f ${d}/start-admin ]] && ha=1
		else # 6 and 7
			wpr="webservd "

			for w in bin/https/bin lib
			do
				f=${d}/${w}/webservd
				[[ -f $f ]] && wsv=$($f -v | sed '1d;s/^.*ver //;s/ .*$//')
			done

			[[ $wsv == 7* ]] && asb=admin-server || asb=https-admserv
			[[ -d ${d}/$asb ]] && ha=1
		fi

		if [[ -n $ha ]]
		then
			wsx=admin
			is_running $asb || wsx="${wsx} (not running)"
		fi

		[[ -n $wsx ]] && wsx="${wsx}, "

		wsx="$wsx $wsc x http ("$(ps -ef | sed -n \
		"/$asb/d;/$wpr/s/^.*$wpr//p" | sort -u | egrep -c .)" running)"

		[[ -n $ha || $wsc -gt 0 ]] && disp "iPlanet web@$d" ${wsv:-unknown} $wsx
	done
}

function get_coldfusion
{
	# displays the version of any installed coldfusion, and tells you if
	# it's installed, but not running

	for BIN in $(find_bins cfinfo)
	do
		is_run_ver "Coldfusion@$BIN" fusion $($BIN -version)
	done
}

function get_squid {

	for BIN in $(find_bins squid)
	do
		is_run_ver "Squid@$BIN" squid $($BIN -v | sed -n '1s/^.*ion //p')
	done
}

function get_mysql_s
{
	# Get the version of any MySQL server software we find

	for BIN in $(find_bins mysqld)
	do
		is_run_ver "MySQL server@$BIN" $BIN $($BIN -V | \
		sed 's/^.*Ver \([^ -]*\).*$/\1/')
	done
}

function get_mongodb_s {

	# Get MongoDB version

	for BIN in $(find_bins mongod)
	do
		is_run_ver "MongoDB@$BIN" $BIN $($BIN --version \
		| sed -n '1s/db version v\([0-9.]*\).*$/\1/p')
	done
}

function get_redis_s {
	for BIN in $(find_bins redis-server)
	do
		is_run_ver "Redis server@$BIN" $BIN $($BIN -v \
        | sed 's/^.*v=\([^ ]*\).*/\1/')
	done
}

function get_postgres_s {

	# Get Postgres version

	for BIN in $(find_bins postgres)
	do
		is_run_ver "Postgres@$BIN" $BIN $($BIN --version | sed 's/.* //')
	done
}

function get_ora_s
{
	# Get Oracle server version. Assume it's running if there's a listener,
	# and that lsnrctl gives us the server version. Get the base directories
	# out of the oratab

	if [[ -f /var/opt/oracle/oratab ]]
	then

		egrep -v "^#|^[${IFS}]*$" /var/opt/oracle/oratab | cut -d: -f2 \
		| sort -u | while read oh
		do
			[[ -f ${oh}/bin/tnslsnr ]] \
				&& is_run_ver "Oracle@$oh" tnslsnr $(ORACLE_HOME=$oh \
				${oh}/bin/tnsping -\? \
				| sed -n '/^TNS/s/^.*Version \([^ ]*\).*$/\1/p')
		done

	fi
}

function get_exim
{
	# Get an exim version and see if it's running as a daemon

	for BIN in $(find_bins exim)
	do
		is_run_ver "exim@$BIN" $BIN $($BIN -bV | \
		sed -n '1s/^.*version \([^ ]*\) .*$/\1/p')
	done
}

function get_sendmail
{
	# Get sendmail version and see if it's running as a daemon. Is careful
	# to check that it's not something like exim *pretending* to be
	# sendmail. The timeout_job is necessary for machines without reverse
	# DNS entries.

	for BIN in $(find_bins sendmail)
	do

		if $BIN -bV 2>&1 | $EGS "Invalid operation mode V"
		then
			sndver=$(print \$Z | timeout_job "$BIN -bt -d0" 2 | \
			sed '1!d;s/^.* //' 2>/dev/null)
			is_run_ver "sendmail@$BIN" $BIN $sndver
		fi

	done
}

function get_cronolog
{
	# Get a cronolog version and see if it's running as a daemon.

	for BIN in $(find_bins cronolog)
	do
		CRONOLOG_VER=$($BIN -V 2>&1)
		is_run_ver "cronolog@$BIN" $BIN ${CRONOLOG_VER##* }
	done
}

function get_svnserve
{
	# Find out what version of Subversion server we have and report if it's
	# running as a daemon or not.

	for BIN in $(find_bins svnserve)
	do
		is_run_ver "svn server@$BIN" $BIN "svnserve $($BIN --version \
		| sed '1s/^.*on \([^ ]*\).*$/\1/;q')"
	done

	# Also check to see whether Apache is running the mod_dav_svn module.

	for BIN in $(find_bins httpd)
	do
		unset ar
		APVER=$($BIN -v | sed -n '1s/^.*\/\([^ ]*\).*$/\1/p')

		if get_apache_mods $BIN $APVER | $EGS _svn
		then
			is_running $BIN || ar="not "
			disp "svn server" "apache module (${ar}running)"
		fi

	done

}

function get_nb_s
{
	# Get Netbackup server version

	for BIN in $(find_bins vauth_test)
	do
		NBVF=${BIN%bin/*}netbackup/bin/version
		BPRD=${NBVF%version}bprd

		if [[ -f $BPRD ]]
		then
			[[ -f $NBVF ]] && NBV=$(sed 's/^.* //' $NBVF)

			is_run_ver "NB server@$BPRD" $BPRD $NBV
		fi

	done
}

function get_nb_c
{
	# Get Netbackup client info

	for BIN in $(find_bins vauth_test)
	do
		NBVF=${BIN%bin/*}/netbackup/bin/version
		BPC=${NBVF%version}bpclntcmd

		[[ -f $NBVF ]] && NBV=$(sed 's/^.* //' $NBVF)

		my_pgrep vnetd && NBX="(running)"
		$EGS "^vnetd" /etc/inetd.conf && NBX="(inetd)"

		disp "NB client@$BPC" "$NBV $NBX"
	done
}

function get_networker_s
{
	# Get Networker server version. I can't find any way to do this other
	# than from the package.

	if can_has pkginfo && pkginfo LGTOserv >/dev/null 2>&1
	then
		is_run_ver "Networker srvr " nsrindexd \
		$(pkginfo -l LGTOserv | sed -n '/VERSION:/s/^.*:  //p')
	fi
}

function get_networker_c
{
	# Get Networker client version. I can't find any way to do this other
	# than from the package.

	if can_has pkginfo && pkginfo LGTOclnt >/dev/null 2>&1
	then
		is_run_ver "Networker clnt" nsrexecd \
		$(pkginfo -l LGTOclnt | sed -n '/VERSION:/s/^.*:  //p')
	fi
}

function get_named
{
	# Get the version of BIND that we're running. (Or not, as the case may
	# be.) May be called named or in.named. Now handles weird old nameds
	# that write to stderr on multiple lines

	for BIN in $(find_bins named in.named)
	do
		#  The grep -v is to strip out the "BIND 8.x is obsolete" message on
		#  Solaris 8, which can confuse things

		is_run_ver "BIND@$BIN" $BIN $($BIN -v 2>&1 | egrep -v \
		obsolete | sed -n '/BIND/s/^.*BIND \([^ ]*\).*$/\1/p')
	done
}

function get_vbox
{
	# Get the version of VirtualBox

	can_has VBoxManage && disp VirtualBox $(VBoxManage --version)
}

function get_sshd
{
	# Get the version of the SSH daemon. Seems the only way to get it is to
	# feed it bad options. This works for SunSSH and OpenSSH

	for BIN in $(find_bins sshd)
	do
		is_run_ver "sshd@$BIN" $BIN $($BIN -o  2>&1 \
		| sed '2!d;s/sshd version //;s/[, ].*$//')
	done
}

function get_mailman
{
	# Get mailman version

	# Mailman has a python script called "version" which reports the
	# version. Seriously.

	for BIN in $(find_bins qrunner)
	do
		MM_VER=$(${BIN%/*}/version)
		is_run_ver "Mailman@$BIN" $BIN ${MM_VER##* }
	done
}

function get_splunk
{
	# Get Splunk version

	for BIN in $(find_bins splunkd)
	do
		SPLUNK_VER=$($BIN --version)
		SPLUNK_VER=${SPLUNK_VER#* }
		is_run_ver "Splunk@$BIN" $BIN ${SPLUNK_VER%% *}
	done
}

function get_sccli
{
	# Get the version of SCCLI on the box, assuming there is one.

	for BIN in $(find_bins sccli)
	do
		SCCLI_VER=$($BIN -v)
		disp "SCCLI@$BIN" ${SCCLI_VER##* }
	done
}

function get_vcs
{
	# had is capable of displaying a version number, so we'll just do that.
	# Report "not running" if hashadow isn't in the process table

	can_has had && is_run_ver "VCS" hashadow $(had -version)
}

function get_chef_client
{
	for BIN in $(find_bins chef-client)
	do
		CHEFC_VER="$($BIN --version)"
		is_run_ver "chef-client@/$BIN" $BIN ${CHEFC_VER##* }
	done
}

function get_puppet
{
    for BIN in $(find_bins puppet)
	do
        disp "puppet@/$BIN" $($BIN --version)
	done
}

function get_cfengine
{
    for BIN in $(find_bins cf-agent)
	do
        cfe_ver=$($BIN --version)
        disp "cf-engine@/$BIN" ${cfe_ver##* }
	done
}

function get_collectd
{
    for BIN in $(find_bins collectd)
	do
        CD_VER=$($BIN -h | sed -n '/^collect/s/^collectd \([^,]*\).*/\1/p')
        is_run_ver "collectd@/$BIN" $BIN $CD_VER
	done
}


function get_samba
{
	# Samba version. We get the shares elsewhere

	for BIN in $(find_bins smbd)
	do
		SMB_VER="$($BIN --version)"
		is_run_ver "Samba@/$BIN" $BIN ${SMB_VER##* }
	done
}

function get_powermt
{
	# Get the version of EMC powermt

	is_root && can_has powermt && \
	disp "powermt" $(powermt version | sed 's/^.*sion //')
}

function get_vxvm
{
	# Get the version of Veritas Volume Manager. Might change this to use
	# vxlicrep, but for now get the version of the kernel module. This is
	# more accurate

	is_run_ver "VxVm" vxconfigd \
		$(modinfo | sed -n '/ vxio /s/^.*VxVM \([^ ]*\).*$/\1/p')
}

function get_vxfs
{
	# Get the version of Veritas Filesystem. As with volume manager, by
	# querying the loaded module list

	disp "VxFS" $(modinfo | sed -n '/vxfs/s/^.*xFS \([^ ]*\).*/\1/p')
}

function get_scs
{
	# Get the Sun cluster version

	scr=/etc/cluster/release

	if [[ -f $scr ]]
	then
		scv=$(sed -n '1s/^.*Cluster \([^ ]*\) .*$/\1/p' $scr)
		is_running cluster || scx="(not running)"
		disp "Sun Cluster" ${scv:-unknown} $scx
	fi
}

function get_ai_srv
{
	# Is there an AI install or package (repo) server running?

	for srv in install pkg
	do
		unset x
		s=$(svcs -Ho state ${srv}/server 2>/dev/null)

		[[ -n $s ]] && x="not running"
		[[ $s == "online" ]] && x="running"

		[[ -n $x ]] && disp "AI server" "$srv server (${x})"
	done
}

function get_ldm
{
	# Get the version of the LDOM management software on the box

	for BIN in $(find_bins ldm)
	do
		is_run_ver "ldm@$BIN" ${BIN}d $($BIN --version 2>/dev/null \
		| sed -n '/^Logic/s/^.*(v \([^)]*\).*/\1/p')
	done
}

function get_smc
{
	# Is SMC running and listening?

	if can_has smcwebserver
	then
		V=$(wcadmin --version 2>/dev/null)

		[[ -z $V ]] && V="unknown" || V=${V##* }

		if (( $OSVERCMP < 510 ))
		then
			netstat -an | $EGS "6789.*LISTEN" && msg="running"
		else
			s="system/webconsole"

			if [[ $(svcs -H -o state $s) == "online" ]]
			then
				msg="running"

				[[ $(svcprop -p options/tcp_listen $s) == "true" ]] \
					&& msg="$msg and listening"
			else
				msg="not running"
			fi

		fi

	elif can_has smc
	then
		# This can hang forever on some machines

		V=$(timeout_job "smc --version" 2>/dev/null)
		is_running smcboot && msg=running || msg="not running"
	fi

	[[ -n $msg ]] && disp "SMC" "${V##* } ($msg)"
}

function get_symon
{
	# Get SyMon version

	for BIN in $(find_bins sm_symond)
	do
		is_run_ver "SyMon@$BIN" $BIN $($BIN -v \
		| sed 's/^.*mon version \([^, ]*\).*$/\1/')
	done
}

function get_ssp
{
	# Looks to see if we are an E10k SSP. I'm not aware of an SSP program
	# which displays its version, so we have to query the package

	for BIN in $(find_bins scotty)
	do
		is_run_ver "SSP@$BIN" $BIN $(pkginfo -l SUNWsspop | \
		sed -n '/VERSION/s/^.*ION: *\([^ ,]*\).*$/\1/p')
	done
}

function get_x
{
	# Is this box running, or capable of running, an X-server? The binary
	# differs between platforms. Xorg for recent x86, Xsun for old X86 and
	# SPARCs

	if can_has Xorg
	then
		XBIN=Xorg
		is_root && XVER="Xorg-$(Xorg -version 2>&1 | sed '2!d;s/^.* //')"
	elif can_has Xsun
	then
		XBIN=Xsun
		XVER="Xsun"
	fi

	[[ -n $XBIN ]] && is_run_ver "X server" $XBIN "$XVER"
}

#-- TOOL TESTS --------------------------------------------------------------

function get_openssl
{
	# Gets the version of any OpenSSL binary it finds. This is probably a
	# good indicator of the OpenSSL libraries any SSL enabled software is
	# running, but not guaranteed.

	for BIN in $(find_bins openssl)
	do
		OSSL_VER=$($BIN version)
		OSSL_VER=${OSSL_VER#* }
		disp "OpenSSL@$BIN" ${OSSL_VER%% *}
	done
}

function get_rsync
{
	# rsync's version reporting is, err, thorough

	for BIN in $(find_bins rsync)
	do
		disp "rsync@$BIN" $($BIN --version | \
		sed '1!d;s/^.*sion \([^ ]*\) ..*$/\1/')
	done
}

function get_mysql_c
{
	# Get the version of any MySQL client software we find

	for BIN in $(find_bins mysql)
	do
		disp "MySQL client@$BIN" $($BIN -V | sed 's/^.*rib \([^ -,]*\).*$/\1/')
	done
}


function get_redis_c
{
	for BIN in $(find_bins redis-client)
	do
        disp "Redis client@$BIN" $($BIN -v | sed 's/.* //')
	done
}

function get_git_c
{
    for BIN in $(find_bins git)
	do
        disp "Git client@$BIN" $($BIN --version | sed 's/.* //')
	done
}

function get_postgres_c
{
	# Get the version of the Postgres client

	for BIN in $(find_bins psql)
	do
		disp "Postgres client@$BIN" $($BIN --version | sed  's/.* //;q')
	done
}

function get_sqlplus
{
	# Get verion of SQL*Plus

	[[ -f ${ORACLE_HOME}/bin/sqlplus ]] \
		&& disp "sqlplus" $(ORACLE_HOME=$ORACLE_HOME \
		${ORACLE_HOME}/bin/sqlplus -\? \
		| sed -n '/SQL/s/^.*Release \([^ ]*\).*$/\1/p')
}

function get_svn_c
{
	# Get the version of Subversion client software

	for BIN in $(find_bins svn)
	do
		disp "svn client@$BIN" $($BIN --version --quiet)
	done
}

function get_java
{
	# Get the version of Java on this box. Nothing's ever straightforward
	# with Java is it? If there's a javac, assume it's a JDK.

	for BIN in $(find_bins java)
	do
		JV=$($BIN -version 2>&1 | sed -n '1s/^.*"\([^"]*\)"/\1/p')
		[[ $JV == *JDK_* ]] && JV=${JV#*JDK_}
		[[ -x "${BIN}c" ]] && JEX="(JDK)" || JEX="(JRE)"
		disp "Java@$BIN" $JV $JEX
	done
}

# Get the versions of a few scripting languages.

function get_python
{
	for BIN in $(find_bins python)
	do
		PV=$($BIN -V 2>&1 )
		disp "Python@$BIN" ${PV#* }
	done
}

function get_ruby
{
	for BIN in $(find_bins ruby)
	do
		RV=$($BIN -v)
		RV=${RV#* }
		disp "ruby@$BIN" ${RV%% *}
	done
}

function get_node
{
	for BIN in $(find_bins node)
	do
		NV=$($BIN -v)
		disp "node@$BIN" ${NV#v}
	done
}

function get_perl
{
	# Get the version of Perl

	for BIN in $(find_bins perl)
	do
		PV="$($BIN -V:version 2>/dev/null)" && PV=${PV%\'*}
		disp "perl@$BIN" ${PV##*\'}
	done
}

function get_php_cmd
{
	# Command line PHP binary

	for BIN in $(find_bins php)
	do
		disp "PHP cmdline@$BIN" $($BIN -v | sed '1!d;s/PHP \([^ ]*\).*$/\1/')
	done
}

function get_cc
{
	# Gets the version and patch number of whatever Sun are calling their C
	# compiler this week. Definitely works for 5, 7, 11 and 12.

	for BIN in $(find_bins cc)
	do
		# Is this really Sun CC?

		cc 2>&1 | $EGS "^usage" || continue

		CCDIR=${BIN%/*}
		CC_LIST="(C"
		CC_INFO=$($BIN -V 2>&1 | sed 1q)

		[[ "$CC_INFO" == *Forte* ]] && FN=6 || FN=4

		CC_MAJ_VER=$(print $CC_INFO | cut -d\  -f$FN)

		if [[ $CC_INFO == *Patch* ]]
		then
			CC_MIN_VER=${CC_INFO% *}
			CC_MIN_VER=" ${CC_MIN_VER##* }"
		elif print $CC_INFO | $EGS "/[0-9][0-9]$"
		then
			CC_MIN_VER=" ${CC_INFO##* }"
		fi

		# Look to see what options we have. C/C++/Fortran/IDE

		[[ -x ${CCDIR}/CC ]] && CC_LIST="${CC_LIST}, C++"
		[[ -x ${CCDIR}/f77 ]] && CC_LIST="${CC_LIST}, Fortran"
		[[ -x ${CCDIR}/sunstudio ]] && CC_LIST="${CC_LIST}, IDE"
		CC_LIST="${CC_LIST})"

		disp "Sun CC@$BIN" ${CC_MAJ_VER}$CC_MIN_VER $CC_LIST
	done
}

function get_gcc
{
	# Get the version of GCC on this box and find out what languages it
	# supports. Doesn't understand ADA, because I don't.

	for BIN in $(find_bins gcc)
	do
		GCCDIR=${BIN%/*}
		GCC_LIST="(C"
		GCC_VER=$($BIN -dumpversion)
		[[ -x ${GCCDIR}/g++ ]] && GCC_LIST="${GCC_LIST}, C++"
		[[ -x ${GCCDIR}/gfortran ]] && GCC_LIST="${GCC_LIST}, Fortran"
		[[ -x ${GCCDIR}/gcj ]] && GCC_LIST="${GCC_LIST}, Java"
		$BIN --help=objc >/dev/null 2>&1 && GCC_LIST="${GCC_LIST}, Obj-C"
		$BIN --help=objc++ >/dev/null 2>&1 && GCC_LIST="${GCC_LIST}, Obj-C++"
		GCC_LIST="${GCC_LIST})"

		disp "GCC@$BIN" $GCC_VER $GCC_LIST
	done
}

function get_nettracker
{
	# Get the version of Nettracker. Works for 7

	for BIN in $(find_bins nettracker)
	do
		disp "Nettracker@$BIN" $($BIN \
		| sed '1!d;s/NetTracker \([^ ]*\).*$/\1/')
	done
}

function get_saudit
{
	# Look for all installed copies this script

	for BIN in $(find_bins s-audit.sh)
	do
		disp "s-audit@$BIN" $($BIN -V)
	done
}

function get_pca
{
	# Get the version of PCA, if it's installed

	for BIN in $(find_bins pca)
	do
		PCA_VER=$($BIN --version 2>/dev/null)
		disp "PCA@$BIN" ${PCA_VER#* }
	done
}

function get_scat
{
	# Get the version of Solaris CAT

	for BIN in $(find_bins scat)
	do
		disp "SunCAT@$BIN" $(print | $BIN \
		| sed '2!d;s/^.*CAT \([^ ]*\).*$/\1/')
	done
}

function get_explorer
{
	# Get the version and the installation status of Sun Explorer. It can be
	# installed but not configured, which means you can't get the version
	# straight from the main executable.

	for BIN in $(find_bins explorer)
	do
		EXD=$(which explorer | sed "s|/bin/explorer||")

		# Only root can run explorer

		if is_root
		then
			EXV=$(${EXD}/bin/explorer -V 2>&1 | sed -n '/^Explorer/s/^.*: //p')
		else
			EXV=$(sed -n '/EXP_VERSION=/s/^.*=//p' $EXD/bin/exp_defaults)
		fi

		disp "Explorer@$BIN" $EXV
	done
}


function get_jass
{
	# Get JASS version

	for BIN in $(find_bins add-client)
	do
		disp "JASS@$BIN" $(add-client -v | sed 's/^.* //')
	done
}

function get_jet
{
	# Get JET version - I can't see a way to do this other than pkginfo

	disp "JET" $(pkginfo -l SUNWjet 2>/dev/null | sed -n '/VERSION/s/^.* //p')
}

function get_vts
{
	# Get the version of Sun VTS

	for BIN in $(find_bins vtstty)
	do
		VTS_VER=$($BIN -v)
		disp "VTS@$BIN" ${VTS_VER##*,}
	done
}

function get_sneep
{
	# Get the version of Sneep

	for BIN in $(find_bins sneep)
	do
		SNEEP_VER=$($BIN -V)
		disp "Sneep@$BIN" ${SNEEP_VER##* }
	done
}
#-- SECURITY AUDIT FUNCTIONS -------------------------------------------------

function get_users
{
	# Get non-standard users

	cut -d: -f1,3 /etc/passwd | sort -nt: -k 2 | tr : \  | while read u id
	do
		disp user "$u ($id)"
	done
}

function get_authorized_keys
{
	# Get authorized keys. Do this by looking in any locally mounted home
	# directory for a .ssh/authorized_keys file, then studying it. Can't do
	# this if we're not root

	is_root || return

	# Look to see if our home directories are automounted

	[[ -d /home ]] && df -b /home | $EGS ^auto_home && AUTO_HOME=true

	cut -d: -f6 /etc/passwd | sort -u | while read dir
	do
		KEYFILE="${dir}/.ssh/authorized_keys"

		# Any kind of test on an automounter controlled directory will
		# result in either that dir being mounted, or an error messages in
		# messages and in splunk. We don't want that.

		[[ $dir == /home/* ]] && [[ -n $AUTO_HOME ]] && continue

		# If we've got this far, the directory isn't part of auto_home. Ask
		# df to check this isn't local. I think this will be okay. There's a
		# chance this could flag a warning if someone's added an automounted
		# directory through an extra auto_home entry, but I'm prepared to
		# take the chance

		df -l $dir >/dev/null 2>&1 || continue

		# Now we *know* we're on a local fs, so we can look for the key

		if [[ -f $KEYFILE ]]
		then

			cut -d\  -f3 $KEYFILE | while read key
			do
				user=$(ls -o $KEYFILE | tr -s " " | cut -d\  -f3)
				disp "authorized key" "$key ($user)"
			done

		fi

	done

}

function get_empty_passwd
{
	# report on any blank passwords.

	if is_root
	then
		cut -d: -f1,2 /etc/shadow | while read line
		do
			user=${line%:*}
			pass=${line#*:}

			[[ -z $pass ]] && disp "empty password" $user
		done
	fi
}

function get_ssh_root
{
	# Can you SSH in as root?

	is_running sshd \
	&& CF=$(find_config sshd_config "/etc/ssh /usr/local/openssh/etc" sshd)

	if [[ -n $CF ]]
	then
		egrep -i permitrootlogin $CF | egrep -v "#" | $EGS yes \
			&& SSHRL="yes" || SSHRL="no"
	else
		SSHRL="unknown"
	fi

	disp "SSH root" $SSHRL
}

function get_root_shell
{
	# Only report this if it's not /bin.sh

	ROOT_SHELL=$(egrep ^root: /etc/passwd | cut -d: -f7)
	[[ "$ROOT_SHELL" == "/sbin/sh" ]] || disp "root shell" $ROOT_SHELL
}

function get_snmp
{
	is_running snmpdx && disp "SNMP" "yes"
}

function get_uid_0
{
	# Getting paranoid here, but this reports on any UID=0 accounts that
	# aren't root

	cut -d: -f1,3 /etc/passwd | egrep ":0$" | while read acct
	do
		[[ $acct != "root:0" ]] && disp "UID 0 acct" ${acct%%:*}
	done
}

function get_ports
{
	# Get a list of all the programs with open ports, and stick them in a
	# horrible kind of eval-ey array thing. This is not nice. Skip things
	# that never have open ports, and any process which belongs to us.

	NOTEST_F=" cron ttymon kcfd utmpd logadm nscd zsched ps svc.conf \
	 automoun ksh bash vi vim cluster statd sac sed init rcapd smbiod "
	NOTEST_P=" $(ptree $$ | sed 's/^ *//' | cut -d\  -f1 | tr '\n' ' ' ) "

	if is_root && can_has pfiles
	then

		[[ -n $Z_FLAG ]] && PSFLAGS=$Z_FLAG || PSFLAGS="-e"

		# If we're in a global, filter out everything in a local

		(($OSVERCMP > 59)) && is_global && SED=';/ global$/!d' || SED=''

		ps -e $PSFLAGS -o pid,fname | sed "1d$SED" | while read pid fname z
		do

			[[ $NOTEST_F == *" $fname "* || $NOTEST_P == *" $pid "* ]] \
				&& continue

			timeout_job "pfiles $pid" 4 2>/dev/null | \
			egrep "sockname.*port:" | while read p
			do
				PORT=${p##*: }
				eval port_$PORT=$fname 2>/dev/null
				unset PORT
			done

		done

		# Now ask netstat for the open sockets and see if we can match each
		# one to a program name in the list we just made

		netstat -an | sed -n '/LISTEN/s/^ *\*\.\([0-9]*\).*$/\1/p' \
		| sort -un | while read num
		do
			eval fname='$'"port_$num"
			service=$(sed -n "/$num\/tcp/{p;q;}" /etc/services)
			disp port "${num}:${service%%[$IFS]*}:$fname"
		done

	fi
}

function get_dtlogin
{
	# Get desktop login daemons. Most of these can't report versions, but
	# that doesn't much matter in this context

	typeset -u prt

	for BIN in $(find_bins gdm kdm dtlogin xdm)
	do
		unset prt2
		prt=${BIN##*/}
		[[ $prt == "DTLOGIN" ]] && prt2="CDE dtlogin"

		is_run_ver "dtlogin@$BIN" $BIN "${prt2:-$prt}"
	done
}

function get_cron
{
	# Produce and format a nice list of cron jobs

	ls /var/spool/cron/crontabs | while read user
	do
		# First, join any backslash broken commands. Then, remove any blank
		# or comment lines, and prefix each line with the user who runs the
		# command

		crontab -l $user 2>/dev/null | sed -e :a -e '/\\$/N; s/\\\n//; ta' \
		| sed "/^[$WSP]*$/d;/^[$WSP]*#/d;s/^/$user:/"
	done | while read line
	do
		disp "cron job" "$line"
	done
}
function get_RBAC
{
	# Get non-standard user-attr entries. Not all versions of Solaris have
	# this. This will grow.

	ATTR_FILE=/etc/user_attr

	if [[ -f $ATTR_FILE ]]
	then

		egrep -v "^[$WSP]*#|^$" $ATTR_FILE | while read -r line
		do
			disp user_attr "$line"
		done

	fi
}

function get_jass_appl
{
	# Report when JASS was applied

	ls /etc/*JASS* 2>/dev/null | sed \
	's/^.*JASS\.\([0-9]\{4\}\)\([0-9]\{2\}\)\([0-9]\{2\}\).*$/\1-\2-\3/' \
	| sort -u | while read d
	do
		disp "JASS applied" $d
	done
}

#-- HOSTED TESTS ------------------------------------------------------------

function get_site_apache
{
	# Get the server names and aliases of any sites this box is hosting.
	# Produces output of the form
	#   apache site_name conf_name doc_root
	# and the interface EXPECTS that. Don't change it! We use space
	# separators rather than the usual ":" because site names can have :s
	# in, specifying ports

	# First we have to find Apache conf directories. Start by looking at
	# ../conf from httpd binaries. Oh, but don't do anything if there's no
	# httpd in the PATH

	can_has httpd || return

	for BIN in $(find_bins httpd)
	do
		cdirs="$cdirs ${BIN%/*/httpd}/conf"
	done

	# Then tag on some other places we know from experience and work through
	# them. Ignore anything that doesn't have an httpd.conf

	for cdir in $cdirs /etc/apache /etc/apache2 /config/apache
	do
		[[ -f ${cdir}/httpd.conf ]] || continue

		unset docrt

		# Looking at everything in turn that might be a config file

		find $cdir -type f | egrep -v ARCHIVE | sort -u | while read conf
		do
			# Strip out all the comments from the config file. Easiest just
			# to do this once and create a temp file to work on. Also shift
			# all indented lines right up to the left margin so we can
			# assume all keywords are at the beginnings of lines, and remove
			# trailing whitespace. This all used to be done inside the sed
			# commands that follow, and it wasn't pretty. We'll even remove
			# blank lines while we're at it

			TCF="/tmp/ap_conf$$"
			sed "/^[$WSP]*#/d;s/^[$WSP]*//;s/[$WSP]*$//;/^$/d" $conf >$TCF

			# If what's left has a ServerName directive, let's assume it's a
			# config file. So, if it isn't, forget it

			$EGS -i "^ServerName" $TCF || continue

			# Get the document root. There should only be one of these.

			docrt=$(grep -i "^DocumentRoot" $TCF \
			| sed "s/^.*[$WSP]//;s/\"//g" | head -1)

			# There may be multiple servers in a single vhost config.
			# Aliases can be on multiple backslashed lines, or with repeated
			# entries.  This seems the safest way to do this.

			# First, join together any backslashed broken lines, then grep
			# out any ServerName and ServerAliased lines

			for word in $(sed -e :a -e '/\\$/N; s/\\\n//; ta' $TCF \
			| egrep -i "ServerName|ServerAlias" | sort -u)
			do
				print $word | $EGS -i "ServerName|ServerAlias" && continue
				disp website "apache $word $conf $docrt"
			done

			rm -f $TMP_CONF
		done

	done
}

function get_site_iplanet
{
	# Get info about iPlanet hosted sites.  Produces output of the form:
	#   iplanet site_name instance_name doc_root
	# So far only version 7 is supported. The way info is stored changes
	# between releases.

	for d in $IPL_DIRS
	do

		[[ -d $d ]] || continue

		ls $d | egrep ^https- | while read sd
		do
			x=${d}/${sd}/config/server.xml

			[[ -f $x ]] || continue

			sed -e '1,/<virtual-server>/d' -e 's/<\/.*$//;s/[<>]/ /g' \
			-e '/virtual-server/d' $x | while read a b
			do
				[[ $a == name ]] && in=$b
				[[ $a == "document-root" ]] && dr=$b
				[[ $a == host ]] && hn="$hn $b"
				[[ $a == http-listener-name ]]

				if [[ $a == "" && -n $in ]]
				then

					for i in $hn
					do
						disp "website" iPlanet $i $in $dr
					done

					unset in dr hn
				fi

			done

		done

	done
}

function get_db_mysql
{
	# Get a list of MySQL databases, in a crude way. The "correct" way would
	# be to go through the mysqladmin interface, but to do that we'd have to
	# carry a username and password around, which I don't want to do. This
	# just looks at directories in /data/mysql instead. After all, MySQL is
	# just some big complicated way of getting at flat files... The problem
	# with this approach is that you have to be root.

	# We now look to see if a database has been updated in the last 30 days.
	# if not, we flag it as potentially stale by appending the time of last
	# update

	# Output is of the form
	#  mysql:db_name:db_size:stale

	can_has mysqld && is_root || return

	for BIN in $(find_bins mysqld)
	do
		dbdirs="$dbdirs ${BIN%/*/mysql}/var"
	done

	for dbdir in $dbdirs /data/mysql /var/mysql
	do
		[[ -d $dbdir ]] || continue

		find $dbdir -name \*MYD | sed 's|/[^/]*$||' | sort -u | \
		while read dir
		do
			dbsize=$(du -sh $dir | sed 's/^ *\([0-9\.A-Z]*\).*$/\1/')
			unset dbextra

			if [[ -z $(find $dir -type f -a -mtime -30) ]]
			then
				dbextra="$(ls -lret $dir | tail -1 | tr -s \  | \
				cut -d\  -f6,7,9)"
			fi

			# Print differently if we're parseable

			if [[ -n $OUT_P ]]
			then
				disp "database" "mysql:${dir##*/}:${dbsize}:$dbextra"
			else
				[[ -n $dbextra ]] && dbx="[${dbextra}]"
				disp "database" "${dir##*/} (MySQL) ${dbsize} $dbx"
			fi

		done

	done
}

function get_ai
{
	# Get install services on an AI server. List AI service name with path
	# and architecture. 'installadm list' changed in 11/11

	if can_has installadm
	then
		installadm list 2>/dev/null | sed '1q' | $EGS Port && old=1
		installadm list 2>&1 | egrep "x86|Sparc" | while read n f2 f3 f4 p
		do
			[[ -n $old ]] && x="($f3/$f2)" || x="($f4/$f3) [$f2]"

			disp "AI service" "$n $p $x"
		done

		# Now list the install clients by MAC address, with the associated
		# install service and architecture

		installadm list -c 2>/dev/null | grep : | while read s cm a pth
		do

			# installadm output is not easily parsable. It only prints the
			# install service name once, so we may have to shift the
			# variables along. We also record the last service name seen in
			# ls

			if [[ -z $pth ]]
			then
				pth=$a
				a=$cm
				cm=$s
				s=$ls
			fi

			ls=$cm
			disp "AI client" "$cm $s (${a})"
		done

	fi

}

#-- PATCH AND PACKAGE FUNCTIONS ----------------------------------------------

function get_package_list
{
	# produce a list of all the packages in the zone. We only want their IDs

	if can_has pkgin
	then
		PKGLIST=$(pkgin ls | cut -d\  -f1 | sort -u)
	elif can_has pkg
	then
		PKGLIST=$(pkg list -Hs | sed 's/ .*//' | sort -u)
	elif can_has pkginfo
	then

		# Get a list of partially installed packages so we can alert the
		# user to them

		PARTLIST=" $(pkginfo -p | tr -s \  | cut -d\  -f2 | tr "\n" " ") "
		PKGLIST=$(pkginfo | tr -s \  | cut -d\  -f2 | sort -u)
	fi

	for pkg in $PKGLIST
	do
		[[ "$PARTLIST" == *" $pkg "* ]] \
			&& disp "package" "$pkg (partially installed)" \
			|| disp "package" $pkg
	done
}

function get_patch_list
{
	# produce a list of all the patches in the zone. We only want their IDs

	if can_has patchadd
	then

		patchadd -p 2>/dev/null | sed -n \
		'/^Patch/s/^Patch: \([^ ]*\) .*$/\1/p' | sort -u | while read patch
		do
			disp "patch" $patch
		done

	fi
}

#-----------------------------------------------------------------------------
# SCRIPT STARTS HERE

log "${0##*/} invoked"

# Clean up if we're "ctrl-c"ed

trap 'die "user hit CTRL-C"' 2
# Get options

while getopts "CD:e:f:FhlL:Mo:pjPqR:T:vVz:Z" option 2>/dev/null
do
	case $option in

		"C")	# AM_CHILD - undocumented, used to tell the script it's
				# a copy in a zone
			AM_CHILD=1
			;;

		"D")	# Delay startup by OPTARG seconds
			T_DELAY=$OPTARG
			;;

		"e")	# Path to "extras" file
			UEXTRAS=$OPTARG
			;;

		"f")	# send all output to automatically named files, optionally
				# in the named directory
			OD_R=${OPTARG##* }
			TO_FILE=1
			;;

		"F")	# Force removal of lock file
			rm -f $LOCKFILE
			;;

        "h")    # Usage
            usage
            ;;

		"l")	# display the checks we have
			show_checks
			exit 0
			;;

		"L")	# Set syslog facility
			SYSLOG=$OPTARG
			;;

		"M")
			DO_PLUMB=1
			;;

		"o")	# Omit the following tests
			OMIT_TESTS=" $(print $OPTARG | tr "," " ") "
			Z_OPTS="$Z_OPTS -o $OPTARG"
			;;

		"p")	# We want machine-parsable output, so set a flag which
				# disp() will pick up. Set the Z_OPTS variable in case we
				# need to propagate this option down to local zones
			OUT_P=1
			Z_OPTS="$Z_OPTS -p"
			SHOW_PATH=1
			;;

		"j")    # We want JSON output
			OUT_J=1
			Z_OPTS="$Z_OPTS -j"
			SHOW_PATH=1
			;;

		"P")	# Print PATH information
			SHOW_PATH=1
			;;

		"q")	# Be quiet
			BE_QUIET=1
			;;

		"R")	# Remote copy information
			REM_STR=$OPTARG
			OD_R="${VARBASE}/audit_out"
			TO_FILE=1
			;;

		"T")	# class timeout
			C_TIME=$OPTARG
			Z_OPTS="$Z_OPTS -T $OPTARG"
			;;

		"v")	# Be verbose
			VERBOSE=1
			Z_OPTS="$Z_OPTS -v"
			;;

		"V")	# Print version and exit
			print $MY_VER
			exit 0
			;;

		"z")	# Do a zone
			ZL=$(print $OPTARG | tr "," " ")
			unset RUN_HERE
			;;

		"Z")	# Compress output file
			COMPRESS=1
			;;

		*)	print -u2 "unknown option '$option'"
			exit 2

	esac

done

shift $(($OPTIND - 1))

# We run a lot of pgreps. Make sure we only check the current zone if we're
# on a zoned system by dropping $Z_FLAG into the command.

can_has zonename && Z_FLAG="-z $(zonename)"

# Have we been asked to be quiet?

[[ -n $BE_QUIET ]] && { exec >/dev/null; exec 2>&-; }

# Sanity checking. Args?

[[ $# == 0 ]] && usage
CL=$@

# Timeout?

print $C_TIME | $EGS "^[0-9]*$" || die "Invalid timeout. [${C_TIME}]"

# If we're writing to syslog, does it look like a proper facility?

[[ -n $SYSLOG ]] && [[ $SYSLOG != "local"[0-7] ]] \
	&& die "Invalid syslog facility. [${SYSLOG}]"

[[ -n $OUT_P && -n $OUT_J ]] && die "-p and -j are mutually exclusive."

# Verify classes and zones

if [[ "$CL" == "machine" ]]
then
	is_global || die "machine audits can only be run from the global zone."
	ZL=all
	CL=${CL_LS% }
elif [[ "$CL" == "all" ]]
then
	CL=$CL_LS
else

	for c in $@
	do
		[[ $CL_LS == *" $c "* ]] || die "invalid class. [$c]"
	done

fi

if [[ -n $ZL ]]
then
	is_global || die "-z and machine audits invalid in local zone."
	is_root || die "only root can do -z or full machine audits."

	if can_has zoneadm
	then
		AL=$(print " "$(zoneadm list -i)" ")

		[[ $ZL == "all" ]] \
			&& ZL=$AL \
			|| for z in $ZL
			do
				[[ $AL == *" $z "* ]] || die "invalid zone. [$z]"
			done

	fi

fi

# Get the compression tool, if we need it. We'll always have compress,
# probably gzip

[[ -n $COMPRESS ]] && ! can_has gzip && die "no gzip binary"


# If this zone has been named, remove it from the zlist

if can_has zonename && [[ " $ZL " == *" $(zonename) "* ]]
then
	RUN_HERE=2 &&
	ZL=$(print $ZL | sed "s/$(zonename) //")
fi

# Extras file?

[[ -f $EXTRAS ]] || unset EXTRAS

if [[ -n $UEXTRAS ]]
then
	unset EXTRAS

	[[ -f $UEXTRAS ]] \
		&& EXTRAS=$UEXTRAS \
		|| msg "extras file not found. [${UEXTRAS}]" warn

fi

# Lock-file time. If there already is one, that's an error. If not and we're
# root, make one. Don't worry about lockfiles if we're not root.

if is_root && [[ -f $LOCKFILE ]]
then
	OPID=$(cat $LOCKFILE)
	can_has zonename && OPID="$OPID, zone $(zonename)"
	die "Lock file exists at ${LOCKFILE}. [PID ${OPID}]" 3 1
elif is_root
then
	[[ -d ${LOCKFILE%/*} ]] || mkdir -p ${LOCKFILE%/*} 2>/dev/null
	print $$ >$LOCKFILE 2>/dev/null \
		|| msg "could not create lockfile." warn
fi

# If we've been asked to delay startup, do that now

[[ -n $T_DELAY ]] && sleep $T_DELAY

# Are we writing to files? If so, make the target directory

if [[ -n $TO_FILE ]]
then
	umask 002

	OD=${OD_R}/$HOSTNAME
	mkdir -p $OD
	[[ -d $OD && -w $OD ]] || die "can't write files to ${OD}."

	# parseable audits write to a single file, human-readable to one file
	# per class. It has a header.

	if [[ -n $OUT_J ]]
	then
		OUTFILE="${OD}/${HOSTNAME}.machine.json"
	elif [[ -n $OUT_P ]]
	then
		OUTFILE="${OD}/${HOSTNAME}.machine.saud"
	fi

	exec 3>$OUTFILE

	[[ -n $OUT_P ]] && print -u3 \
		"@@BEGIN_s-audit v-$MY_VER $MY_DATE" $(date "+%Y %m %d %H %M")

	msg "Writing audit data to ${OD}."
else
	exec 3>&1
fi

# Now ZL is the list of zones to do, and RUN_HERE is set if we're doing this
# zone. We can run the checks

[[ -z $OUT_P && -z $OUT_J && -n $TO_FILE ]] && of_h=1

# If we're doing filesystem audits, do we have ZFS datasets?

can_has zpool && (( $(zpool status | wc -l) > 1)) && HAS_ZFS=1

if [[ -n $RUN_HERE ]]
then

	# If we're in a zone, consider the zone name canonical, not the
	# hostname. If the system doesn't have zones, call this zone "global".
	# This is for the benefit of the interface.

	hn=$HOSTNAME

	if can_has zonename
	then
		znm=$(zonename)
		[[ $znm != global ]] && hn=$znm
	else
		# Branded zones don't know they are a zone, so we have to trust that
		# the hostname is the zone name.

		[[ $KERNVER == Generic_Virtual ]] && znm=$HOSTNAME || znm="global"
	fi

	# Don't write head and foot to file for non-parseable, and issue a
	# warning if we're not root

	if [[ -n $OUT_J ]]
	then
		if [[ -z $AM_CHILD ]]
		then
			cat <<-EOJSON
			{
			"header": {
			  "sauditver": "$MY_VER",
			  "hostname": "$HOSTNAME",
			  "starttime": "$(date "+%Y %m %d %H %M %S")"
			},
			EOJSON
		else
			print ","
		fi

		print "\"$znm\": {"
	fi >&3

	for myc in $CL
	do

		[[ -n $of_h ]] && exec 3>"${OD}/${znm}.${myc}.saud"

		[[ -n $VERBOSE ]] && print -u2 "${znm}/$myc"

		WARN=$(nr_warn $myc)

		if [[ -n $WARN ]] && ! is_root
		then
			prt_bar
			print "WARNING: running this script as an unprivileged user may
			not produce a full audit. ${WARN}.\n" | fold -s -w 80 | \
			sed -e "s/[$WSP]\{1,\}/ /g" -e :a -e 's/^.\{1,78\}$/ & /;ta'
			prt_bar
		fi >&2

		# Run the audit class

		{
		class_head $znm $myc

		if [[ $C_TIME != 0 ]]
		then
			timeout_job "run_class $myc" $C_TIME || disp "_err_" "timed out"
		else
			run_class $myc
		fi

		class_foot $znm $myc
		} >&3
	done

	[[ -n $OUT_J && -z $AM_CHILD ]] && print -nu3 "}"

fi

# To audit zones we copy ourselves into the zone's /var/tmp , then run that
# copy via zlogin, capturing the output

if can_has zoneadm
then

	for z in $ZL
	do
		[[ $ZL == "global" ]] && continue

		zoneadm -z $z list -p | cut -d: -f3,4,6 | tr : " " | read zst zr zbr

		# Running zones get properly audited but those which aren't running
		# get some dummy output so the interface can show that the zone
		# exists

		[[ $zst == "running" && $zbr != "lx" ]] && zlive=1 || zlive=""

		if [[ -n $zlive ]]
		then
			zf=${zr}/root/var/tmp/$$${0##*/}$RANDOM
			cp $0 $zf
			chmod 0700 $zf
		fi

		# The only way to get separate files is to run the script multiple
		# times, changing the file descriptor each time. Also run multiple
		# times for a non-running zone

		if [[ -n $of_h || -z $zlive ]]
	 	then

			for c1 in $CL
			do

				[[ -n $of_h ]] && exec 3>"${OD}/${z}.${c1}.saud"

				if [[ -n $zlive ]]
				then
					zlogin -S $z /var/tmp/${zf##*/} $Z_OPTS $c1 \
						|| disp "error" "incomplete audit"
				else
					class_head $z $c1
					disp "hostname" $z
					[[ $zbr == "lx" ]] && disp "zone brand" lx
					disp "zone status" $zst
					get_time
					class_foot $z $c1
				fi >&3

			done

		else
			[[ -n $VERBOSE ]] && print -u2 "Running $zf on $z"
			zlogin -S $z /var/tmp/${zf##*/} $Z_OPTS $CL >&3 \
				|| disp "error" "incomplete audit"
		fi

		[[ -n $zlive ]] && rm $zf
	done

fi

# Write a footer if we've just done a parseable file

[[ -n $TO_FILE && -n $OUT_P ]] && print -u3 "@@END_s-audit"

[[ -n $OUT_J ]] && print -u3 "}"

# Compression

if [[ -n ${OUT_J}$OUT_P && -n $COMPRESS ]]
then
	gzip -f9 $OUTFILE
	OUTFILE=${OUTFILE}.gz
fi

# Do we have to copy the output directory?

if [[ -n $REM_STR ]]
then
	can_has scp	|| die "no scp binary."

	scp -rqCp ${OUTFILE%.*} $REM_STR >/dev/null 2>&1 \
		&& msg "copied data to $REM_STR" \
		|| die "failed to copy data to $REM_STR"
fi

clean_up

exit 0
