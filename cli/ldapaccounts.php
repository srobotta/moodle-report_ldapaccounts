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
 * @package    ldapaccounts
 * @copyright  2023 Stephan Robotta (stephan.robotta@bfh.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Define the input options.
$longparams = [
    'action' => '',
    'delimiter' => '',
    'filter' => '',
    'help' => false,
    'ldapmail' => '',
    'ldapquery' => '',
    'silent' => false,
];

$shortparams = [
    'a' => 'action',
    'd' => 'delimiter',
    'f' => 'filter',
    'h' => 'help',
    'm' => 'ldapmail',
    'q' => 'ldapquery',
    's' => 'silent',
];

// Define exit codes.
$exitsuccess = 0;
$exitunknownoption = 1;
$exitinvalidfilter = 2;
$exitinvaliddelimiter = 3;
$exitinvalidaction = 4;
$exiterrorldap = 5;

// Now get cli options that are set by the caller.
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

$verbose = empty($options['silent']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    if ($verbose) {
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized), $exitunknownoption);
    }
    exit($exitunknownoption);
}

if ($options['help']) {
    $help = "
Get users and check whether these exist in the ldap directory. A user is
identified by his email.

There are no security checks here because anybody who is able to execute
this file may execute any PHP too.

Options:
-a, --action     Action on the user, possible actions are suspend, delete,
                 emailstop. The user entry will be modified in that way,
                 when there is no entry found in the LDAP with this users
                 email address. In case an action is used, the users are
                 written to stdout only if the action has been applied to
                 the user and the data has been modified.
-d, --delimiter  CSV delimiter, can be one of ; , | ~ : tab
                 If not set, the semicolon is used.
-f, --filter     Filter which users to select. Think of an array such as:
                 only active users: ['deleted' => 0]
                 only users with email ending in example.org:
                 ['email' => '*example.org']
                 only users with id > than 100: ['id' => '>100']
                 You may also combine these:
                 ['deleted' => 0, 'email' => '*example.org']
                 Take this string and convert it into a json. The correct
                 filter argument for the above should
                 look like this: {\"deleted\":0,\"email\":\"*example.org\"}
                 Fields that can be used are all columns inside the user
                 table.
-h, --help       Print out this help
-m, --ldapmail   The ldap mail field where to look up emails. If not set
                 the setting `report_ldapaccounts | ldapmailfield` is used.
-q, --ldapquery  Query prefix that is prepend to all LDAP queries. If not
                 set the setting `report_ldapaccounts | ldapquery` is used.
-s, --silent     No output to stdout.

Example:
\$sudo -u www-data /usr/bin/php report/ldapaccounts/cli/ldapaccounts.php -f='{\"deleted\":0,\"email\":\"*example.org\"}'
";

    echo $help;
    exit($exitsuccess);
}

$csvdelimiter = $options['delimiter'] ?: ';';
if (strtolower($csvdelimiter) === 'tab') {
    $csvdelimiter = "\t";
}
if (!in_array($csvdelimiter, [';', ',', '|', '~', ':', "\t"])) {
    if ($verbose) {
        cli_error('Invalid csv delimiter: ' . $csvdelimiter, $exitinvaliddelimiter);
    }
    exit($exitinvaliddelimiter);
}

$queryuserfields = ['id', 'email', 'firstname', 'lastname'];

$action = $options['action'] ?: null;
if (!empty($action)) {
    if (!in_array($action, ['delete', 'suspend', 'emailstop'])) {
        if ($verbose) {
            cli_error('Invalid action: ' . $action, $exitinvalidaction);
        }
        exit($exitinvalidaction);
    }
    if ($action === 'delete') {
        $action .= 'd';
    } else if ($action === 'suspend') {
        $action .= 'ed';
    }
    $queryuserfields[] = $action;
}


$ldapmail = $options['ldapmail'] ?? \report_ldapaccounts\config::get_instance()->get_setting('ldapmailfield');
$ldapquery = $options['ldapprefix'] ?? \report_ldapaccounts\config::get_instance()->get_setting('ldapquery');


// Prepare query for users in Moodle db.
$query = new \report_ldapaccounts\user_query($queryuserfields);
$query->set_page_size(100);
if (isset($options['filter'])) {
    try {
        $query->set_filter_json($options['filter']);
    } catch (\RuntimeException $e) {
        if ($verbose) {
            echo $e->getMessage() . PHP_EOL;
        }
        exit($exitinvalidfilter);
    }
}

// Initialize ldap query.
$ldap = \report_ldapaccounts\ldap::init_from_config();
// Write to stdout.
if ($verbose) {
    $fp = fopen('php://stdout', 'w');
}
$csvheader = false;
while (1) {
    $idstoupdate = [];
    $result = $query->get_users();
    if (empty($result)) {
        break;
    }
    $userinldap = [];
    foreach ($result as $user) {
        $userinldap[$user->email] = 0;
    }
    try {
        $ldapres = $ldap->search([$ldapmail => array_keys($userinldap)], $ldapmail, $ldapquery)
            ->get_parsed_result();
    } catch (\RuntimeException $e) {
        if ($verbose) {
            cli_error($e->getMessage(), $exiterrorldap);
        }
        exit($exiterrorldap);
    }
    foreach ($ldapres as $ldapuser) {
        $userinldap[$ldapuser[$ldapmail]] = 1;
    }
    if (!$csvheader && $verbose) {
        $fields = ['id', 'ldap', 'email', 'firstname', 'lastname'];
        if (!empty($action)) {
            $fields[] = 'action';
            array_unshift($fields, 'date');
        }
        fputcsv($fp, $fields, $csvdelimiter);
        $csvheader = true;
    }
    foreach ($result as $user) {
        $fields = [$user->id, $userinldap[$user->email], $user->email, $user->firstname, $user->lastname];
        if (!empty($action)) {
            if ($userinldap[$user->email] === 1) {
                continue; // User is in LDAP, no action.
            }
            if ((int)$user->{$action} !== 0) {
                continue; // User is not in LDAP but already suspended, deleted or has an email stop flag set.
            }
            // Remember the ID of the user, to run the update statement once only for the current chunk.
            $idstoupdate[] = $user->id;
            $fields[] = $action;
            array_unshift($fields, date('Y-m-d H:i:s'));
        }
        if ($verbose) {
            fputcsv($fp, $fields, $csvdelimiter);
        }
    }
    $query->set_next_page();
    if (!empty($idstoupdate) && !empty($action)) {
        $sql = 'UPDATE {user} '
            . ' SET '. $action . ' = 1, timemodified = ' . time()
            . ' WHERE id IN ( ' . implode(',', $idstoupdate). ')';
        $DB->execute($sql);
    }
}
if ($verbose) {
    fclose($fp);
}
$ldap->close();


exit($exitsuccess);

