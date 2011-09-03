<?php

//============================================================================
//
// s-audit security definition file for OpenSolaris 5.11.
//
// Generated Sat Sep  3 00:40:08 BST 2011 by s-audit_secdefs.sh
//
//============================================================================

$sec_data = array(

	"user_attrs" => array(
		"adm::::profiles=Log Management",
		"daemon::::auths=solaris.smf.manage.ilb,solaris.smf.modify.application",
		"dladm::::auths=solaris.smf.manage.wpa,solaris.smf.modify",
		"lp::::profiles=Printer Management",
		"postgres::::type=role;profiles=Postgres Administration,All",
		"root::::type=role;auths=solaris.*,solaris.grant;profiles=All;lock_after_retries=no;min_label=admin_low;clearance=admin_high",
		"zfssnap::::type=role;auths=solaris.smf.manage.zfs-auto-snapshot;profiles=ZFS File System Management"),

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
		"smmsp (25)",
		"listen (37)",
		"gdm (50)",
		"zfssnap (51)",
		"upnp (52)",
		"xvm (60)",
		"mysql (70)",
		"openldap (75)",
		"webservd (80)",
		"postgres (90)",
		"svctag (95)",
		"unknown (96)",
		"nobody (60001)",
		"noaccess (60002)",
		"nobody4 (65534)"),

	"crontabs" => array(
		"root:10 3 * * * /usr/sbin/logadm",
		"root:15 3 * * 0 [ -x /usr/lib/fs/nfs/nfsfind ] && /usr/lib/fs/nfs/nfsfind",
		"root:30 3 * * * [ -x /usr/lib/gss/gsscred_clean ] && /usr/lib/gss/gsscred_clean",
		"root:30 0,9,12,18,21 * * * /usr/lib/update-manager/update-refresh.sh")
);

?>
