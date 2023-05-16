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

class user_table {

    /**
     * @var bool
     */
    private $showheader = false;

    /**
     * @var bool
     */
    private $showactionprofile = false;

    /**
     * @var bool
     */
    private $showactiondelete = false;

    /**
     * @var bool
     */
    private $showactionsuspend = false;

    /**
     * @var bool
     */
    private $showactionnotification = false;

    /**
     * @var array
     */
    private $colunms;

    /**
     * @var \html_table
     */
    private $table;

    /**
     * @var string
     */
    private $csvfile;

    /**
     * Constructor
     */
    public function __construct() {
        $this->table = new \html_table();
    }

    /**
     * @param bool $val
     */
    public function set_show_action_profile(bool $val): user_table {
        $this->showactionprofile = $val;
        return $this;
    }

    /**
     * @return bool
     */
    public function is_show_action_profile(): bool {
        return $this->showactionprofile;
    }

    /**
     * @param bool $val
     * @return user_table
     */
    public function set_show_action_delete(bool $val): user_table {
        $this->showactiondelete = $val;
        return $this;
    }

    /**
     * @return bool
     */
    public function is_show_action_delete(): bool {
        return $this->showactiondelete;
    }

    /**
     * @param bool $val
     * @return user_table
     */
    public function set_show_action_suspend(bool $val): user_table {
        $this->showactionsuspend = $val;
        return $this;
    }

    /**
     * @return bool
     */
    public function is_show_action_suspend(): bool {
        return $this->showactionsuspend;
    }

    /**
     * @param bool $val
     * @return user_table
     */
    public function set_show_action_notification(bool $val): user_table {
        $this->showactionnotification = $val;
        return $this;
    }

    /**
     * @return bool
     */
    public function is_show_action_notification(): bool {
        return $this->showactionnotification;
    }

    /**
     * @param array $cols
     * @return user_table
     */
    public function set_columns(array $cols): user_table {
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
     * Returns the name of the csv file in case one was written.
     * @return string|null
     */
    public function get_csvfile(): ?string
    {
        return $this->csvfile;
    }

    /**
     * @param \stdClass $user
     * @return user_table
     * @throws \coding_exception
     */
    public function add_table_row(\stdClass $user): user_table {
        global $CFG;

        if ($this->colunms === null) {
            throw new \RuntimeException('columns must be set first');
        }

        $row = [];
        foreach ($this->colunms as $col) {
            if (\in_array($col, ['currentlogin', 'lastlogin', 'timecreted', 'timemodified', 'firstaccess', 'lastaccess'])) {
                $cell = !empty($user->{$col}) ? userdate_htmltime($user->{$col}) : '';
            } else {
                $cell = $user->{$col} ?? '';
            }
            $row[] = (int)$user->deleted === 1 ? '<s>' . $cell . '</s>' : $cell;
        }
        if ($this->showactionprofile) {
            $row[] = (int)$user->deleted === 1 ? '' :
                '<a href="' . $CFG->httpswwwroot
                    . '/user/profile.php?id=' . $user->id . '" target="blank"><i title="'
                    . htmlspecialchars(get_string('userdetails', 'core'))
                    . '" class="icon fa fa-cog fa-fw" rolw="img" aria-label="'
                    . htmlspecialchars(get_string('userdetails', 'core'))
                    . '"></i></a>';
        }
        if ($this->showactiondelete) {
            $row[] = (int)$user->deleted === 1 ? '' :
                '<a href="' . $CFG->httpswwwroot . '/admin/user.php?sort=name&dir=ASC&perpage=30&page=0&delete='
                    . $user->id . '&sesskey=' . sesskey() . '" target="blank"><i title="'
                    . htmlspecialchars(get_string('delete', 'core'))
                    . '" class="icon fa fa-trash fa-fw" role="img" aria-label="'
                    . htmlspecialchars(get_string('delete', 'core'))
                    . '"></i></a>';
        }
        if ($this->showactionsuspend) {
            if ((int)$user->suspended === 1) {
                $action = 'unsuspend';
                $icon = 'fa-eye-slash';
            } else {
                $action = 'suspend';
                $icon = 'fa-eye';
            }
            $row[] = (int)$user->deleted === 1 ? '' :
                '<a href="' . $CFG->httpswwwroot . '/admin/user.php?sort=name&dir=ASC&perpage=30&page=0&'
                    . $action . '=' . $user->id . '&sesskey=' . sesskey() . '" target="blank"><i title="'
                    . htmlspecialchars(get_string($action . 'user', 'admin'))
                    . '" class="icon fa ' . $icon . ' fa-fw" role="img" aria-label="'
                    . htmlspecialchars(get_string($action . 'user', 'admin'))
                    . '"></i></a>';
        }
        if ($this->showactionnotification) {
            $label = (int)$user->emailstop === 0
                ? htmlspecialchars(get_string('emailstop', 'core'))
                : htmlspecialchars(get_string('enable_emailstop', 'report_ldapaccounts'));
            $row[] = (int)$user->deleted === 1 ? '' :
                '<a href="' . $CFG->httpswwwroot . '/message/notificationpreferences.php?userid='
                    . $user->id . '" target="blank"><i title="' . $label
                    . '" class="icon fa fa-envelope fa-fw '. ($user->emailstop ? 'btn-secondary' : '')
                    .'" role="img" aria-label="' . $label . '"></i></a>';
        }
        $this->table->data[] = $row;
        return $this;
    }

    /**
     * @param bool $val
     * @return $this
     * @throws \coding_exception
     */
    public function enable_header(bool $val): user_table {
        $this->showheader = $val;
        return $this;
    }

    /**
     * @param bool $iscsv
     * @return user_table
     * @throws \coding_exception
     */
    protected function build_header_row(bool $iscsv = false): user_table {
        if ($this->colunms === null) {
            throw new \RuntimeException('columns must be set first');
        }
        $headerrow = new \html_table_row();
        $totalheadercells = [];
        $totalheadertitles = [];
        foreach ($this->colunms as $col) {
            if ($col === 'id') {
                $totalheadertitles[] = 'ID';
            } else if ($col === 'ldap_status') {
                $totalheadertitles[] = get_string('form_col_ldap_status', 'report_ldapaccounts');
            } else {
                $totalheadertitles[] = get_string($col, 'core');
            }
        }
        if (!$iscsv) {
            $colspan = 0;
            if ($this->showactionprofile) {
                $colspan++;
            }
            if ($this->showactiondelete) {
                $colspan++;
            }
            if ($this->showactionsuspend) {
                $colspan++;
            }
            if ($this->showactionnotification) {
                $colspan++;
            }
            if ($colspan > 0) {
                $totalheadertitles[] = get_string('edit', 'core');
            }
        } else {
            if ($this->showactionprofile) {
                $totalheadertitles[] = get_string('userdetails', 'core');
            }
            if ($this->showactiondelete) {
                $totalheadertitles[] = get_string('delete', 'core');
            }
            if ($this->showactionsuspend) {
                $totalheadertitles[] = get_string('suspenduser', 'admin');
            }
            if ($this->showactionnotification) {
                $totalheadertitles[] = get_string('emailstop', 'core');
            }
        }
        foreach (\array_keys($totalheadertitles) as $t) {
            $cell = new \html_table_cell($totalheadertitles[$t]);
            $cell->header = true;
            if ($t + 1 === count($totalheadertitles) && !$iscsv && $colspan > 1) {
                $cell->colspan = $colspan;
            }
            $totalheadercells[] = $cell;
        }
        $headerrow->cells = $totalheadercells;
        // If the first table row is already a header row, then replace the header with the new header row.
        if (!empty($this->table->data) && $this->table->data[0] instanceof \html_table_row) {
            $this->table->data[0] = $headerrow;
        } else { // Otherwise just prepend the header row on top of the other rows.
            \array_unshift($this->table->data, $headerrow);
        }
        return $this;
    }

    /**
     * Return the directory where the csv files are stored.
     * @return string
     */
    public static function get_dir(): string {
        global $CFG;

        // Directory where to store the csv files.
        $dir = $CFG->dataroot . DIRECTORY_SEPARATOR . 'report_ldapaccounts';
        if (!is_dir($dir) && !mkdir($dir, $CFG->directorypermissions)) {
            throw new \RuntimeException('could not create directory for saving csv file');
        }
        return $dir;
    }

    /**
     * Write the table using the html_writer.
     * @return void
     */
    public function output_table(): void {
        if ($this->showheader) {
            $this->build_header_row();
        }
        echo \html_writer::table($this->table);
    }

    /**
     * Write the table data into a csv file.
     * @param string $delimiter
     * @param string $quote
     * @param string $escape
     * @param string $eol
     * @return void
     */
    public function output_csv(string $delimiter = ',', string $quote = '"', string $escape = '\\', string $eol = PHP_EOL) {
        $this->csvfile = md5(microtime()) . '.csv';
        $fp = fopen(self::get_dir() . DIRECTORY_SEPARATOR . $this->csvfile, 'w');
        if (!$fp) {
            throw new \RuntimeException('could not open csv file for writing');
        }
        if ($this->showheader) {
            $this->build_header_row(true);
        }
        foreach ($this->table->data as $row) {
            fputcsv($fp, $this->prepare_csv_row($row), $delimiter, $quote, $escape, $eol);
        }
        fclose($fp);
    }

    /**
     * If a row is inside the \html_table_row object, extract the cells, otherwise the row is just the array from
     * the argument. All html must be converted to something usable in the CSV file.
     * - In anchor tags, the href attribute is used.
     * - In time tags, the content of the element is used.
     * The so transformed array is returned.
     *
     * @param array|\html_table_row $row
     * @return array
     */
    private function prepare_csv_row($row): array {
        if ($row instanceof \html_table_row) {
            $raw = [];
            foreach ($row->cells as $cell) {
                $raw[] = $cell->text;
            }
        } else {
            $raw = $row;
        }
        foreach (\array_keys($raw) as $i) {
            if (strpos($raw[$i], '<time') === 0) {
                $raw[$i] = strip_tags($raw[$i]);
            } else if (strpos($raw[$i], '<a ') === 0) {
                $start = strpos($raw[$i], 'href="');
                $end = $start !== false ? strpos($raw[$i], '"', $start + 7) : false;
                if ($end !== false) {
                    $raw[$i] = substr($raw[$i], $start + 6, $end - $start - 6);
                }
            }
        }
        return $raw;
    }
}
