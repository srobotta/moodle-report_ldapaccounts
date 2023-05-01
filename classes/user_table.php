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
 * Defines {@link \report_ldapaccounts\user_table} class.
 * Table to display user list at the report page.
 *
 * @package     report_ldapaccounts
 * @copyright   2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_ldapaccounts;

class user_table
{
    /**
     * @var bool
     */
    private $showHeader = false;

    /**
     * @var bool
     */
    private $showActionProfile = false;

    /**
     * @var bool
     */
    private $showActionDelete = false;

    /**
     * @var bool
     */
    private $showActionDisable = false;

    /**
     * @var array
     */
    private $colunms;

    /**
     * @var \html_table
     */
    private $table;

    /**
     * Constructor
     */
    public function __construct() {
        $this->table = new \html_table();
    }

    /**
     * @param bool $showActionProfile
     */
    public function setShowActionProfile(bool $showActionProfile): user_table {
        $this->showActionProfile = $showActionProfile;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowActionProfile(): bool
    {
        return $this->showActionProfile;
    }

    /**
     * @param bool $val
     * @return user_table
     */
    public function setShowActionDelete(bool $val): user_table {
        $this->showActionDelete = $val;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowActionDelete(): bool
    {
        return $this->showActionDelete;
    }

    /**
     * @param bool $val
     * @return user_table
     */
    public function setShowActionNotification(bool $val): user_table {
        $this->showActionNotification = $val;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowActionNotification(): bool
    {
        return $this->showActionNotification;
    }

    /**
     * @param array $cols
     * @return user_table
     */
    public function setColumns(array $cols): user_table {
        $keys = array_flip($cols);
        // Never ever display contents of password and secret.
        if (isset($keys['password'])) {
            unset($keys['password']);
        }
        if (isset($keys['secret'])) {
            unset($keys['secret']);
        }
        $this->colunms = \array_keys($keys);
        return $this;
    }

    /**
     * @param \stdClass $user
     * @return user_table
     * @throws \coding_exception
     */
    public function addTableRow(\stdClass $user): user_table {
        global $CFG;

        if ($this->colunms === null) {
            throw new \RuntimeException('columns must be set first');
        }

        $row = [];
        foreach ($this->colunms as $col) {
            if (\in_array($col, ['currentlogin', 'lastlogin', 'timecreted', 'timemodified', 'firstaccess', 'lastaccess'])) {
                $row[] = !empty($user->{$col}) ? userdate_htmltime($user->{$col}) : '';
            } else {
                $row[] = $user->{$col} ?? '';
            }
        }
        if ($this->showActionProfile) {
            $row[] = '<a href="' . $CFG->httpswwwroot . '/user/profile.php?id=' . $user->id . '" target="blank">'
                . get_string('userdetails', 'core') . '</a>';
        }
        if ($this->showActionDelete) {
            $row[] = (int)$user->deleted === 1 ? '' :
                '<a href="' . $CFG->httpswwwroot . '/admin/user.php?sort=name&dir=ASC&perpage=30&page=0&delete='
                    . $user->id . '&sesskey=' . sesskey() . '" target="blank">'
                    . get_string('delete', 'core') . '</a>';
        }
        if ($this->showActionNotification) {
            $row[] = (int)$user->emailstop === 1 ? '' :
                '<a href="' . $CFG->httpswwwroot . '/message/notificationpreferences.php?userid='
                    . $user->id . '" target="blank">' . get_string('emailstop', 'core') . '</a>';
        }
        $this->table->data[] = $row;
        return $this;
    }

    /**
     * @param bool $val
     * @return $this
     * @throws \coding_exception
     */
    public function enableHeader(bool $val): user_table {
        $this->showHeader = $val;
        return $this;
    }

    /**
     * @return user_table
     * @throws \coding_exception
     */
    protected function buildHeaderRow(): user_table {
        if ($this->colunms === null) {
            throw new \RuntimeException('columns must be set first');
        }
        $headerrow = new \html_table_row();
        $totalheadercells = [];
        $totalheadertitles = [];
        foreach ($this->colunms as $col) {
            if ($col === 'id') {
                $totalheadertitles[] = 'ID';
            } elseif ($col === 'ldap_status') {
                $totalheadertitles[] = get_string('form_col_ldap_status', 'report_ldapaccounts');
            } else {
                $totalheadertitles[] = get_string($col, 'core');
            }
        }
        if ($this->showActionProfile) {
            $totalheadertitles[] = get_string('userdetails', 'core');
        }
        if ($this->showActionDelete) {
            $totalheadertitles[] = get_string('delete', 'core');
        }
        if ($this->showActionNotification) {
            $totalheadertitles[] = get_string('emailstop', 'core');
        }
        foreach ($totalheadertitles as $totalheadertitle) {
            $cell = new \html_table_cell($totalheadertitle);
            $cell->header = true;
            $totalheadercells[] = $cell;
        }
        $headerrow->cells = $totalheadercells;
        \array_unshift($this->table->data, $headerrow);
        return $this;
    }

    /**
     * Write the table using the html_writer.
     * @return void
     */
    public function outputTable(): void {
        if ($this->showHeader) {
            $this->buildHeaderRow();
        }
        echo \html_writer::table($this->table);
    }
}