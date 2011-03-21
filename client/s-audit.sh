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
# v3.0. (c) 2011 SNLTD.
#
#=============================================================================

#-----------------------------------------------------------------------------
# VARIABLES

PATH=/bin:/usr/bin

WSP="	 "
	# A space and a literal tab. ksh88 won't accept \t

MY_VER="3.0"
	# The version of the script. PLEASE UPDATE

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

# Set a big PATH. This should try to include everywhere we expect to find
# software. SPATH is for quicker searching later.

SPATH="/bin /usr/bin /usr/sbin /usr/lib/ssh /usr/xpg6/bin /usr/sun/bin \
	$(find /usr/*/*bin /usr/local/*/*bin /opt/*/*bin /usr/*/libexec \
	/usr/local/*/libexec /opt/*/libexec -prune 2>/dev/null) \
	/usr/netscape/suitespot"

PATH=$(print "$SPATH" | tr " " : | tr "\n" ":")

# Get host information and cache it to save us running uname all over. Also
# strip the dot out of OSVER so we can use it in arithmetic comparisons.
# (And shorten it for 2.5.1.)

uname -pnrvim | read HOSTNAME OSVER KERNVER HW_PLAT HW_CHIP HW_HW
OSVERCMP=$(print $OSVER | tr -d .)

[[ $OSVERCMP == "251" ]] && OSVERCMP="25"

# Paths to special tools

SCADM=/usr/platform/${HW_HW}/sbin/scadm

[[ -x /usr/sbin/prtdiag ]] \
	&& PRTDIAG=/usr/sbin/prtdiag \
	|| PRTDIAG=/usr/platform/${HW_HW}/sbin/prtdiag

IGNOREFS=" dev devfs ctfs mntfs sharefs tmpfs fd objfs proc "
	# Ignore these fs types in the fs audit

# On Nexenta, egrep is really ggrep, and the two have different flags.

egrep --version >/dev/null 2>&1 && EGS="ggrep -q" || EGS="egrep -s"

IPL_DIRS="/opt/SUNWwbsvr /opt/oracle/webserver7 /usr/netscape/suitespot \
	/sun/webserver7"
	# Directories where we look for iPlanet software

EXTRAS="/etc/s-audit_extras"
	# default path for extras file

# DLADM STUFF. This is to handle the ever-changing way in which dladm is
# called. The commands are hard quoted because they're run through eval,
# with the variables filled in then.

# The first block is for "modern" Crossbow, the second for an older revision
# that had different output, the third for old Solaris 10.

[[ -f /usr/sbin/dladm ]] && HAS_DL=1

if [[ -n $HAS_DL ]] && dladm 2>&1 | $EGS "show-ether.*-o"
then

	if dladm show-ether -p >/dev/null 2>&1
	then
		DL_DEVLIST_CMD='dladm show-link -p | cut -d\" -f2'
		DL_TYPE_CMD='dladm show-link -p $S0 | cut -d\" -f4'
		DL_LINK_CMD='dladm show-link -p $S0 | cut -d\" -f8'
		DL_SPEED_CMD='dladm show-ether -p $S0 | cut -d\" -f10'
	else
		DL_DEVLIST_CMD='dladm show-link -po link'
		DL_TYPE_CMD='dladm show-link -po class $S0'
		DL_LINK_CMD='dladm show-link -po state $S0'
		DL_SPEED_CMD='dladm show-ether -pospeed-duplex $S0'
	fi

	DL_ZONE_CMD='dladm show-linkprop -c -pzone -o value $S0 | 
	sed "s/^.*=\"\([^\"]*\)\"/\1/"'

elif [[ -n $HAS_DL ]]
then
	DL_DEVLIST_CMD='dladm show-dev -p | cut -d" " -f1'
	DL_TYPE_CMD='dladm show-link -p $S0 | sed "s/^.*type=\([^ ]*\).*$/\1/"'
	DL_LINK_CMD='dladm show-dev -p $S0 | sed "s/^.*link=\([^ ]*\).*$/\1/"'
	DL_ZONE_CMD='dladm show-linkprop -c $S0 |
	sed -n "/Y=\"zone\"/s/^.*VALUE=\"\([^\"]*\).*$/\1/p"'
	DL_SPEED_CMD='dladm show-dev -p $S0 |
	sed "s/^.*speed=\([^ ]*\).*duplex=\(.*\)$/\1Mb:\2/"'
fi

#-- TEST LISTS ---------------------------------------------------------------

CL_LS=" platform os net fs app tool hosted security patch "
	# All the classes we support

# Here we define the lists of tests which make up the various audits. These
# lists are always bookended by "hostname" and "time". 

G_PLATFORM_TESTS="hardware virtualization cpus memory sn obp alom disks
	optical lux_enclosures tape_drives cards printers" 
L_PLATFORM_TESTS="virtualization printers"

G_NET_TESTS="ntp name_service dns_serv nis_domain name_server nfs_domain
	snmp ports routes nic"
L_NET_TESTS=$G_NET_TESTS

G_OS_TESTS="os_dist os_ver os_rel kernel hostid local_zone ldoms
	scheduler svc_count package_count patch_count uptime "
L_OS_TESTS="os_dist os_ver os_rel kernel hostid svc_count
	package_count patch_count uptime"

L_APP_TESTS="apache coldfusion tomcat iplanet_web nginx mysql_s ora_s
	svnserve sendmail exim cronolog mailman splunk sshd named ssp symon
	samba x vbox"
G_APP_TESTS="vxvm vxfs vcs ldm $L_APP_TESTS nb_c nb_s" 

L_TOOL_TESTS="openssl rsync mysql_c sqlplus svn_c java perl php_cmd python
	ruby cc gcc pca nettracker saudit scat explorer"
G_TOOL_TESTS="sccli sneep vts $L_TOOL_TESTS"

G_HOSTED_TESTS="site_apache site_iplanet db_mysql"
L_HOSTED_TESTS=$G_HOSTED_TESTS

G_PATCH_TESTS="patch_list package_list"
L_PATCH_TESTS=$G_PATCH_TESTS

G_SECURITY_TESTS="users uid_0 empty_passwd authorized_keys ssh_root
	user_attr root_shell dtlogin cron"
L_SECURITY_TESTS=$G_SECURITY_TESTS

L_FS_TESTS="root_fs fs exports"
G_FS_TESTS="zpools capacity $L_FS_TESTS"

#-----------------------------------------------------------------------------
# FUNCTIONS

#-- GENERAL FUNCTIONS --------------------------------------------------------

function disp
{
	# Print information in one of two ways. If OUT_P isn't set, display
	# in a nice tabular way that people will like, otherwise print in a
	# cold, clinical fashion that a heartless computer enjoys.  If SHOW_PATH
	# is set, the path to the binary is printed, presuming it was ever
	# passed.

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

	if [[ -n $val && -n $OUT_P ]]
	then
		[[ -n $SHOW_PATH && -n $pth ]] && sp="@=$pth"
		print "${key}=${val}$sp"
	elif [[ -n $val ]]
	then
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
		(($(my_pgrep "/${ID}/init") == 1)) || RET=1
		IS_GLOBAL=$RET
	fi

	return $RET
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

	print -u2 "ERROR: $1"
	log "$1" err
	clean_up
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

	kill_children $BGP
	kill $BPG 2>/dev/null
}

function get_disk_type
{
	# Try to work out what type of disk (IDE, SCSI, USB etc.) a given cxtx
	# device is

	s=$(ls -l /dev/dsk/${1}s2)

	if [[ $s == *ide@* ]]
	then
		print "IDE" # Also SATA, can't see a way to differentiate
	elif [[ $s == *scsi_vhci* ]]
	then
		print "SCSI VHCI"
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
	else
		print "unknown"
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

	if [[ -n $OUT_P ]]
	then
		print "BEGIN $2@$1"
	elif [[ -z $TO_FILE ]]
	then
		tput bold
		print "\n'$2' audit on $1\n"
		tput sgr0
	fi
}

function class_foot
{
	# Print the information that goes after a class audit
	# $1 is the zone
	# $2 is the class

	if [[ -n $OUT_P ]]
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
		print "Many tests, including ALOM, FC enclosure, virtualization \
		will not be run"
	elif [[ $1 == "net" ]]
	then
		print "NIC information will be limited"
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

function usage
{
	# How to use the script

	cat<<-EOUSAGE

	usage:

	    ${0##*/} [-f dir] [-z all|zone] [-qpPM] [-D secs] [-L facility]
		[-T secs ] [-o test,test,...,test] [-R user@host:dir ] [-e file]
		[-u file] $(print $CL_LS | tr " " "|")|all|machine

	    ${0##*/} -l

	where
	  -f :     write files to an (optionally) supplied local directory.
	           Files are named in the format "audit.hostname.type" where
	           type is either "os" or "software". Without this option
	           output goes to standard out
	  -z :     audit a zone. A single zone name may be supplied, or "-z all"
	           may be used to audit all running zones. The output from "-z
	           all" can be confusing, and it is intended to be run with the
	           -f flag
	  -M :     in network audits, plumb and unplumb uncabled NICs to obtain
	           their MAC address
	  -o :     omit these tests, comma separated
	  -p :     write machine-parseable output. By default output is
	           "prettyfied"
	  -P :     print paths to tools and applications. (Implied by -f.)
	  -q :     be quiet
	  -R :     information for scp to copy audit files to remote host. Of
	           form "user@host:directory"
	  -D :     delay this many seconds before beginning the audit
	  -u :     path to user check file
	  -e :     full path to "extras" file
	  -L :     syslog facility - must be lower case
	  -l :     list tests in each audit type
	  -T :     maximum time, in s, for any audit class
	  -V :     print version and exit

	The final argument tells the script what kind of audit to perform.

	           platform : looks at the box and things attached to it
	           net      : looks at network connections and configuration
	           os       : looks at the OS and virtualizations
	           app      : examines installed versions of a range of
	                      application software
	           tool     : examines installed versions of various tools.
	                      "apps" are normally things which are run as
	                      daemons, "tools" things which are run by a user
	           hosted   : looks at services hosted on the box, like
	                      databases and web sites
	           fs       : local and remote filesystem information
	           plist    : lists installed patches and packages
	           security : examines security issues and NFS
	           machine  : all of the above, for all zones
	           all      : all audit types for the current zone

	"machine" can only be run from a global zone, and performs all the other
	audit types in the global, and every local, zone. It only really makes
	sense when used with the -f flag.

	EOUSAGE
	exit 2
}

#-- AUDITING FUNCTIONS -------------------------------------------------------

# Every check has its own function, called get_something. Just put
# "something" in the appropriate CHECK list at the top of the script

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

		egrep "^$class	" $EXTRAS | while read cl k v
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

#-- PLATFORM AUDITING FUNCTIONS ----------------------------------------------

function get_hardware
{
	# Get a string which identifies the hardware, and pretty it up a bit.
	# This gives the name of the hardware platform, which doesn't always
	# exactly tally with what's printed on the front of the box.  It'll have
	# to be good enough. uname -i works for most things, but not v100s, so
	# there's a special case for those.

	if [[ $HW_PLAT == "SUNW,UltraAX-i2" ]]
	then
		HW_OUT=$($PRTDIAG \
		| sed -n "/Configuration/s/^.*$HW_HW \([^\(]*\).*$/\1/p")
	else
		HW_OUT=$(print $HW_HW | sed 's/SUNW,//;s/-/ /g')
	fi

	[[ $HW_CHIP == "sparc" ]] && CH="SPARC"
	can_has isainfo && BITS=$(isainfo -b)

	disp hardware "$HW_OUT (${BITS:-32}-bit ${CH:-x86})"
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
	elif $EGS Nevada $r
	then
		OS_D="Nevada"
	elif $EGS Community $r
	then
		OS_D="SXCE"
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
	elif $EGS "Solaris 11" $r
	then
		OS_D="Oracle Solaris"
	fi 2>/dev/null

	disp "distribution" ${OS_D:-unknown}
}

function get_os_ver
{
	# Get the SunOS version of the operating system

	if (($OSVERCMP < 511))
	then
		MV=${OSVER#*.}
		(($OSVERCMP < 57)) && MV="2.$MV"
		OS_V="$MV (SunOS $OSVER)"
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
	elif [[ $OS_D == "OpenSolaris" ]]
	then
		[[ -f /etc/release ]] \
			&& OS_R=$(sed '1!d;s/^.*aris \([^ ]*\) .*$/\1/' $r) \
			|| OS_R="unknown"
	elif [[ $OS_D == "BeleniX" ]]
	then
		OS_R=$(sed '1!d;s/^.*iX \([^ ]*\) \(.*\)$/\1 \(\2\)/' $r)
	elif [[ $OS_D == "Nexenta" ]]
	then
		OS_R=$(sed '1!d;s/NexentaCore //' /etc/issue)
	fi

	disp "release" $OS_R
}

function get_hostid
{
	# I don't need to comment this, do I?

	disp "hostid" $(hostid)
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

	swap=$(swap -l 2>/dev/null | sed '1d' | tr -s " " | cut -d\  -f4)

	[[ -n $swap ]] \
		&& disp "memory" "$(num_suff $(($swap * 512)))b swap" \
		|| disp "memory" "no swap space"
}

function get_cpus
{
	# Get CPU information. Solaris 10 has the -p option, and understands the
	# concept of physical and virtual processors

	if (($OSVERCMP >= 510))
	then
		CPUN=$(psrinfo -p)
		CPUC=$(psrinfo -pv 0 | sed '/physical/!d;s/^.*has \([0-9]*\).*$/\1/')
		(($CPUC > 1)) && CPUX="x $CPUC cores"
	else
		CPUN=$(psrinfo | wc -l)
	fi

	disp "CPU" $CPUN $CPUX @ $(psrinfo -v 0 \
	| sed -n '/MHz/s/^.*at \([0-9]*\) .*$/\1/p')MHz
}

function get_alom
{
	# Can we get the ALOM version? We need to be root to even try, and some
	# machines, like T2000s, can't provide it anyway.

	if is_root && [[ -x $SCADM ]]
	then
		disp "ALOM f/w" $($SCADM version | sed -n '/Firmware/s/^.* //p')
		disp "ALOM IP" $($SCADM show netsc_ipaddr | cut -d'"' -f2)
	fi
}

function get_optical
{
	# Counts CD/DVD drives, identifies their type, and says whether or not
	# they have a disk in. eject -q works differently on 2.6

	typeset -i cdc=0

	iostat -En | sed -e'/^c[0-9]/{N;s/\n/ /;}' -e '/Vend/{N;s/\n/ /;}' \
	| egrep "^c" | egrep "DV|CD|COMBO" | while read dev junk
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
	# Solarises, fall back to format(1). I don't think you can get the disk
	# size. On a vbox, only the CD-ROM shows up

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

}

function get_printers
{
	# Get a list of the printers this box can use.
	
	if can_has lpstat
	then
		defp=$(lpstat -d | sed 's/^.*: //')

		lpstat -s | sed -n '/^system /s/^system for \([^:]*\):.*$/\1/p' | \
		while read pr
		do
			[[ $defp == $pr ]] && e=" (default)" || e=""
			disp "printer" "${pr}$e"
		done
	fi

}

function get_lux_enclosures
{
	# Prints a string of the form "Vendor Product-ID (fw REV)" where REV is
	# the firmware revision, for each FC attached device that luxadm can
	# find.

	# Get unique node WWNs, which should identify each attached device.

	luxadm probe 2>/dev/null \
		| sed -n '/Node /s/^.*WWN:\([^ ]*\).*$/\1/p' | sort -u \
		| while read node
	do

		if luxadm display $node | $EGS Vendor:
		then
			luxadm display $node | sed -n -e "/Vendor/s/^.*:[$WSP]*//p" \
				-e "/Product/s/^.*:[$WSP]*//p" \
				-e "/Revision/{s/^.*:[$WSP]*\(.*\)/(fw \1)/p;q;}" \
				| tr '\n' ' ' | tr -s ' '
			print
		else
			print "unidentified (WWN ${node})"
		fi

	done | sort | uniq -c | while read COUNT LUX
	do
		disp "storage" "FC array: $COUNT x $LUX"
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
		TPLST=$(cfgadm -al | \
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


function get_cards
{
	# Have a go at finding cards. 
	
	if [[ -x $PRTDIAG && $HW_HW != "i86pc" ]]
	then
		# SBUS first. Works on the Ultra 2, YMMV.  I'm not very confident
		# about only checking below slot 14

		$PRTDIAG | awk '{ if ($2 == "SBus" && $4 < 14) print $4,$5 }' | \
		sort -u | while read slot type
		do
			disp "card" "$type (SBUS slot $slot)"
		done

		# Now PCI. This is not very reliable. It seems to work well enough
		# on SPARC Solaris 10/11, but it's a dead loss on 8, and doesn't
		# work at all on x86.

		$PRTDIAG | grep "PCI[0-9] *.*(" | sort -u | \
		while read pci hz slot name desc extra
		do
			desc=${desc#\(}
			desc=${desc%\)}
			disp "card" "$desc ($extra $slot@${hz}MHz)"
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

		if [[ $HW_HW == "i86pc" ]]
		then

			# Prtdiag is not supported on x86 Solaris < 10. At the moment I
			# can't work out a bulletproof way to tell whether those old
			# OSes are running on a physical machine or in a virtualbox
			
			if (($OSVERCMP > 59))
			then
				sysconf=$($PRTDIAG | sed 1q)

				if [[ $sysconf == *VirtualBox* ]]
				then
					VIRT="VirtualBox"
				elif [[ $sysconf == *VMware* ]]
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

			if [[ $VIRT == "VirtualBox" ]]
			then
				VBGVER=$(pkginfo -l SUNWvboxguest 2>/dev/null | sed -n \
				'/VERSION/s/VERSION:.* \([^,]*\).*$/\1/p')
				
				[[ -n $VBGVER ]] && VIRT="${VIRT} (guest add. $VBGVER)" 
			fi
				
		elif [[ $HW_PLAT == "sun4v" ]] 
		then

			# Now we look at logical domains. There's only any point doing
			# this if we're on sun4v hardware. If we have the ldm binary,
			# and that can list the primary, then we must be in the primary
			# domain. If we can't run ldm (must be root), look to see if
			# ldmd is running. Finally, ask prtpicl what it can see. In a
			# guest domain it should be able to see the OBP, but not an LED.
			# I do this +ve/-ve test because prtpicl can hang on T2000s.
			# That's why it's run through timeout_job(). If the prtpicl job
			# times out, we play safe and guess that we're in a normal
			# domain. We need a [=] test here.

			if can_has ldm 
			then
				ldm ls primary >/dev/null && VIRT="primary LDOM"
			elif my_pgrep ldmd >/dev/null
			then
				VIRT="primary LDOM"
			elif my_pgrep picld >/dev/null && [ $(timeout_job prtpicl \
			| egrep -c "led \(|obp \(" ) = 1 ]
			then
				VIRT="guest LDOM"
			fi

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

			can_has zoneadm \
				&& VIRT="zone (${ZI}/$(zoneadm list -cp | cut -d: -f6))" \
				|| VIRT="zone (${ZI}/$OSVER)"
		fi

	fi

	disp "virtualization" $VIRT 
}

#-- NETWORK AUDITING FUNCTIONS -----------------------------------------------

function mk_nic_devlist
{
	# Create a list of all network devices on the box. Used by get_nic().
	# Used to be used by get_mac() which no longer exists. Could be
	# reincorporated into get_nic() at some point

	# Get the plumbed interfaces. This'll work on anything, even a zone.

	DLST="$(ifconfig -a | sed '/^[a-z]/!d;/^lo/d;s/: .*//')"

	# Now we need a full interface list. This'll show up things like
	# unused QFE cards. We'll use dladm if we have it. If not, we're going
	# to guess what cards we might have from the contents of /dev. There
	# might be a better way to do it on older revisions, but I don't know
	# what it is. Don't do this in local zones.

	if is_global && is_root && [[ -n $HAS_DL ]]
	then
		DLST2=$(eval $DL_DEVLIST_CMD)
	elif is_global
	then
		DLST2=$(ls /dev | egrep "e1000g|bge|qfe|hme|ce|pcn|ge|dmfe|iprb" \
		| egrep "[0-9]$")
	fi 

	for d in $DLST $DLST2
	do
		print $d
	done | sort -u
}

function get_nic
{
	# Get information on all the network interfaces, whether they're cabled,
	# plumbed, or whatever.

	# Later, we're going to look to see if all the interfaces have an LDOM
	# vswitch on them, so let's get a list of the ones that do

	if is_root && can_has ldm
	then
		VSW_IF_LIST=" $(ldm list-domain -p -o network primary | \
		sed -n '/^VSW/s/^.*net-dev=\([^|]*\).*$/\1/p' | tr '\n' ' ') "
	fi
	
	# Get a list of devices

	DEVLIST=$(mk_nic_devlist)

	# We assemble a groups of strings S1....Sn

	for S0 in $DEVLIST
	do
		unset HNAME S2 S3 S4 S5 S6 SD mac

		# Ask ifconfig for the IP address of this device

		S1="$(ifconfig $S0 2>/dev/null \
			| sed -n '/inet/s/^.*inet \([^ ]*\).*$/\1/p')"
		
		# Look to see if an interface is a vlan, vnic, etherstub or
		# whatever. This needs privs on some OSes, so silence the error

		[[ $S0 == *:* ]] || ! is_global \
		|| nic_type=$(eval $DL_TYPE_CMD 2>/dev/null)

		if [[ -n $S1 ]]
		then

			# This interface has an address. Does it belong to a zone? If it
			# does, tag the zone name on to the address, (in brackets). If
			# not, look to see if the address is 0.0.0.0, which it will be
			# if the interface is not configured in this zone.

			if ifconfig $S0 | $EGS zone
			then
				S3=$(ifconfig $S0 | sed -n 's/^.*zone \([^ ]*\).*$/\1/p')
			else
				[[ $S1 == "0.0.0.0" ]] && S1="unconfigured in global"
			fi

			# Is this address part of an IPMP group?

			IPMP=$(ifconfig $S0 | grep -w groupname)

			[[ -n $IPMP ]] && S5=${IPMP##* }

			# Is this address under the control of DHCP?

			ifconfig $S0 | $EGS DHCP && S5="DHCP"

			# Denote VLANned interfaces

			[[ $nic_type == "vlan" ]] && S6="vlan"

			# Finally, record the hostname, if we can. DHCP connected
			# interfaces may not have a proper hostname.dev file, so we'll
			# use the uname output from earlier. There may be extra stuff in
			# the hostname file for IPMP, so we just take the first word

			if [[ -s /etc/hostname.$S0 ]]
			then
				HNAME=$(sed '1!d;s/[  ].*$//' /etc/hostname.$S0)
				S3=${HNAME:-$HOSTNAME}
			fi

		else
			# We hit this if ifconfig couldn't get an address for this
			# interface. In the old days we'd assume that meant the
			# interface wasn't in use, but now it could be anything.

			# LDOM virtual switches seem to be automatically called "vswx"

			if [[ $S0 == "vsw"* ]]
			then
				S1="vswitch"

				# If we can run the ldm program, we can find out which
				# device this switch is bound to

				if is_root && can_has ldm
				then
					S1="$S1 on $(ldm ls-domain -p -o network primary \
					| sed -n \
					"/dev=switch@${S0#vsw}/s/^.*net-dev=\([^|]*\).*$/\1/p")"
				fi

			elif [[ $nic_type == "etherstub" ]]
			then
				S1="etherstub"
			elif is_root && is_global && [[ -n $HAS_DL ]] \
				&& dladm show-link $S0 >/dev/null 2>&1
			then
				# If the interface is "up" then we can assume it's doing
				# something. If not, call it "uncabled" and give up

				if [[ $(eval $DL_LINK_CMD) == "up" ]]
				then

					# Is the interface owned by a zone? If it is, it must be
					# for an exclusive IP instance

					IPZONE=$(eval $DL_ZONE_CMD)

					if [[ -n $IPZONE ]]
					then
						S1="exclusive IP"
						S3=$IPZONE
					else
						# It's not a zone exclusive IP, and it's not an LDOM
						# switch. Let's just call it "unconfigured".
						# Either it's not in use, or it's been VLANned

						S1="unconfigured"
					fi

				else
					S1="uncabled" # interface is down
				fi

			else
				S1="uncabled"
			fi

			[[ $nic_type == "vnic" ]] \
				&& S6="vnic over $(dladm show-link -poover $S0)"

		fi

		# Did the interface have a vswitch on it?

		[[ $VSW_IF_LIST == *" $S0 "* ]] && S6="+vsw"

		# So long as the interface isn't "uncabled", and is a physical port,
		# we can probably get its speed. Some interfaces, we can't

		if [[ $S0 == *"pcn"* ]]
		then
			S4="unknown"
		elif [[ $nic_type == "vnic" || $nic_type == "etherstub" ]]
		then
			:
		elif [[ $S1 != "uncabled" ]] && is_root && is_global
		then

			# Use dladm if we can. If not, try kstat

			if [[ -n $HAS_DL ]] && eval $DL_DEVLIST_CMD | $EGS "^$S0"
			then
				S4=$(eval $DL_SPEED_CMD)
			elif can_has kstat
			then
				KDEV=${S0%[0-9]*}

				if [[ -a /dev/$KDEV ]] && ! ifconfig $S0 | $EGS FAILED
				then
					KI=${S0#$KDEV}
					DUP=$(kstat -m $KDEV -i $KI \
						| sed -n '/link_duplex/s/^.* //p')

					[[ $DUP == 2 ]] && SD="full" || SD="half"

					S4="$(kstat -m $KDEV -i $KI \
					| sed -n '/ifspeed/s/^.* //p')-$SD"
				fi

			fi

		fi

		# Turns out you can't (currently?) get the link speed from an LDOM,
		# and the message makes it look a bit like there's no connection.
		# So...

		[[ $S4 == *"unknown"* ]] && S4="unknown"

		# S2 going to be the MAC address

		if is_root
		then
			mac=$(ifconfig $S0 2>/dev/null | sed -n "/ether/s/^.*r //p")

			# If the interface is down, we can quickly plumb it to get its
			# MAC.  This is a "destructive" action, and is only done if the
			# DO_PLUMB variable is defined

			if [[ -n $DO_PLUMB && -z $mac ]]
			then
		
				# ifconfig exits 0 even if it can't plumb an interface. But
				# it does complain in that case

				tstr=$(ifconfig $S0 plumb 2>&1)

				if [[ -z $tstr ]]
				then
					mac=$(ifconfig $S0 2>/dev/null | \
					sed -n "/ether/s/^.*r //p")
					ifconfig $S0 unplumb
				fi

			fi

		fi

		S2=${mac:-unknown}

		# Now, depending the kind of output we're producing, we either trim
		# up the S variables, or pass a string to disp

		if [[ -n $OUT_P ]]
		then
			disp NIC "$S0|$S1|$S2|$S3|$S4|$S5|$S6"
		else
			[[ -n $S3 ]] && S3="($S3)"
			[[ -n $S4 ]] && S4="[$S4]"

			disp NIC $S0 $S1 $S2 $S3 $S4 $S5 $S6
		fi

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

	egrep "^hosts|^passwd|attr" /etc/nsswitch.conf | while read a
	do
		disp "name service" $(print $a | sed 's/\[NOTFOUND=return\]//')
	done
}

function get_nis_domain
{
	can_has domainname && disp "NIS domain" $(domainname)
}

function get_routes
{
	# Routing table. Flag up default routes not in defaultrouters and non
	# .0 routes which are persistent

	route -p show >/dev/null 2>&1 && HAS_PER=1

	netstat -nr | egrep -v '127.0.0.1|224.0.0.0' | \
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

	if can_has dpkg
	then
		PKGS="$(dpkg -l | wc -l) [dpkg]"
	elif can_has pkg
	then
		can_has pkg && PKGS="$(pkg list -Hs | wc -l) [ipkg]"
	elif can_has pkginfo
	then
		PARTIAL=$(pkginfo -p | wc -l)
		PKGS=$(pkginfo | wc -l)
	
		(($PARTIAL > 0)) && PKGS="$PKGS (${PARTIAL## * } partial)"

		PKGS="$PKGS [SVR4]"
	fi

	disp "packages" $PKGS
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
			unset ccpu dcpu phy swp xt
			print $(zonecfg -z $zn info capped-cpu) | read a b ccpu
			print $(zonecfg -z $zn info dedicated-cpu) | read a b dcpu
			print $(zonecfg -z $zn info capped-memory) | read a b phy s swp

			[[ -n $ccpu ]] && xt=" ${ccpu%]}CPU"
			[[ -n $dcpu ]] && xt=" ${dcpu%]} dedicated CPU"

			[[ -n "${phy}$swp" ]] && xt="$xt ${phy:-}/${swp:-}"

			[[ -n $xt ]] && xt=":${xt# }"

			disp "local zone" "$zn (${zbrand}:${zstat}${xt}) [$zpth]"
		done

	fi
}

function get_ldoms
{
	# A list of logical domains on this server

	if is_root && can_has ldm
	then

		ldm ls | sed 1d | while read n st fl co cp m x
		do
			disp LDOM "$n (${cp}vCPU/${m}:${st}) [port $co]"
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

		pv=$(strings $pl | sed -n '/^X-Powered/s/.*\///p')
		
		[[ -z $pv ]] \
			&& pv=$(strings $pl | egrep "^[3-5]\.[0-9]\.[0-9]*$" | head -1)

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

function get_mysql_s
{
	# Get the version of any MySQL server software we find

	for BIN in $(find_bins mysqld)
	do
		is_run_ver "MySQL server@$BIN" $BIN $($BIN -V | \
		sed 's/^.*Ver \([^ -]*\).*$/\1/')
	done
}

function get_ora_s
{
	# Get Oracle server version. Assume it's running if there's a listener,
	# and that lsnrctl gives us the server version. Get the base directories
	# out of the oratab

	if [[ -f /var/opt/oracle/oratab ]]
	then

		egrep -v "^#|^[${IFS}]*$" /var/opt/oracle/oratab | cut -d: -f2 | \
		while read oh
		do
			[[ -f ${oh}/bin/tnslsnr ]] \
				&& is_run_ver "Oracle" tnslsnr $(ORACLE_HOME=$oh \
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
			sed '1!d;s/^.* //')
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
		XVER="Xorg-$(Xorg -version 2>&1 | sed '2!d;s/^.* //')"
	elif can_has Xsun
	then
		XBIN=Xsun
		XVER="Xsun"
	fi

	[[ -n $XBIN ]] && is_run_ver "X server" $XBIN "$XVER"
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

function get_vxvm
{
	# Get the version of Veritas Volume Manager. AFAIK Veritas don't supply
	# a command-line tool to do this, so I'm going to get the version of the
	# kernel module.

	is_run_ver "VxVm" vxconfigd \
		$(modinfo | sed -n '/ vxio /s/^.*VxVM \([^ ]*\).*$/\1/p')
}

function get_vxfs
{
	# Get the version of Veritas Filesystem on the box. As with volume
	# manager, the best way I can find to do this is by querying the loaded
	# module list. Surely there's a better way that that?

	VXFS_VER=$(modinfo | egrep vxfs)
	VXFS_VER=${VXFS_VER##* }
	disp "VxFS" ${VXFS_VER%,*}
}

function get_vcs
{
	# had is capable of displaying a version number, so we'll just do that.
	# Report "not running" if hashadow isn't in the process table

	can_has had && is_run_ver "VCS" hashadow $(had -version)
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

function get_ldm
{
	# Get the version of the LDOM management software on the box

	for BIN in $(find_bins ldm)
	do
		is_run_ver "ldm@$BIN" ${BIN}d $($BIN --version \
		| sed -n '/^Logic/s/^.*(v \([^)]*\).*/\1/p')
	done
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

function get_perl
{
	# Get the version of Perl

	for BIN in $(find_bins perl)
	do
		PV="$($BIN -V:version)" && PV=${PV%\'*}
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
	# compiler this week. Definitely works for 5, 11 and 12.

	for BIN in $(find_bins cc)
	do
		# Is this really Sun CC?

		cc 2>&1 | $EGS details$ || continue

		CCDIR=${BIN%/*}
		CC_LIST="(C"
		CC_INFO=$($BIN -V 2>&1 | sed 1q)
		CC_MAJ_VER=$(print $CC_INFO | cut -d\  -f4)

		if [[ $CC_INFO == *Patch* ]]
		then
			CC_MIN_VER=${CC_INFO% *}
			CC_MIN_VER=" ${CC_MIN_VER##* }"
		fi

		# Look to see what options we have. C/C++/Fortran

		[[ -x ${CCDIR}/CC ]] && CC_LIST="${CC_LIST}, C++"
		[[ -x ${CCDIR}/f77 ]] && CC_LIST="${CC_LIST}, Fortran"
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

function get_pca
{
	# Get the version of PCA, if it's installed

	for BIN in $(find_bins pca)
	do
		PCA_VER=$($BIN --version 2>/dev/null)
		disp "PCA@$BIN" ${PCA_VER#* }
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

function get_scat
{
	# Get the version of Solaris CAT

	for BIN in $(find_bins scat)
	do
		disp "SunCAT@$BIN" $(print | $BIN \
		| sed '2!d;s/^.*CAT \([^ ]*\).*$/\1/')
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

function get_explorer
{
	# Get the version and the installation status of Sun Explorer. It can be
	# installed but not configured, which means you can't get the version
	# straight from the main executable.

	for BIN in $(find_bins explorer)
	do
		EXD=$(which explorer | sed "s|/bin/explorer||")
		EXV=$(${EXD}/bin/explorer -V 2>&1 | sed -n '/^Explorer/s/^.*: //p')

		# If we didn't get the version just now, assume it's not been
		# configured, and get the version somewhere else

		if [[ -z $EXV ]]
		then
			EXV="$(sed -n '/EXP_VERSION=/s/^.*=//p' \
			${EXC}/bin/core_check.sh) (unconfigured)"
		fi

		disp "Explorer@$BIN" $EXV
	done
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

		find ${dbdir}/* -name \*MYD | sed 's|/[^/]*$||' | sort -u | \
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

#-- FILESYSTEM AUDIT FUNCTIONS -----------------------------------------------

function get_capacity
{
	# Get the available and used disk space on the server. Here, "available"
	# means the raw capacity of the disk, not the unused space.  Typesetting
	# avail and used to -i causes problems on ksh93 with large disks
	
	typeset avail=0 used=0

	# If there are zpools, get those
	
	if can_has zpool
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
		df -kF$fstyp | sed '1d'
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
	# "zpool get" in early ZFS releases

	if can_has zpool
	then
		# First get the current highest supported zpool version, so we can
		# flag pools running something different. Can't always get this.

		zpool help 2>&1 | $EGS get \
			&& zpsup=$(zpool upgrade -v \
			| sed '1!d;s/^.*version \([0-9]*\).*$/\1/')

		# zpool's get command doesn't have the -H and -o options, so this is
		# harder than it need be. 

		zpool list -Ho name,health | while read zp st
		do

			if [[ $st == "FAULTED" ]]
			then
				disp "zpool" $zp $st
			else

				if [[ -n $zpsup ]]
				then
					zpool get version,size $zp | sed '1d;$!N;s/\n/ /' \
					| read a b zv c d e zs f
					zpext="$zp (${zs}) [${zv}/${zpsup}]"
				fi

				# Add on the date of the last scrub

				lscr=$(zpool status $zp | sed -n '/scrub:/s/^.*s on //p')

				[[ -z $lscr ]] && lscr="none"

				zpext="$zpext $st (last scrub: $lscr)"

				disp "zpool" $zpext
			fi

		done

	fi
}

function get_root_fs
{
	# Get the filesystem type for / and say if it's mirrored or not

	RFS=$(df -n / | sed 's/^.* : \([^ ]*\).*$/\1/')
	RDEV=$(df -e / | sed '1d;s/ .*//')

	if [[ $RFS == "ufs" ]] && [[ $RDEV == */md/* ]]
	then
		metastat ${RDEV##*/} | $EGS "^${RDEV##*/}: Mirror" \
			&& FSM="(mirrored)"
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

	if can_has zfs
	then
		ZPL="compression,quota"

		zfs get help 2>&1 | $EGS dedup && ZPL="${ZPL},dedup"
		zfs get help 2>&1 | $EGS zoned && ZPL="${ZPL},zoned"

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

		# we get extra info for ZFS filesystems

		if [[ $typ == "zfs" && $mdv != "/" ]]
		then
			zfs get -Hp -o property,value $ZPL $mdv | while read p v
			do
				zx="${zx}${p}=${v},"
			done

			extra="${extra}:${zx%,}$zsup"

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

		disp "fs" "$mpt $typ [$dfs] (${mdv}:${mo}$extra)$vf"
	done
}

function get_exports
{
	# Get exported filesystems. This is an amalgamation of the old Samba and
	# NFS share functions, plus some extra
	# Format is of the form
	#   what_is_shared fs_type (options) [name]

	# NFS first. Only global zones can export filesystems

	if is_global
	then
		share | while read a dir opts desc
		do
			txt="$dir nfs (${opts})"
			[[ $desc != '""' ]] && txt="$txt [${desc}]"
			disp "export" $txt
		done
	fi

	# Now Samba. Query the config file, because smbclient may not be
	# available, or may need a password. Just shows share name and path for
	# now

	if can_has smbd
	then
		SCF=$(smbd -b | sed -n '/CONFIGFILE/s/^.*: //p')

		if [[ -f $SCF ]]
		then
			sed -e '/\[global\]/d' -e '/^\[/b' -e '/path/b' -e d $SCF \
			| sed -e :a -e '/\]$/N; s/\]\n//; ta' \
			| sed 's/^\[//;s/path *= */ /' | while read name dir
			do
				disp "export" "$dir smb [${name}]"
			done
		fi

	fi

	# Now iSCSI. Just shows the ZFS dataset and the value of the shareiscsi
	# property

	if can_has zfs && zfs get 2>&1 | $EGS iscsi
	then

		zfs get -H -po name,value shareiscsi \
		| sed '/@/d;/off$/d' | while read fs st
		do
			disp "export" "$fs iscsi ($st)"
		done

	fi
	
	# Now virtual disks in an LDOM primary

	if can_has ldm && is_root
	then
		LDMS=$(ldm ls | sed '1,2d;s/ .*$//')

		ldm ls-services -p \
		| sed -n '/vol=/s/^|vol=\([^|]*\).*dev=\([^|]*\).*$/\1 \2/p' | \
		while read vol dev
		do
			own=unassigned

			for ldm in $LDMS
			do
				ldm ls-constraints -p $ldm | $EGS "^VDISK\|name=${vol}\|" \
					&& own=$ldm
			done

			disp "export" "$vol vdisk (${dev} bound to ${own})"
		done

	fi
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

function get_user_attr
{
	# Get non-standard user-attr entries. Not all versions of Solaris have
	# this

	ATTR_FILE=/etc/user_attr

	if [[ -f $ATTR_FILE ]]
	then

		egrep -v "^[$WSP]*#|^$" $ATTR_FILE | while read line
		do
			disp user_attr "$line"
		done

	fi
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
	# horrible kind of eval-ey array thing. This is not nice

	if is_root && can_has pfiles
	then

		[[ -n $Z_FLAG ]] && PSFLAGS=$Z_FLAG || PSFLAGS="-e"

		ps $PSFLAGS -o pid,fname | while read pid fname
		do

			pfiles $pid 2>/dev/null | egrep "sockname.*port:" | while read p
			do
				PORT=${p##*: }
				eval port_$PORT=$fname
				unset PORT
			done

		done

		# Now ask netstat for the open sockets. and see if we can match each
		# one to a program name, using the list we just made

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

#-- PATCH AND PACKAGE FUNCTIONS ----------------------------------------------

function get_package_list
{
	# produce a list of all the packages in the zone. We only want their IDs

	if can_has pkg
	then
		PKGLIST=$(pkg list -Ha | pkg list -Hs | sed 's/ .*//' | sort -u)
	else

		# Get a list of partially installed packages so we can alert the
		# user to them

		PARTLIST=" $(pkginfo -p | tr -s \  | cut -d\  -f2) "
		PKGLIST=$(pkginfo | tr -s \  | cut -d\  -f2 | sort -u)
	fi

	for pkg in $PKGLIST	
	do
		[[ $PARTLIST == *" $pkg "* ]] \
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

while getopts "D:e:f:lL:Mo:pPqR:T:uVz:" option 2>/dev/null
do
	case $option in

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
			;;

		"p")	# We want machine-parsable output, so set a flag which
				# disp() will pick up. Set the Z_OPTS variable in case we
				# need to propagate this option down to local zones
			OUT_P=1
			Z_OPTS="-p"
			SHOW_PATH=1
			;;

		"P")	# Print PATH information
			SHOW_PATH=1
			;;

		"q")	# Be quiet
			BE_QUIET=1
			;;

		"R")	# Remote copy information
			REMOTE_STR=$OPTARG
			OD_R="${VARBASE}/audit_out"
			TO_FILE=1
			;;

		"T")	# class timeout
			C_TIME=$OPTARG
			;;

		"u")	# User check file
			UCHK=$OPTARG
			;;

		"V")	# Print version and exit
			print $MY_VER
			exit 0
			;;

		"z")	# Do a zone
			ZL=$(print $OPTARG | tr "," " ")
			unset RUN_HERE
			;;

		*)	usage

	esac

done

shift $(($OPTIND - 1))

# Have we been asked to be quiet?

[[ -n $BE_QUIET ]] && { exec >/dev/null; exec 2>&-; }

# We run a lot of pgreps. Make sure we only check the current zone if we're
# on a zoned system by dropping $Z_FLAG into the command.

can_has zonename && Z_FLAG="-z $(zonename)"

# Sanity checking. Args?

[[ $# == 0 ]] && usage
CL=$@

# Timeout?

print $C_TIME | $EGS "^[0-9]*$" || die "Invalid timeout. [${C_TIME}]"

# If we're writing to syslog, does it look like a proper facility?

[[ -n $SYSLOG ]] && [[ $SYSLOG != "local"[0-7] ]] \
	&& die "Invalid syslog facility. [${SYSLOG}]"

# Verify classes and zones

if [[ "$CL" == "machine" ]]
then
	is_global || die "machine audits can only be run from the global zone."
	ZL=all
	CL=$CL_LS
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
	is_global || die "-z option invalid in local zones."
	is_root || die "only root can use -z option."

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
	die "Lock file exists at ${LOCKFILE}. [PID ${OPID}]"
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

	if [[ -n $OUT_P ]]
	then
		exec 3>"${OD}/${HOSTNAME}.machine.saud"
		print -u3 "@@BEGIN_s-audit v-$MY_VER "$(date "+%Y %m %d %H %M")

	fi

	msg "Writing audit files to ${OD}."
else
	exec 3>&1
fi

# Now ZL is the list of zones to do, and RUN_HERE is set if we're doing this
# zone. We can run the checks

[[ -z $OUT_P && -n $TO_FILE ]] && of_h=1

if [[ -n $RUN_HERE ]]
then

	# Don't write head and foot to file for non-parseable, and issue a
	# warning if we're not root

	for myc in $CL
	do
		[[ -n $ofh ]] && exec 3>"${OD}/${HOSTNAME}.${myc}.saud"

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
		class_head $HOSTNAME $myc

		if [[ $C_TIME != 0 ]]
		then
			timeout_job "run_class $myc" $C_TIME || disp "_err_" "timed out"
		else
			run_class $myc
		fi

		class_foot $HOSTNAME $myc
		} >&3
	done

fi

# To audit zones we copy ourselves into the zone root, then run that copy
# via zlogin, capturing the output

can_has zoneadm && for z in $ZL
do
	[[ $ZL == "global" ]] && continue

	zoneadm -z $z list -p | cut -d: -f3,4,6 | tr : " " | read zst zr zbr

	# Running zones get properly audited but those which aren't running get
	# some dummy output so the interface can show that the zone exists

	[[ $zst == "running" && $zbr != "lx" ]] && zlive=1 || zlive=""

	if [[ -n $zlive ]]
	then
		zf=${zr}/root/$$${0##*/}$RANDOM
		cp -p $0 $zf
	fi

	# The only way to get separate files is to run the script multiple
	# times, changing the file descriptor each time. Also run multiple times
	# for a non-running zone

	if [[ -n $ofh || -z $live ]]
	then

		for c1 in $CL
		do

			[[ -n $of_h ]] && exec 3>"${OD}/${z}.${c1}.saud"

			if [[ -n $zlive ]] 
			then
				zlogin $z /${zf##*/} $Z_OPTS $c1 \
				|| disp "error" "incomplete audit"
			else
				class_head $z $c1
				disp "hostname" $z
				disp "zone status" $zst
				get_time
				class_foot $z $c1
			fi >&3

		done

	else
		zlogin $z /${zf##*/} $Z_OPTS $CL >&3 \
			|| disp "error" "incomplete audit"
	fi

	[[ -n $zlive ]] && rm $zf
done 

# Write a footer if we've just done a parseable file

[[ -n $TO_FILE && -n $OUT_P ]] && print -u3 "@@END_s-audit"

# Do we have to copy the output directory?

if [[ -n $REMOTE_STR ]]
then
	can_has scp	|| die "no scp binary."

	scp -rqCp $DP $REMOTE_STR >/dev/null 2>&1 \
		&& msg "copied data to $REMOTE_STR" \
		|| die "failed to copy data to $REMOTE_STR"
fi

clean_up
exit 0
