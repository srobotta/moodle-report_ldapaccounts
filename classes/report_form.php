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
 * Defines {@link \report_ldapaccounts\user_form} class.
 * Form to select user data and fields to display at the report page.
 *
 * @package     report_ldapaccounts
 * @copyright   2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_ldapaccounts;

require_once($CFG->libdir . '/formslib.php');

class report_form extends \moodleform {

    /**
     * @var array
     */
    private $authmethods;

    /**
     * Define the form.
     */
    protected function definition() {
        global $CFG, $DB;
        $this->_form->addElement(
            'html',
            '<h4>' . get_string('form_filter_userdata', 'report_ldapaccounts'). '</h4>'
        );
        if (count($this->getAuthMethods()) > 2){
            $this->_form->addElement(
                'select',
                'filter_auth',
                $this->_s('form_filter_auth'),
                $this->getAuthMethods()
            );
        }
        $this->addAnyNoYes('filter_deleted');
        $this->addAnyNoYes('filter_suspended');
        $this->addAnyNoYes('filter_emailstop');

        $this->_form->addElement('text', 'filter_firstname', $this->_s('form_filter_firstname'));
        $this->_form->setType('filter_firstname', PARAM_ALPHA);
        $this->_form->addElement('text', 'filter_lastname', $this->_s('form_filter_lastname'));
        $this->_form->setType('filter_lastname', PARAM_ALPHA);
        $this->_form->addElement('text', 'filter_email', $this->_s('form_filter_email'));
        $this->_form->setType('filter_email', PARAM_RAW_TRIMMED);

        $this->_form->addElement('html', '<div class=""><h4>' . $this->_s('form_show_userdata'). '</h4>');
        $this->_form->addElement('textarea', 'show_cols', $this->_s('form_show_cols'), ['rows' => 6])
            ->setValue("id\nemail\nusername\nfirstname\nlastname\nlastlogin");

        $this->_form->addElement('submit', 'submitbutton', $this->_s('callreport'));
    }

    /**
     * Shortcut to the language tags that are taken from this package.
     * @param string $name
     * @return string
     * @throws \coding_exception
     */
    private function _s(string $name): string
    {
        return get_string($name,'report_ldapaccounts');
    }

    /**
     * Add a selection with -1 => any, 0 => inactive, 1 => active element to the form. This is used for
     * filter columns like deleted, suspended.
     * @param string $name
     * @return void
     * @throws \coding_exception
     */
    protected function addAnyNoYes(string $name): void {
        $this->_form->addElement(
            'select',
            $name,
            get_string('form_' . $name, 'report_ldapaccounts'),
            [
                -1 => ' ',
                0 => '0',
                1 => '1',
            ]
        );
    }

    /**
     * @param $data
     * @param $files
     * @return array
     * @throws \coding_exception
     */
    public function validation($data, $files)
    {
        $errors = [];
        foreach (\array_keys($data) as $key) {
            if (strpos($key, 'filter_') === 0) {
                $field = substr($key, 7);
                if (\in_array($field, ['deleted', 'suspended', 'emailstop'])) {
                    if (!\in_array($data[$key], ['-1', '0', '1'])) {
                        $errors[$key] = get_string('form_error_input', 'report_ldapaccounts');
                    }
                }
            } elseif ($key === 'show_cols') {
                try {
                    (new user_query())->validateFields($this->getSubmittedSelectFields());
                } catch (\InvalidArgumentException $e) {
                    $errors[$key] = str_replace('{0}', explode(' ', $e->getMessage())[1],
                       get_string('form_error_column','report_ldapaccounts')
                    );
                }
            }
        }
        return $errors;
    }

    /**
     * Get list of different user.auth values.
     * @return array|string[]
     * @throws \dml_exception
     */
    protected function getAuthMethods(): array {
        global $DB;

        if ($this->authmethods === null) {
            $this->authmethods = [-1 => ' '];
            $i = 0;
            $res = $DB->get_records_sql('SELECT DISTINCT(auth) FROM {user}');
            foreach($res as $row) {
                $this->authmethods[$i++] = $row->auth;
            }
        }
        return $this->authmethods;
    }

    /**
     * Get a json string as filter for the user query.
     * @return string
     */
    public function getSubmittedFilters(): string {
        $filter = [];
        if (!$this->is_submitted()) {
            return json_encode($filter);
        }
        $data = $this->get_data();
        if (isset($data->filter_auth) && $data->filter_auth > -1) {
            $val = $this->getAuthMethods()[(int)$data->filter_auth] ?: '';
            if (!empty($val)) {
                $filter['auth'] = $val;
            }
        }
        if (isset($data->filter_deleted) && $data->filter_deleted > -1) {
            $filter['deleted'] = (int)$data->filter_deleted;
        }
        if (isset($data->filter_suspended) && $data->filter_suspended > -1) {
            $filter['suspended'] = (int)$data->filter_suspended;
        }
        if (isset($data->filter_emailstop) && $data->filter_emailstop > -1) {
            $filter['emailstop'] = (int)$data->filter_emailstop;
        }
        if (isset($data->filter_firstname) && !empty(trim($data->filter_firstname))) {
            $filter['firstname'] = trim($data->filter_firstname) . '*';
        }
        if (isset($data->filter_lastname) && !empty(trim($data->filter_lastname))) {
            $filter['lastname'] = trim($data->filter_lastname) . '*';
        }
        if (isset($data->filter_email) && !empty(trim($data->filter_email))) {
            $filter['email'] = trim($data->filter_email) . '*';
        }
        return json_encode($filter);
    }

    /**
     * @return array
     */
    public function getSubmittedSelectFields(): array {
        $cols = array_filter(
            array_map(
                function ($i) { return trim($i); },
                explode("\n", $this->get_submitted_data()->show_cols ?? '')
            ),
            function ($i) { return !empty($i); }
        );
        if (!\in_array('id', $cols)) {
            array_unshift($cols, 'id');
        }
        return $cols;
    }

    /**
     * @return user_query
     */
    public function getUserQuery(): user_query {
        $query = new user_query();
        $cols = $this->getSubmittedSelectFields();
        if (!empty($cols)) {
            $query->setSelectedFields($cols);
        }
        $query->setFilterFromJson($this->getSubmittedFilters());
        return $query;
    }

}