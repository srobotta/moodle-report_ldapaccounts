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
 * Displays Moodle user accounts and match them with a configured LDAP.
 * @package    report_ldapaccounts
 * @copyright  2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');

require_login();
if (!has_capability('report/ldapaccounts:view', context::instance_by_id(CONTEXT_SYSTEM, MUST_EXIST))) {
    throw new \moodle_exception('nopermissiontoaccesspage', 'error');
}

// Parameters that may come within the request.
$csv = optional_param('csv', '', PARAM_ALPHANUM);
$permalink = optional_param('permalink', '', PARAM_ALPHANUM);

// CSV Download was triggered.
if (!empty($csv)) {
    $csvfile = \report_ldapaccounts\config::get_plugin_file_dir() . DIRECTORY_SEPARATOR . $csv . '.csv';
    if (!file_exists($csvfile)) {
        header('HTTP/1.0 404 not found');
        exit();
    }
    // Switch off output buffering to write the content of a potentially large file.
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Description: File Transfer");
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename=ldapaccounts-"' . date('Y-n-m-H-i') . '.csv"');
    header('Content-Length: ' . filesize($csvfile));
    readfile($csvfile);
    // Exit is needed here otherwise another Content-Type header might be sent which will break the download.
    exit();
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/report/ldapaccounts/index.php'));
$output = $PAGE->get_renderer('report_ldapaccounts');
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'report_ldapaccounts'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_ldapaccounts'));

if (empty(\report_ldapaccounts\config::get_instance()->get_setting('server')) ||
    empty(\report_ldapaccounts\config::get_instance()->get_setting('user'))
) {
    $text = get_string('ldapnotconfigured', 'report_ldapaccounts');
    if (strpos($text, '[link]') !== false) {
        $link = (new moodle_url('/admin/settings.php?section=report_ldapaccounts_settings'))->__toString();
        $text = str_replace('[link]', '<a href="' . $link . '">', $text);
        if (strpos($text, '[/link]') !== false) {
            $text = str_replace('[/link]', '</a>', $text);
        } else {
            $text .= '</a>';
        }
    }
    echo $output->box($text, 'alert alert-warning');
    echo $OUTPUT->footer();
    exit;
}

$mform = new \report_ldapaccounts\report_form(new moodle_url('/report/ldapaccounts/'));
if (($mform->is_submitted() && $mform->is_validated())) {
    // Processing of the submitted form.
    echo $OUTPUT->box(get_string('reportldapaccountsdesc', 'report_ldapaccounts') . "<br />&#160;", 'generalbox');

    $ldap = \report_ldapaccounts\ldap::init_from_config();
    $ldapmailfield = \report_ldapaccounts\config::get_instance()->get_setting('ldapmailfield');
    $ldapquerypart = \report_ldapaccounts\config::get_instance()->get_setting('ldapquery');
    $filterldapstatus = $mform->get_filter_ldapstatus();
    $userquery = $mform->get_user_query();
    $userquery->add_selected_fields(['deleted', 'suspended', 'emailstop', 'email']);
    $colstoshow = $mform->get_submitted_select_fields();
    $table = new \report_ldapaccounts\user_table();
    $table->set_columns($colstoshow)
        ->enable_header(true)
        ->set_show_action_delete(true)
        ->set_show_action_suspend(true)
        ->set_show_action_profile(true)
        ->set_show_action_notification(true);

    $first = $filterldapstatus === -1; // Do not show this when LDAP status is set because this number refers to the moodle db only.
    while (true) {
        $result = $userquery->get_users();
        if ($first) {
            echo $OUTPUT->box(str_replace(
                '{0}',
                $userquery->get_record_count(),
                get_string('resultcount', 'report_ldapaccounts')
            ));
            $first = false;
        }
        if (empty($result)) {
            break;
        }
        $userinldap = [];
        foreach ($result as $user) {
            $userinldap[$user->email] = 0;
        }
        try {
            $ldapres = $ldap->search([$ldapmailfield => array_keys($userinldap)], $ldapmailfield, $ldapquerypart)
                ->get_parsed_result();
        } catch (\RuntimeException $e) {
            echo $OUTPUT->box($e->getMessage(), 'alert alert-danger');
            break;
        }
        foreach ($ldapres as $ldapuser) {
            $userinldap[$ldapuser[$ldapmailfield]] = 1;
        }
        foreach ($result as $user) {
            $user->ldap_status = $userinldap[$user->email];
            if ($filterldapstatus === -1 || $filterldapstatus === $user->ldap_status) {
                $table->add_table_row($user);
            }
        }
        $userquery->set_next_page();
    }

    $table->output_table();
    if ($mform->is_csv_download()) {
        $table->output_csv($mform->get_csv_delimiter());
        echo \html_writer::link(new moodle_url(
          '/report/ldapaccounts/',
            ['csv' => str_replace('.csv', '', $table->get_csvfile())]
        ), get_string('form_download_csv', 'report_ldapaccounts'));
        echo '&nbsp;|&nbsp;';
    }
    echo \html_writer::link(new moodle_url(
        '/report/ldapaccounts/',
        ['permalink' => $mform->get_permalink_param()]
    ), get_string('permalink', 'report_ldapaccounts'));
    $mform->display();
} else {
    // Form was not submitted yet.
    echo $OUTPUT->box(get_string('reportldapaccountsdesc', 'report_ldapaccounts') . "<br />&#160;", 'generalbox');
    if (!empty($permalink)) {
        $mform->set_data_from_permalink($permalink);
    }
    $mform->display();
}

echo $OUTPUT->footer();
