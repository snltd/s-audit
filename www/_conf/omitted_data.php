<?php

//============================================================================
//
// omitted_data.php
// ----------------
//
// By default we don't report on standard users, cron jobs and user_attrs.
// They're on all Solaris boxes, and shouldn't be of interest. If you
// want to report them, remove them from the following arrays.
//
// R Fisher 02/10
//
// v1.0  Initial release
//
//============================================================================

class omitData {

	// The following users will not show in the "users" column on the
	// security audit page. The format matches the way the client produces
	// data. UID (number)

	public $omit_users = array(
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
		"svctag (95)",
		"nobody (60001)",
		"noaccess (60002)",
		"nobody4 (65534)",
		"zfssnap (51)",
		"openldap (75)",
		"xvm (60)",
		"dladm (15)"
		);

	// The following user_attrs aren't displayed.  The first four elements
	// are Solaris 10, fifth is Solaris 9, sixth, seventh and tenth are
	// Nevada, ninth is Solaris 8

	public $omit_attrs = array(
		"adm::::profiles=Log Management",
		"lp::::profiles=Printer Management",
		"root::::auths=solaris.*,solaris.grant;profiles=All;lock_after_retries=no;min_label=admin_low;clearance=admin_high",
		"root::::auths=solaris.*,solaris.grant;profiles=All",
		"zfssnap::::type=role;auths=solaris.smf.manage.zfs-auto-snapshot;profiles=ZFS",
		"File System Management",
		"dladm::::auths=solaris.smf.manage.wpa,solaris.smf.modify",
		"root::::type=normal;auths=solaris.*,solaris.grant;profiles=All",
		"zfssnap::::type=role;auths=solaris.smf.manage.zfs-auto-snapshot;profiles=ZFS File System Management",
		"daemon::::auths=solaris.smf.manage.ilb,solaris.smf.modify.application");

	// The following are standard cron jobs on Solaris, and won't be
	// displayed on the secuirity audit page

	public $omit_crons = array(
		"lp:13 3 * * 0 cd /var/lp/logs; if [ -f requests ]; then if [ -f requests.1 ]; then /bin/mv requests.1 requests.2; fi; /usr/bin/cp requests requests.1; >requests; fi",
		"root:10 3 * * * /usr/sbin/logadm",
		"root:15 3 * * 0 [ -x /usr/lib/fs/nfs/nfsfind ] && /usr/lib/fs/nfs/nfsfind",
		"root:30 3 * * * [ -x /usr/lib/gss/gsscred_clean ] && /usr/lib/gss/gsscred_clean",
		"root:1 2 * * * [ -x /usr/sbin/rtc ] && /usr/sbin/rtc -c > /dev/null 2>&1",
		"root:15 3 * * 0 /usr/lib/fs/nfs/nfsfind",
		"root:10 3 * * 0,4 /etc/cron.d/logchecker",
		"root:10 3 * * 0   /usr/lib/newsyslog",
		"lp:15 3 * * 0 cd /var/lp/logs; if [ -f lpsched ]; then if [ -f lpsched.1 ]; then /bin/mv lpsched.1 lpsched.2; fi; /usr/bin/cp lpsched lpsched.1; >lpsched; fi");

	// We expect the following ports to be open. 22 is SSH, 111 is sunrpc,
	// 4045 is lockd, the 13xxx are NetBackup. These will be reported, but
	// won't be highlighted

	public $usual_ports = array(22, 111, 4045, 13722, 13724, 13782,
	13783);

	public function get_data($index)
	{
		return (isset($this->$index))
			? $this->$index
			: false;
	}
}

?>
