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

namespace report_ldapaccounts\task;

use report_ldapaccounts\sync_accounts;

/**
 * Scheduler task for importing new LDAP users into Moodle.
 *
 * @package    report_ldapaccounts
 * @copyright  2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_ldap_accounts extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('ldapaccountsynctask', 'report_ldapaccounts');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute(): void {
        $sync = new sync_accounts();
        $sync->exec();
    }
}
