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
 * Defines {@link \report_ldapaccounts\user_query} class.
 * Encapsulates selecting users from the database, and handles chunks of data to prevent fetching all data
 * at once.
 *
 * @package     report_ldapaccounts
 * @copyright   2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_ldapaccounts;

class user_query {

    /**
     * List of fields to select.
     * @var array
     */
    private $selectedfields = [];

    /**
     * Number of records to select in one chunk.
     * @var int
     */
    private $size = 1000;

    /**
     * Number of the chunk to fetch (start counting by 1 for the first chunk).
     * @var int
     */
    private $page = 1;

    /**
     * Filter to limit the search to some conditions.
     * @var array
     */
    private $filter = [];

    /**
     * The where clause that is build from the filter.
     * @var string
     */
    private $where;

    /**
     * The array with the arguments, taken from the filter and used in the SQL statement when sending the query.
     * @var array
     */
    private $args;

    /**
     * Number of all records that match the filter.
     * @var int
     */
    private $recordcnt;

    /**
     * @param array|null $fields
     * @param array|null $filter
     * @param int|null $size
     */
    public function __construct(array $fields = null, array $filter = null, int $size = null) {
        if (is_array($fields)) {
            $this->set_selected_fields($fields);
        }
        if (is_array($filter)) {
            $this->filter = $filter;
        }
        if ($size !== null && $size >= 0) {
            $this->set_page_size($size);
        }
    }

    /**
     * Filters for selecting users from the database. Provide and associative array with key being the column name
     * and value either a concrete value or an array like [term, operator], where term can be any string (wildcard
     * as %) and an operator e.g. > < >= <= like etc.
     *
     * @param array $filter
     * @return self
     */
    public function set_filter(array $filter): self {
        $this->validate_fields(\array_keys($filter));
        $this->filter = $filter;
        $this->where = null;
        $this->maxrecords = null;
        return $this;
    }

    /**
     * A simplified filter setting, used in the cli script. Instead of using an array for filter term and operator,
     * these two values are combined in one, prefixed by the operator. The wildcard is the *.
     * Examples are: {"deleted":0,"email":"*@example.org"} -> active users that have an email in example.org
     *
     * @param string $json
     * @return self
     */
    public function set_filter_json(string $json): self {
        $data = json_decode($json, true);
        if (!\is_array($data)) {
            throw new \InvalidArgumentException('Invalid json provided');
        }
        $filter = [];
        foreach ($data as $key => $val) {
            if (strpos($val, '>') === 0 || strpos($val, '<') === 0) {
                if (substr($val, 1, 1) === '"') {
                    $filter[$key] = [substr($val, 2), substr($val, 0, 2)];
                } else {
                    $filter[$key] = [substr($val, 1), substr($val, 0, 1)];
                }
            } else if (strpos($val, '*') !== false) {
                $filter[$key] = [str_replace('*', '%', str_replace('%', '%%', $val)), 'like'];
            } else {
                $filter[$key] = $val;
            }
        }
        $this->set_filter($filter);
        return $this;
    }

    /**
     * The fields that should be selected from the user table.
     * @param array $fields
     * @return self
     */
    public function set_selected_fields(array $fields): self {
        $this->validate_fields($fields);
        $this->selectedfields = $fields;
        return $this;
    }

    /**
     * @return array
     */
    public function get_selected_fields(): array {
        return $this->selectedfields;
    }

    /**
     * Validate field names that they exist in the user table.
     * @param array $fields
     * @return void
     */
    public function validate_fields(array $fields): void {
        global $DB;
        static $cols;

        if ($cols === null) {
            $cols = $DB->get_columns('user');
        }
        foreach ($fields as $field) {
            if (!isset($cols[$field])) {
                throw new \InvalidArgumentException('Field ' . $field . ' does not exist in user table');
            }
        }
    }

    /**
     * @param int $size
     * @return self
     */
    public function set_page_size(int $size): self {
        if ($size < 1) {
            throw new \InvalidArgumentException('size must be 1 or greater');
        }
        $this->size = $size;
        return $this;
    }

    /**
     * @param int $page
     * @return self
     */
    public function set_page(int $page): self {
        if ($page < 0) {
            throw new \InvalidArgumentException('page must be 0 or greater');
        }
        $this->page = $page;
        return $this;
    }

    /**
     * Get current page.
     * @return int
     */
    public function get_page(): int {
        return $this->page;
    }

    /**
     * Autoincrement page to get the next chunk when running getUsers() again.
     * @return self
     */
    public function set_next_page(): self {
        $this->page++;
        return $this;
    }

    /**
     * Get argument values for where clause.
     * @return array
     */
    protected function get_args(): array {
        if ($this->args === null) {
            $this->get_where();
        }
        return $this->args;
    }

    /**
     * Get parameterized where clause. Also build up the argument values array with the concrete values
     * that are supposed to be used in the query.
     * @return string
     */
    protected function get_where(): string {
        global $DB;
        if ($this->where === null) {
            $this->where = '1=1';
            $this->args = [];
            if (!empty($this->filter)) {
                foreach ($this->filter as $col => $val) {
                    $this->where .= ' AND ';
                    if (is_array($val)) {
                        if (strtolower($val[1]) === 'like') {
                            $this->where .= $DB->sql_like($col, ' :' . $col, false, false);
                        } else {
                            $this->where .= $val[1] . ' :' . $col . ' ';
                        }
                        $this->args[$col] = $val[0];
                    } else {
                        $this->where .= '= :' . $col . ' ';
                        $this->args[$col] = $val;
                    }
                }
            }
        }
        return $this->where;
    }

    /**
     * Get a chunk of users from the specified query.
     * @return array
     * @throws \dml_exception
     */
    public function get_users(): array {
        global $DB;
        if ($this->recordcnt === null) {
            $this->recordcnt = $DB->count_records_select('user', $this->get_where(), $this->get_args());
        }
        $cols = empty($this->selectedfields) ? '*' : implode(', ', $this->selectedfields);
        $sql = 'SELECT ' . $cols . ' FROM {user} WHERE ' . $this->get_where()
            . ' ORDER BY id';
        if ($this->size > 0) {
            $users = $DB->get_records_sql($sql, $this->get_args(), ($this->page - 1) * $this->size, $this->size);
        } else {
            $users = $DB->get_records_sql($sql, $this->get_args());
        }
        // Set is empty property.
        $this->isempty = empty($users);
        return $users;
    }

    /**
     * Get number of all records.
     * @return int
     */
    public function get_record_count(): int {
        return $this->recordcnt ?: 0;
    }
}
