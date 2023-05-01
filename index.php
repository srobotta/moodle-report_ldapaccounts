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
 * Displays outdated Moodle user accounts.
 * @package    report_ldapaccounts
 * @copyright  2016 BFH-ITS, Luca Bösch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Moodle Outdated accounts not in LDAP Report
 *
 * Main file for report
 *
 * @see doc/html/ for documentation
 *
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/lib/statslib.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/report/ldapaccounts/index.php'));
$output = $PAGE->get_renderer('report_ldapaccounts');

$mform = new \report_ldapaccounts\report_form(new moodle_url('/report/ldapaccounts/'));

if (($mform->is_submitted() && $mform->is_validated()) || (isset($_POST['download']))) {
    // Processing of the submitted form.
    $PAGE->set_pagelayout('admin');
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'report_ldapaccounts'));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'report_ldapaccounts'));
    echo $OUTPUT->box(get_string('reportldapaccountsdesc', 'report_ldapaccounts') . "<br />&#160;", 'generalbox');

    $ldap = \report_ldapaccounts\ldap::initFromConfig();
    $ldapmailfield = \report_ldapaccounts\config::getInstance()->getSetting('ldapmailfield');
    $ldapquerypart = \report_ldapaccounts\config::getInstance()->getSetting('ldapquery');
    $userquery = $mform->getUserQuery();
    $userquery->setSelectedFields(array_unique(array_merge($userquery->getSelectedFields(), ['deleted', 'suspended', 'emailstop'])));
    $colstoshow = $mform->getSubmittedSelectFields();
    array_splice($colstoshow, array_search('id', $colstoshow) + 1, 0, ['ldap_status']);
    $table = new \report_ldapaccounts\user_table();
    $table->setColumns($colstoshow)
        ->enableHeader(true)
        ->setShowActionDelete(true)
        ->setShowActionNotification(true)
        ->setShowActionProfile(true);

    while (true) {
        $result = $userquery->getUsers();
        if (empty($result)) {
            break;
        }
        $userinldap = [];
        foreach ($result as $user) {
            $userinldap[$user->email] = 0;
        }
        try {
            $ldapres = $ldap->search([$ldapmailfield => array_keys($userinldap)], $ldapmailfield, $ldapquerypart)
                ->getParsedResult();
        } catch (\RuntimeException $e) {
            echo $OUTPUT->box($e->getMessage(), 'generalbox');
            break;
        }
        foreach ($ldapres as $ldapuser) {
            $userinldap[$ldapuser[$ldapmailfield]] = 1;
        }
        foreach ($result as $user) {
            $user->ldap_status = $userinldap[$user->email];
            $table->addTableRow($user);
        }
        $userquery->setNextPage();
    }

    $table->outputTable();
    $mform->display();
} else {
    // Form was not submitted yet.
    $PAGE->set_pagelayout('admin');
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'report_ldapaccounts'));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'report_ldapaccounts'));
    echo $OUTPUT->box(get_string('reportldapaccountsdesc', 'report_ldapaccounts') . "<br />&#160;", 'generalbox');
    $mform->display();
}

echo $OUTPUT->footer();
