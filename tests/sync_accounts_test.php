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

require_once(__DIR__ . '/mock_ldap.php');

/**
 * Tests for the report_ldapaccounts plugin classes.
 *
 * @package    report_ldapaccounts
 * @copyright  2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sync_accounts_test extends \advanced_testcase {
    /**
     * Remember the original value of $CFG->tempdir.
     * @var string
     */
    protected string $origtempdir;

    /**
     * Set up test case: point the temp dir to the system temp dir, reset the
     * config singleton and the mocked ldap, and act as the admin user (so that
     * the moodle/user:create capability is granted during exec()).
     */
    protected function setUp(): void {
        parent::setUp();
        global $CFG;
        $this->origtempdir = $CFG->tempdir;
        $CFG->tempdir = sys_get_temp_dir();

        $this->resetAfterTest();
        mock_ldap::reset();
        $this->reset_config_singleton();
        $this->setAdminUser();
    }

    /**
     * Tear down: restore the temp directory.
     */
    protected function tearDown(): void {
        global $CFG;
        parent::tearDown();
        if ($this->origtempdir !== $CFG->tempdir) {
            $CFG->tempdir = $this->origtempdir;
        }
    }

    /**
     * Reset the config instance, so that the config values are newly fetched
     * from the DB the next time the singleton is requested.
     */
    private function reset_config_singleton(): void {
        $property = (new \ReflectionClass(config::class))->getProperty('instance');
        $property->setValue(null, null);
    }

    /**
     * Set the plugin configuration that is required to run a sync and reset the
     * config singleton so that the new values are picked up.
     */
    private function set_base_config(): void {
        set_config('ldapserver', 'example.com', 'report_ldapaccounts');
        set_config('ldapbasedn', 'dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldapuser', 'cn=admin,dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldappass', 'pass', 'report_ldapaccounts');
        set_config('ldapport', 389, 'report_ldapaccounts');
        set_config('ldapusernamefield', 'uid', 'report_ldapaccounts');
        set_config('ldapmailfield', 'mail', 'report_ldapaccounts');
        set_config('syncauthmethod', 'manual', 'report_ldapaccounts');
        $this->reset_config_singleton();
    }

    /**
     * Build a mocked ldap_get_entries() result from a list of associative
     * arrays where each array maps an ldap field name to a single string value.
     *
     * @param array $users
     * @return array
     */
    private function make_entries(array $users): array {
        $entries = ['count' => count($users)];
        foreach (array_values($users) as $i => $user) {
            $entry = [];
            foreach ($user as $field => $value) {
                $entry[$field] = ['count' => 1, 0 => $value];
            }
            $entry['count'] = count($user);
            $entries[$i] = $entry;
        }
        return $entries;
    }

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

    /**
     * Test the username field getter/setter. When nothing is set the value
     * falls back to the ldapusernamefield setting.
     *
     * @covers \report_ldapaccounts\sync_accounts::set_username_field
     * @covers \report_ldapaccounts\sync_accounts::get_username_field
     */
    public function test_username_field_getter_setter(): void {
        $sync = new sync_accounts();
        $this->assertInstanceOf(sync_accounts::class, $sync->set_username_field('customuid'));
        $this->assertSame('customuid', $sync->get_username_field());

        // No explicit value -> fall back to the configured setting.
        set_config('ldapusernamefield', 'configuid', 'report_ldapaccounts');
        $this->reset_config_singleton();
        $this->assertSame('configuid', (new sync_accounts())->get_username_field());
    }

    /**
     * Test the mail field getter/setter. When nothing is set the value falls
     * back to the ldapmailfield setting (which defaults to "mail").
     *
     * @covers \report_ldapaccounts\sync_accounts::set_mail_field
     * @covers \report_ldapaccounts\sync_accounts::get_mail_field
     */
    public function test_mail_field_getter_setter(): void {
        $sync = new sync_accounts();
        $this->assertInstanceOf(sync_accounts::class, $sync->set_mail_field('custommail'));
        $this->assertSame('custommail', $sync->get_mail_field());

        // Default setting value.
        $this->reset_config_singleton();
        $this->assertSame('mail', (new sync_accounts())->get_mail_field());

        // Configured value.
        set_config('ldapmailfield', 'emailaddress', 'report_ldapaccounts');
        $this->reset_config_singleton();
        $this->assertSame('emailaddress', (new sync_accounts())->get_mail_field());
    }

    /**
     * Test the query prefix getter/setter. When nothing is set the value falls
     * back to the ldapquery setting.
     *
     * @covers \report_ldapaccounts\sync_accounts::set_queryprefix
     * @covers \report_ldapaccounts\sync_accounts::get_queryprefix
     */
    public function test_queryprefix_getter_setter(): void {
        $sync = new sync_accounts();
        $this->assertInstanceOf(sync_accounts::class, $sync->set_queryprefix('(objectClass=person)'));
        $this->assertSame('(objectClass=person)', $sync->get_queryprefix());

        // No explicit value, no setting -> empty string.
        $this->reset_config_singleton();
        $this->assertSame('', (new sync_accounts())->get_queryprefix());

        // Configured value.
        set_config('ldapquery', '(objectClass=top)', 'report_ldapaccounts');
        $this->reset_config_singleton();
        $this->assertSame('(objectClass=top)', (new sync_accounts())->get_queryprefix());
    }

    /**
     * Test the last sync getter/setter accepting both integer timestamps and
     * date strings, and the fallback to the lastsyncrun setting.
     *
     * @covers \report_ldapaccounts\sync_accounts::set_lastsync
     * @covers \report_ldapaccounts\sync_accounts::get_lastsync
     */
    public function test_lastsync_getter_setter(): void {
        $sync = new sync_accounts();
        $this->assertInstanceOf(sync_accounts::class, $sync->set_lastsync(1000));
        $this->assertSame(1000, $sync->get_lastsync());

        // A date string is converted via strtotime().
        $sync->set_lastsync('2026-06-23');
        $this->assertSame(strtotime('2026-06-23'), $sync->get_lastsync());

        // Zero is a valid timestamp.
        $sync->set_lastsync(0);
        $this->assertSame(0, $sync->get_lastsync());

        // No explicit value -> fall back to the configured last sync time.
        set_config('lastsyncrun', 555, 'report_ldapaccounts');
        $this->reset_config_singleton();
        $this->assertSame(555, (new sync_accounts())->get_lastsync());
    }

    /**
     * Test that an invalid date string for the last sync time throws.
     *
     * @covers \report_ldapaccounts\sync_accounts::set_lastsync
     */
    public function test_lastsync_invalid_string_throws(): void {
        $sync = new sync_accounts();
        $this->expectException(\moodle_exception::class);
        $sync->set_lastsync('this-is-not-a-date');
    }

    /**
     * Test that a negative integer for the last sync time throws.
     *
     * @covers \report_ldapaccounts\sync_accounts::set_lastsync
     */
    public function test_lastsync_negative_int_throws(): void {
        $sync = new sync_accounts();
        $this->expectException(\moodle_exception::class);
        $sync->set_lastsync(-5);
    }

    /**
     * Test the auth method getter/setter including the fallback to the
     * syncauthmethod setting.
     *
     * @covers \report_ldapaccounts\sync_accounts::set_authmethod
     * @covers \report_ldapaccounts\sync_accounts::get_authmethod
     */
    public function test_authmethod_getter_setter(): void {
        $sync = new sync_accounts();
        $this->assertInstanceOf(sync_accounts::class, $sync->set_authmethod('manual'));
        $this->assertSame('manual', $sync->get_authmethod());

        // No explicit value, no setting -> empty string.
        $this->reset_config_singleton();
        $this->assertSame('', (new sync_accounts())->get_authmethod());

        // Configured value.
        set_config('syncauthmethod', 'manual', 'report_ldapaccounts');
        $this->reset_config_singleton();
        $this->assertSame('manual', (new sync_accounts())->get_authmethod());
    }

    /**
     * Test that setting an auth method that is not available throws.
     *
     * @covers \report_ldapaccounts\sync_accounts::set_authmethod
     */
    public function test_authmethod_invalid_throws(): void {
        $sync = new sync_accounts();
        $this->expectException(\moodle_exception::class);
        $sync->set_authmethod('thisauthdoesnotexist');
    }

    /**
     * Test that exec() creates a new Moodle account from an LDAP record.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     * @covers \report_ldapaccounts\sync_accounts::create_new_user
     */
    public function test_exec_creates_new_user(): void {
        $this->set_base_config();
        mock_ldap::$entries = $this->make_entries([
            [
                'uid' => 'newuser',
                'mail' => 'newuser@example.com',
                'givenname' => 'New',
                'sn' => 'User',
                'preferredlanguage' => 'en',
            ],
        ]);

        $sync = new sync_accounts();
        $sync->set_lastsync(time());
        $result = $sync->exec(false);
        $this->assertInstanceOf(sync_accounts::class, $result);

        $user = \core_user::get_user_by_username('newuser');
        $this->assertIsObject($user);
        $this->assertSame('newuser@example.com', $user->email);
        $this->assertSame('New', $user->firstname);
        $this->assertSame('User', $user->lastname);
        $this->assertSame('manual', $user->auth);
        $this->assertEquals(1, $user->confirmed);

        $log = $sync->get_log();
        $this->assertNotEmpty($log);
        $this->assertStringContainsString('Create user:', $log[0]);
    }

    /**
     * Test that a dry run logs the would-be creation but does not write to the
     * database.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     */
    public function test_exec_dryrun_does_not_create_user(): void {
        $this->set_base_config();
        mock_ldap::$entries = $this->make_entries([
            ['uid' => 'dryuser', 'mail' => 'dryuser@example.com', 'givenname' => 'Dry', 'sn' => 'Run'],
        ]);

        $sync = new sync_accounts();
        $sync->set_lastsync(time());
        $sync->exec(true);

        $this->assertFalse(\core_user::get_user_by_username('dryuser'));
        $log = $sync->get_log();
        $this->assertNotEmpty($log);
        $this->assertStringContainsString('Create user:', $log[0]);
    }

    /**
     * Test that an account that already exists in Moodle is skipped.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     */
    public function test_exec_skips_existing_user(): void {
        $this->set_base_config();
        $this->getDataGenerator()->create_user(['username' => 'existinguser']);

        mock_ldap::$entries = $this->make_entries([
            ['uid' => 'existinguser', 'mail' => 'existinguser@example.com', 'givenname' => 'Ex', 'sn' => 'Isting'],
        ]);

        $sync = new sync_accounts();
        $sync->set_lastsync(time());
        $sync->exec(false);

        // Nothing was created, so nothing was logged.
        $this->assertSame([], $sync->get_log());
    }

    /**
     * Test that an LDAP record with an empty username is skipped.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     */
    public function test_exec_skips_empty_username(): void {
        $this->set_base_config();
        mock_ldap::$entries = $this->make_entries([
            ['uid' => '   ', 'mail' => 'empty@example.com', 'givenname' => 'No', 'sn' => 'Name'],
        ]);

        $sync = new sync_accounts();
        $sync->set_lastsync(time());
        $sync->exec(false);

        $this->assertSame([], $sync->get_log());
    }

    /**
     * Test that the username from LDAP is lower cased before the account is
     * created.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     */
    public function test_exec_lowercases_username(): void {
        global $DB;
        $this->set_base_config();
        mock_ldap::$entries = $this->make_entries([
            ['uid' => 'MixedCase', 'mail' => 'mixed@example.com', 'givenname' => 'Mixed', 'sn' => 'Case'],
        ]);

        $sync = new sync_accounts();
        $sync->set_lastsync(time());
        $sync->exec(false);

        $this->assertIsObject(\core_user::get_user_by_username('mixedcase'));
        // Moodle usernames are alphanumeric and lowercase only. This works well with Postgres
        // but not with MariaDB. Therefore, we have a switch here.
        if (str_contains(get_class($DB), 'pgsql')) {
            $this->assertFalse(\core_user::get_user_by_username('MixedCase'));
        } else {
            $user = \core_user::get_user_by_username('MixedCase');
            $this->assertIsObject($user);
            $this->assertSame('mixedcase', $user->username);
        }
    }

    /**
     * Test that exec() throws when the username field is not configured.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     */
    public function test_exec_throws_when_username_field_empty(): void {
        set_config('ldapusernamefield', '', 'report_ldapaccounts');
        set_config('syncauthmethod', 'manual', 'report_ldapaccounts');
        $this->reset_config_singleton();

        $sync = new sync_accounts();
        $this->expectException(\moodle_exception::class);
        $sync->exec(false);
    }

    /**
     * Test that exec() throws when the auth method is not configured.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     */
    public function test_exec_throws_when_authmethod_empty(): void {
        set_config('ldapusernamefield', 'uid', 'report_ldapaccounts');
        set_config('syncauthmethod', '', 'report_ldapaccounts');
        $this->reset_config_singleton();

        $sync = new sync_accounts();
        $this->expectException(\moodle_exception::class);
        $sync->exec(false);
    }

    /**
     * Test that a real run stores the last sync time, while a dry run does not.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     */
    public function test_exec_updates_lastsync_on_real_run(): void {
        $this->set_base_config();
        // No entries, we only care about the last sync bookkeeping.
        mock_ldap::$entries = ['count' => 0];

        // A dry run must not touch the stored last sync time.
        $sync = new sync_accounts();
        $sync->exec(true);
        $this->assertSame(0, (int)get_config('report_ldapaccounts', 'lastsyncrun'));

        // A real run stores the current time.
        $before = time();
        $sync = new sync_accounts();
        $sync->exec(false);
        $this->assertGreaterThanOrEqual($before, $sync->get_lastsync());
        $this->assertGreaterThanOrEqual($before, (int)get_config('report_ldapaccounts', 'lastsyncrun'));
    }

    /**
     * Test that exec() passes the expected filter and result fields to LDAP.
     *
     * @covers \report_ldapaccounts\sync_accounts::exec
     */
    public function test_exec_builds_expected_ldap_query(): void {
        $this->set_base_config();
        mock_ldap::$entries = ['count' => 0];

        $sync = new sync_accounts();
        $sync->set_queryprefix('(objectClass=person)');
        $sync->set_lastsync('2026-06-23');
        $sync->exec(false);

        $this->assertSame('(&(objectClass=person)(uid=*)(createTimestamp>=20260623000000Z))', mock_ldap::$lastfilter);
        $this->assertSame(['uid', 'mail', 'givenname', 'sn', 'preferredlanguage'], mock_ldap::$lastattributes);
    }

    /**
     * Test that the log is empty for a freshly created instance.
     *
     * @covers \report_ldapaccounts\sync_accounts::get_log
     */
    public function test_get_log_is_empty_initially(): void {
        $this->assertSame([], (new sync_accounts())->get_log());
    }
}
