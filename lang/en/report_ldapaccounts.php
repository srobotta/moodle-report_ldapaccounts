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
 * Lang strings.
 *
 * @package    report_ldapaccounts
 * @copyright  2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['callreport'] = 'Call report';
$string['col_auth'] = 'Authentication';
$string['col_calendartype'] = 'Calendar';
$string['col_currentlogin'] = 'Current login';
$string['col_descriptionformat'] = 'Description format';
$string['col_lang'] = 'Language';
$string['col_mailformat'] = 'Mail format';
$string['col_maildigest'] = 'Mail digest';
$string['col_maildisplay'] = 'Mail display';
$string['col_mnethostid'] = 'MNet host ID';
$string['col_policyagreed'] = 'Policy accepted';
$string['col_timemodified'] = 'Time modified';
$string['col_trustbitmask'] = 'Trust bit mask';
$string['col_moodlenetprofile'] = 'MoodleNet profile';
$string['enable_emailstop'] = 'Enable notifications';
$string['form_col_ldap_status'] = 'LDAP status';
$string['form_csv_delimiter'] = 'CSV Delimiter';
$string['form_download_csv'] = 'Download report as CSV';
$string['form_error_column'] = 'The field {0} does not exist in the user table.';
$string['form_error_input'] = 'This field is invalid.';
$string['form_filter_auth'] = 'Authentication';
$string['form_filter_deleted'] = 'Deleted';
$string['form_filter_email'] = 'E-Mail';
$string['form_filter_emailstop'] = 'Emailstop';
$string['form_filter_firstname'] = 'Firstname';
$string['form_filter_lastname'] = 'Lastname';
$string['form_filter_ldapstatus'] = 'LDAP Status';
$string['form_filter_suspended'] = 'Suspended';
$string['form_filter_userdata'] = 'Filter user data';
$string['form_show_cols'] = 'Columns';
$string['form_show_userdata'] = 'Display user data';
$string['ldapaccounts:view'] = 'View accounts in LDAP';
$string['ldapbasedn'] = 'LDAP base DN';
$string['ldapbasedn_desc'] = 'The base DN is the root node where to query the LDAP server.';
$string['ldapcacert'] = 'CA cert file';
$string['ldapcacert_desc'] = 'Certificate file of the CA cert to validate the server. In case of an connection error you can provide the ca cert file by:

1. Connect to the LDAP server via openssl s_client -connect example.com:636.
2. Copy everything between and including -----BEGIN CERTIFICATE----- and -----END CERTIFICATE-----.
3. Save the copied content into a file and store it on the server.
4. Add the file location to this setting.

';
$string['ldapcert'] = 'Cert file';
$string['ldapcert_desc'] = 'Certificate file of the Moodle server itself, in case needed.';
$string['ldapmailfield'] = 'E-Mail field in LDAP';
$string['ldapmailfield_desc'] = 'The name of the field where the mail address of a user is stored in LDAP.';
$string['ldapnotconfigured'] = 'LDAP Connection has not been defined yet. Please go to the [link]settings page[/link].';
$string['ldappass'] = 'LDAP password';
$string['ldappass_desc'] = 'Password of the LDAP user to be used for the connection.';
$string['ldapport'] = 'LDAP server port';
$string['ldapport_desc'] = 'Port where the LDAP server is accessed to.';
$string['ldapquery'] = 'LDAP query';
$string['ldapquery_desc'] = 'Fixed query part to select users in ldap for the report page (e.g. `(&(objectClass=person)(objectClass=top))`). This is expanded by the email from the user record in Moodle.';
$string['ldapserver'] = 'LDAP server';
$string['ldapserver_desc'] = 'Server domain or IP where to connect to.';
$string['ldapuser'] = 'LDAP username';
$string['ldapuser_desc'] = 'Name of the user that is used for the connection.';
$string['logging'] = 'Enable logging';
$string['logging_desc'] = 'Write all communication with the LDAP server into a debug log file.';
$string['permalink'] = 'Permalink for this report';
$string['pluginname'] = 'Moodle Accounts in LDAP';
$string['privacy:metadata'] = 'Moodle Accounts in LDAP does not store any personal data in the default setup. However, personal data are written into the log file and the csv export file when these options are enabled or selected.';
$string['reportldapaccountsdesc'] = 'Select accounts in Moodle and check whether these exist in LDAP.';
$string['resultcount'] = '{0} Entries found.';
