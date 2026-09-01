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

/**
 * Tests for the report_ldapaccounts user_table class.
 *
 * @package    report_ldapaccounts
 * @copyright  2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_ldapaccounts\user_table
 */
final class user_table_test extends \advanced_testcase {
    /**
     * Keep here the original configured temp directory.
     * @var string
     */
    protected string $origtempdir;

    /**
     * Set up test case, point the temp directory at the system temp dir.
     */
    protected function setUp(): void {
        parent::setUp();
        global $CFG;
        $this->origtempdir = $CFG->tempdir;
        $CFG->tempdir = sys_get_temp_dir();
        $this->resetAfterTest();
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
     * Read a private/protected property from a user_table instance.
     * @param user_table $table
     * @param string $name
     * @return mixed
     */
    private function get_property(user_table $table, string $name) {
        $ref = new \ReflectionClass(user_table::class);
        $prop = $ref->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($table);
    }

    /**
     * Build a minimal user object usable by add_table_row().
     * @param array $overrides
     * @return \stdClass
     */
    private function make_user(array $overrides = []): \stdClass {
        return (object)array_merge([
            'id' => 42,
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
            'deleted' => 0,
            'suspended' => 0,
            'emailstop' => 0,
        ], $overrides);
    }

    /**
     * The action setters return the instance (fluent) and toggle the matching getter.
     */
    public function test_action_setters_and_getters_are_fluent(): void {
        $table = new user_table();

        $this->assertFalse($table->is_show_action_profile());
        $this->assertFalse($table->is_show_action_delete());
        $this->assertFalse($table->is_show_action_suspend());
        $this->assertFalse($table->is_show_action_notification());

        $this->assertSame($table, $table->set_show_action_profile(true));
        $this->assertSame($table, $table->set_show_action_delete(true));
        $this->assertSame($table, $table->set_show_action_suspend(true));
        $this->assertSame($table, $table->set_show_action_notification(true));

        $this->assertTrue($table->is_show_action_profile());
        $this->assertTrue($table->is_show_action_delete());
        $this->assertTrue($table->is_show_action_suspend());
        $this->assertTrue($table->is_show_action_notification());

        $table->set_show_action_profile(false);
        $this->assertFalse($table->is_show_action_profile());
    }

    /**
     * set_columns() stores the given columns but always strips password and secret.
     */
    public function test_set_columns_strips_sensitive_fields(): void {
        $table = new user_table();
        $this->assertSame($table, $table->set_columns(['id', 'username', 'password', 'email', 'secret']));

        $columns = $this->get_property($table, 'colunms');
        $this->assertSame(['id', 'username', 'email'], array_values($columns));
        $this->assertNotContains('password', $columns);
        $this->assertNotContains('secret', $columns);
    }

    /**
     * add_table_row() throws when the columns have not been configured yet.
     */
    public function test_add_table_row_requires_columns(): void {
        $table = new user_table();
        $this->expectException(\RuntimeException::class);
        $table->add_table_row($this->make_user());
    }

    /**
     * build_header_row() throws when the columns have not been configured yet.
     */
    public function test_build_header_row_requires_columns(): void {
        $table = new user_table();
        $ref = new \ReflectionMethod(user_table::class, 'build_header_row');
        $ref->setAccessible(true);
        $this->expectException(\RuntimeException::class);
        $ref->invoke($table, false);
    }

    /**
     * A regular row contains the plain formatted values in column order.
     */
    public function test_add_table_row_builds_plain_cells(): void {
        $table = new user_table();
        $table->set_columns(['id', 'username', 'email']);
        $this->assertSame($table, $table->add_table_row($this->make_user()));

        $data = $this->get_property($table, 'table')->data;
        $this->assertCount(1, $data);
        $this->assertSame(['42', 'jdoe', 'jdoe@example.com'], $data[0]);
    }

    /**
     * Deleted users are rendered with a strikethrough and their action cells are empty.
     */
    public function test_add_table_row_marks_deleted_user(): void {
        $table = new user_table();
        $table->set_columns(['username'])
            ->set_show_action_profile(true)
            ->set_show_action_delete(true);
        $table->add_table_row($this->make_user(['deleted' => 1]));

        $row = $this->get_property($table, 'table')->data[0];
        $this->assertSame('<s>jdoe</s>', $row[0]);
        // The two action cells must be empty for a deleted user.
        $this->assertSame('', $row[1]);
        $this->assertSame('', $row[2]);
    }

    /**
     * Date columns are rendered through userdate_htmltime as <time> elements.
     */
    public function test_add_table_row_formats_date_columns(): void {
        $table = new user_table();
        $table->set_columns(['lastaccess']);
        $table->add_table_row($this->make_user(['lastaccess' => 1700000000]));

        $row = $this->get_property($table, 'table')->data[0];
        $this->assertStringContainsString('<time', $row[0]);

        // An empty timestamp yields an empty cell.
        $table2 = new user_table();
        $table2->set_columns(['lastaccess']);
        $table2->add_table_row($this->make_user(['lastaccess' => 0]));
        $this->assertSame('', $this->get_property($table2, 'table')->data[0][0]);
    }

    /**
     * The action columns produce anchor tags pointing at the expected admin urls.
     */
    public function test_add_table_row_builds_action_links(): void {
        global $CFG;
        $table = new user_table();
        $table->set_columns(['username'])
            ->set_show_action_profile(true)
            ->set_show_action_delete(true)
            ->set_show_action_suspend(true)
            ->set_show_action_notification(true);
        $table->add_table_row($this->make_user());

        $row = $this->get_property($table, 'table')->data[0];
        // Cell 0 is the username, cells 1-4 are the four actions in order.
        $this->assertStringContainsString($CFG->wwwroot . '/user/profile.php?id=42', $row[1]);
        $this->assertStringContainsString($CFG->wwwroot . '/admin/user.php?delete=42', $row[2]);
        $this->assertStringContainsString('suspend=42', $row[3]);
        $this->assertStringContainsString('/message/notificationpreferences.php?userid=42', $row[4]);
    }

    /**
     * A suspended user gets the "unsuspend" action instead of "suspend".
     */
    public function test_add_table_row_suspend_toggle(): void {
        $table = new user_table();
        $table->set_columns(['username'])->set_show_action_suspend(true);
        $table->add_table_row($this->make_user(['suspended' => 1]));

        $row = $this->get_property($table, 'table')->data[0];
        $this->assertStringContainsString('unsuspend=42', $row[0 + 1]);
        $this->assertStringContainsString('fa-eye-slash', $row[1]);
    }

    /**
     * get_csvfile() is null until a csv file has been written.
     */
    public function test_get_csvfile_null_by_default(): void {
        $table = new user_table();
        $this->assertNull($table->get_csvfile());
    }

    /**
     * enable_header() is fluent and toggles the internal flag.
     */
    public function test_enable_header_is_fluent(): void {
        $table = new user_table();
        $this->assertSame($table, $table->enable_header(true));
        $this->assertTrue($this->get_property($table, 'showheader'));
    }

    /**
     * Header row is prepended with translated column titles when the header is enabled.
     */
    public function test_build_header_row_prepends_titles(): void {
        $table = new user_table();
        $table->set_columns(['id', 'username'])->enable_header(true);
        $table->add_table_row($this->make_user());

        $ref = new \ReflectionMethod(user_table::class, 'build_header_row');
        $ref->setAccessible(true);
        $ref->invoke($table, false);

        $data = $this->get_property($table, 'table')->data;
        // Header prepended, so we now have 2 rows.
        $this->assertCount(2, $data);
        $header = $data[0];
        $this->assertInstanceOf(\html_table_row::class, $header);
        $this->assertSame('ID', $header->cells[0]->text);
        $this->assertTrue($header->cells[0]->header);
    }

    /**
     * A second call to build_header_row replaces (not duplicates) the header row.
     */
    public function test_build_header_row_replaces_existing_header(): void {
        $table = new user_table();
        $table->set_columns(['id'])->enable_header(true);
        $table->add_table_row($this->make_user());

        $ref = new \ReflectionMethod(user_table::class, 'build_header_row');
        $ref->setAccessible(true);
        $ref->invoke($table, false);
        $ref->invoke($table, false);

        $data = $this->get_property($table, 'table')->data;
        // One header + one data row, the header must not be duplicated.
        $this->assertCount(2, $data);
        $this->assertInstanceOf(\html_table_row::class, $data[0]);
        $this->assertIsArray($data[1]);
    }

    /**
     * output_table() renders the html table to standard output.
     */
    public function test_output_table_outputs_html(): void {
        $table = new user_table();
        $table->set_columns(['username']);
        $table->add_table_row($this->make_user());

        ob_start();
        $table->output_table();
        $output = ob_get_clean();

        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('jdoe', $output);
    }

    /**
     * output_csv() writes a csv file, records its name and extracts links/labels.
     */
    public function test_output_csv_writes_file(): void {
        $table = new user_table();
        $table->set_columns(['username'])
            ->set_show_action_profile(true)
            ->enable_header(true);
        $table->add_table_row($this->make_user());

        $table->output_csv();

        $csvname = $table->get_csvfile();
        $this->assertNotNull($csvname);
        $this->assertStringEndsWith('.csv', $csvname);

        $path = config::get_plugin_file_dir() . DIRECTORY_SEPARATOR . $csvname;
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        // The username value appears in the data row.
        $this->assertStringContainsString('jdoe', $content);
        // The profile action anchor is reduced to its href url in the csv.
        $this->assertStringContainsString('/user/profile.php?id=42', $content);
        // The href must not contain any html markup.
        $this->assertStringNotContainsString('<a ', $content);

        unlink($path);
    }

    /**
     * output_csv() prefixes values that could be interpreted as spreadsheet formulas.
     */
    public function test_output_csv_prevents_formula_injection(): void {
        $table = new user_table();
        $table->set_columns(['username']);
        $table->add_table_row($this->make_user(['username' => '=HYPERLINK(evil)']));

        $table->output_csv();
        $path = config::get_plugin_file_dir() . DIRECTORY_SEPARATOR . $table->get_csvfile();
        $content = file_get_contents($path);

        // The leading "=" must be escaped with a single quote.
        $this->assertStringContainsString("'=HYPERLINK(evil)", $content);

        unlink($path);
    }
}
