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
final class config_test extends \advanced_testcase {
    /**
     * Remember here the original value from $CFG->tempdir.
     * @var string
     */
    protected string $origtempdir;

    /**
     * Setup, Reset the config singleton and set
     * the temp directory to the system temp dir.
     */
    protected function setUp(): void {
        parent::setUp();
        global $CFG;
        $this->origtempdir = $CFG->tempdir;
        $CFG->tempdir = sys_get_temp_dir();

        $this->resetAfterTest();
        $this->reset_config_singleton();
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
     * Reset the config instance, so that the config values
     * are newly fetched from the DB or cache.
     */
    private function reset_config_singleton(): void {
        $class = new \ReflectionClass(config::class);
        $property = $class->getProperty('instance');
        $property->setValue(null, null);
    }

    /**
     * Test config last sync timestamps getter and setter.
     *
     * @covers \report_ldapaccounts\config::get_instance()
     * @covers \report_ldapaccounts\config::get_last_sync_time()
     * @covers \report_ldapaccounts\config::set_last_sync_time()
     */
    public function test_config_last_sync_timestamps(): void {
        set_config('lastsyncrun', 12345, 'report_ldapaccounts');
        $this->reset_config_singleton();
        $instance = config::get_instance();
        $this->assertSame(12345, $instance->get_last_sync_time());

        $instance->set_last_sync_time(54321);
        // The config instance returns an integer, the config values are strings.
        $this->assertSame(54321, $instance->get_last_sync_time());
        $this->assertEquals(54321, get_config('report_ldapaccounts', 'lastsyncrun'));
    }

    /**
     * Test that config plugin file directory is created.
     *
     * @covers \report_ldapaccounts\config::get_plugin_file_dir()
     */
    public function test_config_plugin_file_dir_creates_directory(): void {
        $dir = config::get_plugin_file_dir();
        $this->assertDirectoryExists($dir);
        $this->assertStringContainsString('report_ldapaccounts', $dir);
    }

    /**
     * Test config available auth methods and validation.
     *
     * @covers \report_ldapaccounts\config::get_available_auth_methods()
     * @covers \report_ldapaccounts\config::is_valid_auth_method()
     */
    public function test_config_available_auth_methods_and_validation(): void {
        $methods = config::get_available_auth_methods();
        $this->assertIsArray($methods);
        $this->assertNotEmpty($methods);
        $this->assertSame(array_key_exists('manual', $methods), config::is_valid_auth_method('manual'));
    }

    /**
     * Test that config settings are added to the admin settings page.
     *
     * @covers \report_ldapaccounts\config::add_config_to_settings_page()
     */
    public function test_config_add_config_to_settings_page(): void {
        $settings = new \admin_settingpage('report_ldapaccounts_test', 'LDAP Accounts Test');
        $instance = config::get_instance();
        $instance->add_config_to_settings_page($settings);
        $this->assertNotEmpty($settings->settings);
        $this->assertIsObject($settings->settings);
        $this->assertEquals('LDAP server', $settings->settings->report_ldapaccountsldapserver->visiblename);
        $this->assertEmpty($settings->settings->report_ldapaccountsldapserver->defaultsetting);
    }

    /**
     * Test config can_i_sync validation status.
     *
     * @covers \report_ldapaccounts\config::can_i_sync()
     */
    public function test_config_can_i_sync(): void {
        set_config('ldapserver', 'example.com', 'report_ldapaccounts');
        set_config('ldapbasedn', 'dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldapuser', 'cn=admin,dc=example,dc=com', 'report_ldapaccounts');
        set_config('ldappass', 'pass', 'report_ldapaccounts');
        set_config('ldapusernamefield', 'uid', 'report_ldapaccounts');

        mock_ldap::$entries = ['count' => 1];
        $this->reset_config_singleton();
        $instance = config::get_instance();
        $this->assertSame(0, $instance->can_i_sync());

        mock_ldap::$entries = ['count' => 0];
        $this->assertSame(4, $instance->can_i_sync());

        mock_ldap::$bindfailure = true;
        $this->assertSame(3, $instance->can_i_sync());

        set_config('ldapserver', '', 'report_ldapaccounts');
        $this->reset_config_singleton();
        $instance = config::get_instance();
        $this->assertSame(2, $instance->can_i_sync());

        set_config('ldapusernamefield', '', 'report_ldapaccounts');
        $this->reset_config_singleton();
        $instance = config::get_instance();
        $this->assertSame(1, $instance->can_i_sync());
    }
}
