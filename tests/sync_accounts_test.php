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

namespace report_ldapaccounts;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the report_ldapaccounts plugin classes.
 *
 * @package    report_ldapaccounts
 * @copyright  2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sync_accounts_test extends \advanced_testcase {
    /**
     * Partial test of the query string.
     * @covers \report_ldapaccounts\sync_accounts::build_query_for_new_records
     */
    public function test_build_query_for_new_records() {
        $sync = new sync_accounts();
        $class = new \ReflectionClass($sync);
        $method = $class->getMethod('build_query_for_new_records');
        $this->assertEquals('(=*)', $method->invoke($sync));
        $sync->set_lastsync('2026-06-23');
        $this->assertEquals('(&(createTimestamp>=20260623000000Z)(=*))', $method->invoke($sync));
        $sync->set_username_field('uid');
        $this->assertEquals('(&(createTimestamp>=20260623000000Z)(uid=*))', $method->invoke($sync));
        $sync->set_queryprefix('(&(objectClass=person)(objectClass=top))');
        $this->assertEquals('(&(&(objectClass=person)(objectClass=top))(uid=*)(createTimestamp>=20260623000000Z))', $method->invoke($sync));
    }
}
