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
 * Links and settings
 *
 * @package    report_ldapaccounts
 * @copyright  2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Just a link to course report.
$ADMIN->add('reports', new admin_externalpage('reportldapaccounts', get_string('pluginname', 'report_ldapaccounts'),
        "$CFG->wwwroot/report/ldapaccounts/index.php", 'report/ldapaccounts:view'));

$settings = new admin_settingpage(
    'report_ldapaccounts_settings',
    new lang_string('pluginname', 'report_ldapaccounts')
);

if ($ADMIN->fulltree) {
    \report_ldapaccounts\config::get_instance()->add_config_to_settings_page($settings);
}


