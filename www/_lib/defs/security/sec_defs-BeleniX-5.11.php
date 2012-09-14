<?php

//============================================================================
//
// s-audit security definition file for Belenix 5.11.
//
// Generated Fri Sep 14 15:09:09 BST 2012 by s-audit_secdefs.sh
//
//============================================================================

$sec_data = array(

	"user_attrs" => array(
		"adm::::profiles=Log Management",
		"dladm::::auths=solaris.smf.manage.wpa,solaris.smf.modify",
		"lp::::profiles=Printer Management",
		"rob::::profiles=Primary Administrator;roles=root",
		"root::::type=role;auths=solaris.*,solaris.grant;profiles=All;lock_after_retries=no;min_label=admin_low;clearance=admin_high"),

	"users" => array(
		"root (0)",
		"daemon (1)",
		"bin (2)",
		"sys (3)",
		"adm (4)",
		"lp (71)",
		"uucp (5)",
		"nuucp (9)",
		"smmsp (25)",
		"listen (37)",
		"gdm (50)",
		"webservd (80)",
		"postgres (90)",
		"nobody (60001)",
		"noaccess (60002)",
		"nobody4 (65534)"),

	"crontabs" => array(
		"lp:13 3 * * 0 cd /var/lp/logs; if [ -f requests ]; then if [ -f requests.1 ]; then /bin/mv requests.1 requests.2; fi; /usr/bin/cp requests requests.1; >requests; fi",
		"root:10 3 * * * /usr/sbin/logadm",
		"root:15 3 * * 0 [ -x /usr/lib/fs/nfs/nfsfind ] && /usr/lib/fs/nfs/nfsfind",
		"root:30 3 * * * [ -x /usr/lib/gss/gsscred_clean ] && /usr/lib/gss/gsscred_clean",
		"root:1 2 * * * [ -x /usr/sbin/rtc ] && /usr/sbin/rtc -c > /dev/null 2>&1")
);

?>
