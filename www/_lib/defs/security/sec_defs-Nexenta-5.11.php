<?php

//============================================================================
//
// s-audit security definition file for Nexenta 5.11.
//
// Generated Fri Sep 14 13:31:39 BST 2012 by s-audit_secdefs.sh
//
//============================================================================

$sec_data = array(

	"user_attrs" => array(
		"adm::::profiles=Log Management",
		"daemon::::auths=solaris.smf.manage.ilb,solaris.smf.modify.application",
		"dladm::::auths=solaris.smf.manage.wpa,solaris.smf.modify",
		"lp::::profiles=Printer Management",
		"root::::auths=solaris.*,solaris.grant;profiles=All;lock_after_retries=no;min_label=admin_low;clearance=admin_high",
		"zfssnap::::type=role;auths=solaris.smf.manage.zfs-auto-snapshot;profiles=ZFS File System Management"),

	"users" => array(
		"root (0)",
		"daemon (1)",
		"bin (2)",
		"sys (3)",
		"adm (4)",
		"uucp (5)",
		"man (6)",
		"lp (7)",
		"mail (8)",
		"nuucp (9)",
		"uucp (5)",
		"proxy (13)",
		"sync (14)",
		"dladm (15)",
		"news (17)",
		"smmsp (25)",
		"www-data (33)",
		"backup (34)",
		"listen (37)",
		"list (38)",
		"irc (39)",
		"gnats (41)",
		"games (42)",
		"gdm (50)",
		"webservd (80)",
		"svctag (95)",
		"nfs (60001)",
		"noaccess (60002)",
		"nobody (65534)",
		"messagebus (100)"),

	"crontabs" => array(
		"root:10 3 * * * /usr/sbin/logadm",
		"root:15 3 * * 0 [ -x /usr/lib/fs/nfs/nfsfind ] && /usr/lib/fs/nfs/nfsfind",
		"root:30 3 * * * [ -x /usr/lib/gss/gsscred_clean ] && /usr/lib/gss/gsscred_clean",
		"root:1 2 * * * [ -x /usr/sbin/rtc ] && /usr/sbin/rtc -c > /dev/null 2>&1")
);

?>
