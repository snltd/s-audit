<?php

//============================================================================
//
// s-audit security definition file for Solaris 5.6.
//
// Generated Tuesday January  6 15:29:33 GMT 2004 by s-audit_secdefs.sh
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
		"smtp (0)",
		"uucp (5)",
		"nuucp (9)",
		"listen (37)",
		"nobody (60001)",
		"noaccess (60002)",
		"nobody4 (65534)",
		"rob (264)"),

	"crontabs" => array(
		"root:10 3 * * 0,4 /etc/cron.d/logchecker",
		"root:10 3 * * 0   /usr/lib/newsyslog",
		"root:15 3 * * 0 /usr/lib/fs/nfs/nfsfind",
		"root:1 2 * * * [ -x /usr/sbin/rtc ] && /usr/sbin/rtc -c > /dev/null 2>&1",
		"lp:13 3 * * 0 cd /var/lp/logs; if [ -f requests ]; then if [ -f requests.1 ]; then /bin/mv requests.1 requests.2; fi; /usr/bin/cp requests requests.1; >requests; fi",
		"lp:15 3 * * 0 cd /var/lp/logs; if [ -f lpsched ]; then if [ -f lpsched.1 ]; then /bin/mv lpsched.1 lpsched.2; fi; /usr/bin/cp lpsched lpsched.1; >lpsched; fi",
		"uucp:48 8,12,16 * * * /usr/lib/uucp/uudemon.admin",
		"uucp:20 3 * * * /usr/lib/uucp/uudemon.cleanup",
		"uucp:0 * * * * /usr/lib/uucp/uudemon.poll",
		"uucp:11,41 * * * * /usr/lib/uucp/uudemon.hour")
);

?>
