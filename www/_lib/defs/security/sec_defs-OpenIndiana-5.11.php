<?php

//============================================================================
//
// s-audit security definition file for OpenIndiana 5.11.
//
// Generated September 14, 2012 02:27:57 PM BST by s-audit_secdefs.sh
//
//============================================================================

$sec_data = array(

	"user_attrs" => array(
		"adm::::profiles=Log Management",
		"daemon::::auths=solaris.smf.manage.ilb,solaris.smf.modify.application",
		"dladm::::auths=solaris.smf.manage.wpa,solaris.smf.modify",
		"lp::::profiles=Printer Management",
		"netadm::::type=role;project=default;profiles=Network Autoconf Admin,Network Management,Service Management",
		"netcfg::::type=role;project=default;profiles=Network Autoconf User;auths=solaris.network.autoconf.write",
		"root::::auths=solaris.*,solaris.grant;profiles=All;audit_flags=lo\:no;lock_after_retries=no;min_label=admin_low;clearance=admin_high",
		"root::::min_label=admin_low;lock_after_retries=no;auths=solaris.*,solaris.grant;audit_flags=lo\:no;profiles=All;clearance=admin_high;type=role",
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
		"netadm (16)",
		"netcfg (17)",
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
		"nobody4 (65534)",
		"pkg5srv (97)"),

	"crontabs" => array(
		"lp:13 3 * * 0 cd /var/lp/logs; if [ -f requests ]; then if [ -f requests.1 ]; then /bin/mv requests.1 requests.2; fi; /usr/bin/cp requests requests.1; >requests; fi",
		"root:10 3 * * * /usr/sbin/logadm",
		"root:15 3 * * 0 [ -x /usr/lib/fs/nfs/nfsfind ] && /usr/lib/fs/nfs/nfsfind",
		"root:30 3 * * * [ -x /usr/lib/gss/gsscred_clean ] && /usr/lib/gss/gsscred_clean")
);

?>
