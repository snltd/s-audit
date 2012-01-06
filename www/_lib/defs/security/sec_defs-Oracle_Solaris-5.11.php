<?php

//============================================================================
//
// s-audit security definition file for Solaris 5.11.
//
// Generated Wed Dec 28 00:56:59 GMT 2011 by s-audit_secdefs.sh
//
//============================================================================

$sec_data = array(

	"users" => array(
		"root (0)",
		"daemon (1)",
		"bin (2)",
		"sys (3)",
		"adm (4)",
		"lp (71)",
		"uucp (5)",
		"nuucp (9)",
		"dladm (15)",
		"netadm (16)",
		"netcfg (17)",
		"smmsp (25)",
		"listen (37)",
		"gdm (50)",
		"zfssnap (51)",
		"upnp (52)",
		"mysql (70)",
		"openldap (75)",
		"webservd (80)",
		"postgres (90)",
		"svctag (95)",
		"unknown (96)",
		"nobody (60001)",
		"noaccess (60002)",
		"nobody4 (65534)",
		"pkg5srv (97)"
	),

	"crontabs" => array(
		"root:10 3 * * * /usr/sbin/logadm",
		"root:15 3 * * 0 [ -x /usr/lib/fs/nfs/nfsfind ] && /usr/lib/fs/nfs/nfsfind",
		"root:30 3 * * * [ -x /usr/lib/gss/gsscred_clean ] && /usr/lib/gss/gsscred_clean")

);

?>
