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
 * Defines {@link \report_ldapaccounts\event\log_debug} class.
 * This class provides an event to log the LDAP communication to the system log.
 * This may be enabled in the settings for debug reasons and should be off in production.
 *
 * @package     report_ldapaccounts
 * @copyright   2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_ldapaccounts\event;

use core\event\base;

class log_debug extends base {

    /**
     * @return void
     * @throws \dml_exception
     */
    protected function init() {
        $this->context = \context_system::instance();
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('log_debug', 'report_ldapaccounts');
    }

    /**
     * Returns non-localised event description with details for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return sprintf('Filter: %s - Fields: %s - Result: %s',
            $this->data['other']['filter'],
            $this->data['other']['justthese'],
            $this->data['other']['result']
        );
    }

}