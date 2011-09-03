<?php

//============================================================================
//
// s-audit security definition file for Solaris 5.9.
//
// Generated Saturday September  3 00:09:15 BST 2011 by s-audit_secdefs.sh
//
//============================================================================

$sec_data = array(

	"user_attrs" => array(
		"root::::auths=solaris.*,solaris.grant;profiles=All",
		"lp::::profiles=Printer Management",
		"adm::::profiles=Log Management"),

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
		"nobody (60001)",
		"noaccess (60002)",
		"nobody4 (65534)"),

	"crontabs" => array(
		"root:10 3 * * * /usr/sbin/logadm",
		"root:15 3 * * 0 /usr/lib/fs/nfs/nfsfind",
		"root:1 2 * * * [ -x /usr/sbin/rtc ] && /usr/sbin/rtc -c > /dev/null 2>&1",
		"root:30 3 * * * [ -x /usr/lib/gss/gsscred_clean ] && /usr/lib/gss/gsscred_clean",
		"lp:13 3 * * 0 cd /var/lp/logs; if [ -f requests ]; then if [ -f requests.1 ]; then /bin/mv requests.1 requests.2; fi; /usr/bin/cp requests requests.1; >requests; fi")
);

?>
