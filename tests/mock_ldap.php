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

// phpcs:disable moodle.NamingConventions.ValidVariableName.MemberNameUnderscore

/**
 * Mock the ldap class because we do not have a real ldap server where to
 * send the requests to.
 *
 * @package     report_ldapaccounts
 * @copyright   2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mock_ldap extends ldap {
    /**
     * Simulate a bind failure.
     * @var bool
     */
    public static bool $bindfailure = false;

    /**
     * Simulate a search failure.
     * @var bool
     */
    public static bool $searchfailure = false;

    /**
     * Array of elements in the ldap.
     * @var array
     */
    public static array $entries = [];

    /**
     * The filter for the ldap search query that is set via search().
     * @var string
     */
    public static string $lastfilter = '';

    /**
     * Attributes to return.
     * @var array
     */
    public static array $lastattributes = [];

    /**
     * The simulated search result.
     * @var string
     */
    public static ?string $lastsearchresult = null;

    /**
     * Switch to enable diagnostics.
     * @var bool
     */
    public static bool $returndiagnosticonerror = false;

    /**
     * Reset all mocked variables.
     */
    public static function reset(): void {
        self::$bindfailure = false;
        self::$searchfailure = false;
        self::$entries = [];
        self::$lastfilter = '';
        self::$lastattributes = [];
        self::$lastsearchresult = null;
        self::$returndiagnosticonerror = false;
    }
}

if (!function_exists('report_ldapaccounts\ldap_connect')) {
    /**
     * Mock ldap_connect from LDAP extension in \report_ldapreports\ldap.
     * @param string $uri
     * @return string
     */
    function ldap_connect($uri) {
        return 'ldap_connection';
    }
}

if (!function_exists('report_ldapaccounts\ldap_set_option')) {
    /**
     * Mock ldap_set_option from LDAP extension in \report_ldapreports\ldap.
     * @param resource|\LDAP\Connection $link
     * @param int $option
     * @param string $value
     * @return bool
     */
    function ldap_set_option($link, $option, $value) {
        return true;
    }
}

if (!function_exists('report_ldapaccounts\ldap_bind')) {
    /**
     * Mock ldap_bind from LDAP extension in \report_ldapreports\ldap.
     * @param resource|\LDAP\Connection $ldap
     * @param string $username
     * @param string $password
     * @return bool
     */
    function ldap_bind($ldap, $username, $password) {
        return !mock_ldap::$bindfailure;
    }
}

if (!function_exists('report_ldapaccounts\ldap_search')) {
    /**
     * Mock ldap_search from LDAP extension in \report_ldapreports\ldap.
     * @param resource|\LDAP\Connection $ldap
     * @param string $basedn
     * @param string $filter
     * @param array|null $attributes
     * @param int $attrsonly
     * @param int $sizelimit
     * @return string|bool
     */
    function ldap_search($ldap, $basedn, $filter, $attributes = null, $attrsonly = 0, $sizelimit = -1) {
        mock_ldap::$lastfilter = $filter;
        mock_ldap::$lastattributes = is_array($attributes) ? $attributes : [];
        if (mock_ldap::$searchfailure) {
            return false;
        }
        mock_ldap::$lastsearchresult = 'ldap_search_result';
        return mock_ldap::$lastsearchresult;
    }
}

if (!function_exists('report_ldapaccounts\ldap_get_entries')) {
    /**
     * Mock ldap_get_entries from LDAP extension in \report_ldapreports\ldap.
     * @param resource|\LDAP\Connection $ldap
     * @param string $result
     * @return array
     */
    function ldap_get_entries($ldap, $result) {
        return mock_ldap::$entries;
    }
}

if (!function_exists('report_ldapaccounts\ldap_count_entries')) {
    /**
     * Mock ldap_count_entries from LDAP extension in \report_ldapreports\ldap.
     * @param resource|\LDAP\Connection $ldap
     * @param string $result
     * @return int
     */
    function ldap_count_entries($ldap, $result) {
        return mock_ldap::$entries['count'] ?? 0;
    }
}

if (!function_exists('report_ldapaccounts\ldap_free_result')) {
    /**
     * Mock ldap_free_result from LDAP extension in \report_ldapreports\ldap.
     * @param string $result
     * @return bool
     */
    function ldap_free_result($result) {
        return true;
    }
}

if (!function_exists('report_ldapaccounts\ldap_close')) {
    /**
     * Mock ldap_close from LDAP extension in \report_ldapreports\ldap.
     * @param resource|\LDAP\Connection $ldap
     * @return bool
     */
    function ldap_close($ldap) {
        return true;
    }
}

if (!function_exists('report_ldapaccounts\ldap_get_option')) {
    /**
     * Mock ldap_get_option from LDAP extension in \report_ldapreports\ldap.
     * @param resource|\LDAP\Connection $ldap
     * @param int $option
     * @param string|null $error
     * @return bool
     */
    function ldap_get_option($ldap, $option, &$error) {
        if (mock_ldap::$returndiagnosticonerror) {
            $error = 'Simulated error';
            return true;
        }
        return false;
    }
}
