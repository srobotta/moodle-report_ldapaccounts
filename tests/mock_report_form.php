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
 * Mock the report form, basically because the original
 * class reads the submitted data from the POST fields which
 * are missing in the phpunit tests.
 *
 * @package     report_ldapaccounts
 * @copyright   2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mock_report_form extends report_form {
    /**
     * Post data.
     * @var \stdClass
     */
    protected \stdClass $post;

    /**
     * Set post data.
     * @param array $data
     */
    public function simulate_post_data(array $data) {
        $this->post = new \stdClass();
        foreach ($data as $key => $val) {
            $this->post->{$key} = $val;
        }
    }

    /**
     * Get the previously set post data.
     * @return \stdClass
     */
    public function get_submitted_data(): ?\stdClass {
        return $this->post;
    }

    /**
     * Add a selection with the options any, no, yes to the form.
     * @param string $name
     */
    public function add_any_no_yes_public(string $name): object {
        return $this->add_any_no_yes($name);
    }
}
