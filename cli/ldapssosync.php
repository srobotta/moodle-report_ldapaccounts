<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This script allows you to view and change the emailstop flag of any user.
 *
 * @package    report_ldapaccounts
 * @copyright  2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_ldapaccounts\config;
use report_ldapaccounts\sync_accounts;

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Define the input options.
$longparams = [
    'authmethod' => '',
    'date' => '',
    'dryrun' => false,
    'help' => false,
    'ldapmail' => '',
    'ldapquery' => '',
    'silent' => false,
    'username' => '',
];

$shortparams = [
    'a' => 'authmethod',
    'd' => 'date',
    'h' => 'help',
    'm' => 'ldapmail',
    'n' => 'dryrun',
    'q' => 'ldapquery',
    's' => 'silent',
    'u' => 'username',
];

// Define exit codes.
$exitsuccess = 0;
$exitunknownoption = 1;
$exitauthmethoddisabled = 2;
$exitsynccheckfailed = 3;
$exiterrorldap = 5;

// Now get cli options that are set by the caller.
[$options, $unrecognized] = cli_get_params($longparams, $shortparams);

$verbose = empty($options['silent']);
$dryrun = !empty($options['dryrun']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    if ($verbose) {
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized), $exitunknownoption);
    }
    exit($exitunknownoption);
}

if ($options['help']) {
    $help = "
Fetch users from LDAP. Use the field value from report_ldapaccounts | ldapusernamefield
to check whether that user exists on Moodle with that username. If the user
does not exist, he will be added using authentication method via SSO.

Options:
-a, --authmethod Authentication method set when new users are creaded. If not set
                 the setting report_ldapaccounts | syncauthmethod is used.
-d, --date       Date (must be parseable date string) where to start from in LDAP
                 to search for newer accounts.
-h, --help       Print out this help.
-m, --ldapmail   The ldap mail field where to look up emails. If not set
                 the setting \"report_ldapaccounts | ldapmailfield\" is used.
-n, --dryrun     Do not create users.
-q, --ldapquery  Query prefix that is prepend to all LDAP queries. If not
                 set the setting \"report_ldapaccounts | ldapquery\" is used.
-s, --silent     No output to stdout.
-u, --username   The ldap field that contains the SSO username, which should be used
                 in Moodle as the username. If not set the setting
                 \"report_ldapaccounts | ldapusernamefield\" is used.

Example:
\$sudo -u www-data /usr/bin/php public/report/ldapaccounts/cli/ldapssosync.php -d=2026-06-13
";

    echo $help;
    exit($exitsuccess);
}

// Set user to site admin for permission checks and to avoid issues with user create.
$admin = get_admin();
\core\session\manager::set_user($admin);

$authmethod = $options['authmethod'] ?: config::get_instance()->get_setting('syncauthmethod');
$usernamefield = $options['username'] ?: config::get_instance()->get_setting('ldapusernamefield');
$mailfield = $options['ldapmail'] ?: config::get_instance()->get_setting('ldapmailfield');
$ldapquery = $options['ldapquery'] ?: config::get_instance()->get_setting('ldapquery');
$date = $options['date'] ?: config::get_instance()->get_last_sync_time();

if (!$dryrun && !config::get_instance()->is_valid_auth_method($authmethod)) {
    if ($verbose) {
        cli_error(get_string('authmethoddisabled', 'report_ldapaccounts', $authmethod), $exitunknownoption);
    }
    exit($exitauthmethoddisabled);
}

$synccheck = @config::get_instance()->can_i_sync();
if ($synccheck !== 0) {
    if ($verbose) {
        cli_error(get_string('synccheck_' . $synccheck, 'report_ldapaccounts'));
    }
    exit($exitsynccheckfailed);
}

try {
    if ($dryrun) {
        echo 'DRY RUN' . PHP_EOL;
    }
    $sync = new sync_accounts();
    $sync->set_queryprefix($ldapquery)
        ->set_username_field($usernamefield)
        ->set_mail_field($mailfield)
        ->set_authmethod($authmethod)
        ->set_lastsync($date)
        ->exec($dryrun);

    if ($verbose) {
        foreach ($sync->get_log() as $line) {
            echo $line . PHP_EOL;
        }
    }
} catch (\Exception $e) {
    if ($verbose) {
        echo "Error: {$e->getCode()} - {$e->getMessage()}\n";
    }
    exit($exiterrorldap);
}

exit($exitsuccess);
