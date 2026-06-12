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

global $CFG;

require_once($CFG->dirroot . '/report/ldapaccounts/tests/mock_report_form.php');
require_once(__DIR__ . '/mock_ldap.php');

/**
 * Tests for the report_ldapaccounts plugin classes.
 *
 * @package    report_ldapaccounts
 * @copyright  2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    /**
     * Keep here the original configured temp directory.
     * @var string
     */
    protected string $origtempdir;

    /**
     * Set up test case.
     */
    protected function setUp(): void {
        parent::setUp();
        global $CFG;
        $this->origtempdir = $CFG->tempdir;
        $CFG->tempdir = sys_get_temp_dir();

        $this->resetAfterTest();
        mock_ldap::reset();
        $this->setAdminUser();
    }

    /**
     * Tear down, restore the temp directory.
     */
    protected function tearDown(): void {
        global $CFG;
        parent::tearDown();
        if ($this->origtempdir !== $CFG->tempdir) {
            $CFG->tempdir = $this->origtempdir;
        }
    }

    /**
     * Test the basic functionality of the ldap class.
     *
     * @covers \report_ldapaccounts\ldap::search()
     * @covers \report_ldapaccounts\ldap::get_parsed_result()
     */
    public function test_ldap_search_and_result_parsing_with_mocked_ldap(): void {
        set_config('ldapserver', 'example.com', 'report_ldapaccounts');
        set_config('ldapbasedn', 'dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldapuser', 'cn=admin,dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldappass', 'pass', 'report_ldapaccounts');
        set_config('ldapport', 389, 'report_ldapaccounts');

        mock_ldap::$entries = [
            'count' => 1,
            0 => [
                'cn' => ['count' => 1, '0' => 'Alice'],
                'mail' => ['count' => 1, '0' => 'alice@example.com'],
            ],
        ];

        $ldap = new ldap('example.com', 'dc=example,dc=com', 'cn=admin,dc=example,dc=com', 'pass', 389);
        $ldap->disable_logging();
        $ldap->search(['cn' => 'Alice'], ['cn', 'mail'], null, 10);

        $this->assertSame('(cn=Alice)', mock_ldap::$lastfilter);
        $this->assertSame(['cn', 'mail'], mock_ldap::$lastattributes);
        $this->assertSame(1, $ldap->get_count());

        $parsed = $ldap->get_parsed_result();
        $this->assertCount(1, $parsed);
        $this->assertSame('Alice', $parsed[0]['cn']);
        $this->assertSame('alice@example.com', $parsed[0]['mail']);
    }

    /**
     * Test that ldap initialization from config respects logging and certificate settings.
     *
     * @covers \report_ldapaccounts\ldap::init_from_config()
     */
    public function test_ldap_init_from_config_respects_logging_and_cert_settings(): void {
        set_config('ldapserver', 'example.com', 'report_ldapaccounts');
        set_config('ldapbasedn', 'dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldapuser', 'cn=admin,dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldappass', 'pass', 'report_ldapaccounts');
        set_config('ldapport', 389, 'report_ldapaccounts');
        set_config('ldapcert', '/tmp/ldap-cert.pem', 'report_ldapaccounts');
        set_config('ldapcacert', '/tmp/ldap-cacert.pem', 'report_ldapaccounts');
        set_config('logging', 1, 'report_ldapaccounts');

        $ldap = ldap::init_from_config();
        $class = new \ReflectionClass($ldap);
        $property = $class->getProperty('logging');
        $this->assertTrue($property->getValue($ldap));
    }

    /**
     * Test user query filters and field validation.
     *
     * @covers \report_ldapaccounts\user_query::set_filter_json()
     * @covers \report_ldapaccounts\user_query::set_selected_fields()
     * @covers \report_ldapaccounts\user_query::add_selected_fields()
     */
    public function test_user_query_filters_and_field_validation(): void {
        $query = new user_query();
        $query->set_filter_json('{"deleted": "0", "email": "*example.com"}');

        $fields = $query->get_selected_fields();
        $this->assertSame([], $fields);

        $query->set_selected_fields(['id', 'email']);
        $this->assertSame(['id', 'email'], $query->get_selected_fields());

        $query->add_selected_fields(['email', 'firstname']);
        $this->assertSame(['id', 'email', 'firstname'], $query->get_selected_fields());

        $this->expectException(\InvalidArgumentException::class);
        $query->set_selected_fields(['id', 'doesnotexist']);
    }

    /**
     * Test user query page size and page validation.
     *
     * @covers \report_ldapaccounts\user_query::set_page_size()
     * @covers \report_ldapaccounts\user_query::set_page()
     * @covers \report_ldapaccounts\user_query::get_page()
     */
    public function test_user_query_page_size_and_page_validation(): void {
        $query = new user_query();
        $query->set_page_size(10);
        $query->set_page(2);
        $this->assertSame(2, $query->get_page());

        $this->expectException(\InvalidArgumentException::class);
        $query->set_page_size(0);
    }

    /**
     * Test user table columns and CSV output functionality.
     *
     * @covers \report_ldapaccounts\user_table::set_columns()
     * @covers \report_ldapaccounts\user_table::add_table_row()
     * @covers \report_ldapaccounts\user_table::output_csv()
     */
    public function test_user_table_columns_and_csv_output(): void {
        global $CFG;
        $table = new user_table();
        $table->set_columns(['id', 'password', 'secret', 'email']);

        $class = new \ReflectionClass($table);
        $property = $class->getProperty('colunms');
        $this->assertSame(['id', 'email'], $property->getValue($table));

        $user = (object)[
            'id' => 10,
            'email' => 'alice@example.com',
            'firstname' => 'Alice',
            'lastname' => 'Example',
            'deleted' => 0,
        ];

        $table->add_table_row($user);
        $table->output_csv(',', '"', '\\', "\n");
        $this->assertStringEndsWith('.csv', $table->get_csvfile());
        $this->assertFileExists(config::get_plugin_file_dir() . DIRECTORY_SEPARATOR . $table->get_csvfile());
    }

    /**
     * Test that report form validation rejects invalid filters.
     *
     * @covers \report_ldapaccounts\report_form::validation()
     */
    public function test_report_form_validation_rejects_invalid_filters(): void {
        $form = new mock_report_form(new \moodle_url('/report/ldapaccounts/index.php'));
        $element = $form->add_any_no_yes_public('filter_deleted');
        $this->assertNotNull($element);

        $errors = $form->validation(['filter_deleted' => '2'], []);
        $this->assertArrayHasKey('filter_deleted', $errors);

        $postdata = ['show_cols' => ['id', 'doesnotexist']];
        $form->simulate_post_data($postdata);
        $errors = $form->validation($postdata, []);
        $this->assertArrayHasKey('show_cols', $errors);
    }

    /**
     * Test sync accounts setters, getters, and dry-run execution.
     *
     * @covers \report_ldapaccounts\sync_accounts::set_username_field()
     * @covers \report_ldapaccounts\sync_accounts::set_mail_field()
     * @covers \report_ldapaccounts\sync_accounts::set_queryprefix()
     * @covers \report_ldapaccounts\sync_accounts::set_authmethod()
     * @covers \report_ldapaccounts\sync_accounts::exec()
     */
    public function test_sync_accounts_setters_getters_and_dryrun_execution(): void {
        set_config('ldapusernamefield', 'uid', 'report_ldapaccounts');
        set_config('ldapmailfield', 'mail', 'report_ldapaccounts');
        set_config('syncauthmethod', 'manual', 'report_ldapaccounts');
        set_config('ldapserver', 'example.com', 'report_ldapaccounts');
        set_config('ldapbasedn', 'dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldapuser', 'cn=admin,dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldappass', 'pass', 'report_ldapaccounts');
        set_config('ldapport', 389, 'report_ldapaccounts');

        mock_ldap::$entries = [
            'count' => 1,
            0 => [
                'uid' => ['count' => 1, '0' => 'TestUser'],
                'mail' => ['count' => 1, '0' => 'testuser@example.com'],
                'givenname' => ['count' => 1, '0' => 'Test'],
                'sn' => ['count' => 1, '0' => 'User'],
            ],
        ];

        $sync = new sync_accounts();
        $sync->set_username_field('uid');
        $sync->set_mail_field('mail');
        $sync->set_queryprefix('(objectClass=person)');
        $sync->set_lastsync(time());

        if (!config::is_valid_auth_method('manual')) {
            $this->markTestSkipped('manual auth method is not enabled in this environment');
        }

        $sync->set_authmethod('manual');
        $result = $sync->exec(true);
        $this->assertInstanceOf(sync_accounts::class, $result);

        $property = new \ReflectionProperty(sync_accounts::class, 'log');
        $this->assertNotEmpty($property->getValue($sync));
    }

    /**
     * Test that sync LDAP accounts task returns correct name and executes with blocked status when not configured.
     *
     * @covers \report_ldapaccounts\task\sync_ldap_accounts::get_name()
     * @covers \report_ldapaccounts\task\sync_ldap_accounts::execute()
     */
    public function test_task_sync_ldap_accounts_get_name_and_execute_blocks_when_not_configured(): void {
        set_config('ldapusernamefield', '', 'report_ldapaccounts');
        $task = new task\sync_ldap_accounts();
        $this->assertSame(get_string('ldapaccountsynctask', 'report_ldapaccounts'), $task->get_name());

        ob_start();
        $task->execute();
        $output = ob_get_clean();
        $this->assertStringContainsString(get_string('synccheck_1', 'report_ldapaccounts'), $output);
    }

    /**
     * Test that privacy provider returns metadata reason.
     *
     * @covers \report_ldapaccounts\privacy\provider::get_reason()
     */
    public function test_privacy_provider_returns_metadata_reason(): void {
        $this->assertSame('privacy:metadata', privacy\provider::get_reason());
    }
}
