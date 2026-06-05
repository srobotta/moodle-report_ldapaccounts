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

require_once($CFG->libdir . '/adminlib.php');

/**
 * Helper class for settings that are required for this plugin.
 *
 * @package     report_ldapaccounts
 * @copyright   2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {
    /**
     * Singleton instance is stored here.
     * @var config
     */
    private static $instance;

    /**
     * The defined settings keys and default value, type of setting and section.
     * Internally the setting names are all without the ldap prefix, except the
     * ldapmailfield, and ldapquery setting.
     *
     * @var array
     */
    private $settingsanddefaults = [
        'ldapserver' => ['', PARAM_RAW_TRIMMED, 0],
        'ldapuser' => ['', PARAM_RAW_TRIMMED, 0],
        'ldappass' => ['', PARAM_RAW_TRIMMED, 0],
        'ldapbasedn' => ['', PARAM_RAW_TRIMMED, 0],
        'ldapport' => [636, PARAM_INT, 0],
        'ldapcert' => ['', PARAM_RAW_TRIMMED, 0],
        'ldapcacert' => ['', PARAM_RAW_TRIMMED, 0],
        'logging' => [false, PARAM_BOOL, 0],
        'ldapmailfield' => ['mail', PARAM_RAW_TRIMMED, 1],
        'ldapusernamefield' => ['', PARAM_RAW_TRIMMED, 1],
        'ldapquery' => ['', PARAM_RAW_TRIMMED, 1],
        'syncauthmethod' => ['', PARAM_RAW_TRIMMED, 1],
    ];

    /**
     * The real values that are used in the instance, either retrieved from the db or default value is used.
     * @var array
     */
    private $values;

    /**
     * Get singleton of class.
     * @return config
     */
    public static function get_instance(): config {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return the directory where the plugin generated files (csv and debug log) are stored.
     * @return string
     */
    public static function get_plugin_file_dir(): string {
        global $CFG;

        // Directory where to store the csv files.
        $dir = $CFG->tempdir . DIRECTORY_SEPARATOR . 'report_ldapaccounts';
        if (!is_dir($dir) && !mkdir($dir, $CFG->directorypermissions)) {
            throw new \RuntimeException('could not create temp directory for plugin user files');
        }
        return $dir;
    }

    /**
     * Fetch values from database.
     * @return array
     * @throws \dml_exception
     */
    private function get_values(): array {
        if ($this->values === null) {
            $this->values = [];
            $stored = get_config('report_ldapaccounts');
            foreach ($this->settingsanddefaults as $key => $tuple) {
                [$default, $paramtype] = $tuple;
                $shortkey = (!\in_array($key, ['ldapmailfield', 'ldapquery', 'ldapusernamefield']))
                    ? str_replace('ldap', '', $key) : $key;
                $this->values[$shortkey] = (isset($stored->{$key}) && !empty($stored->{$key}))
                    ? $stored->{$key}
                    : $default;
                if ($paramtype === PARAM_INT) { // Cast values according to their type.
                    $this->values[$shortkey] = (int)$this->values[$shortkey];
                } else if ($paramtype === PARAM_BOOL) {
                    $this->values[$shortkey] = (bool)$this->values[$shortkey];
                }
            }
            // This key is not visible in the settings, hence cannot be configured in the admin interface.
            $this->values['lastsyncrun'] = isset($stored->lastsyncrun) ? (int)$stored->lastsyncrun : 0;
        }
        return $this->values;
    }

    /**
     * Get a single setting. Settings keys are the short version from the keys of $settingsanddefaults without
     * the ldap prefix except for the key ldapmailfield and ldapquery.
     * @param string $name
     * @return mixed|null
     * @throws \dml_exception
     */
    public function get_setting(string $name) {
        return $this->get_values()[$name] ?? null;
    }

    /**
     * Get settings as an associative array.
     * @return array
     * @throws \dml_exception
     */
    public function get_settings_as_array(): array {
        return $this->get_values();
    }

    /**
     * Get settings as an object.
     * @return \stdClass
     * @throws \dml_exception
     */
    public function get_settings_as_object(): \stdClass {
        return (object)$this->get_values();
    }

    /**
     * Get the timestamp of the last successful LDAP sync.
     * @return int
     */
    public function get_last_sync_time(): int {
        return $this->get_values()['lastsyncrun'] ?? 0;
    }

    /**
     * Store the timestamp of the last successful LDAP sync.
     * @param int $timestamp
     * @return void
     */
    public function set_last_sync_time(int $timestamp): void {
        set_config('lastsyncrun', $timestamp, 'report_ldapaccounts');
        if ($this->values !== null) {
            $this->values['lastsyncrun'] = $timestamp;
        }
    }

    /**
     * Get available authentication methods as an array.
     * @return array
     */
    public static function get_available_auth_methods(): array {
        $authentication = \core\di::get(\core\authentication::class);
        $enabledauths = $authentication->get_enabled_plugins();
        $options = [];
        foreach ($enabledauths as $authname) {
            $options[$authname] = self::get_auth_display_name($authname);
        }
        asort($options);
        return $options;
    }

    /**
     * Check if given auth method is valid.
     * @param string $method
     * @return bool
     */
    public static function is_valid_auth_method(string $method): bool {
        return \array_key_exists($method, self::get_available_auth_methods());
    }

    /**
     * Get the display name for an authentication method.
     * @param string $authname
     * @return string
     */
    private static function get_auth_display_name(string $authname): string {
        $component = 'auth_' . $authname;
        $displayname = get_string('pluginname', $component);
        if ($displayname === '[[' . $component . ']]') {
            // Fall back to the auth name if the language string is not found.
            return $authname;
        }
        return $displayname;
    }

    /**
     * Add the config to the given admin_settingpage.
     * @param \admin_settingpage $settings
     * @return void
     * @throws \coding_exception
     */
    public function add_config_to_settings_page(\admin_settingpage $settings): void {
        $sectionheadings = [];
        for ($i = 0; $i < 2; $i++) {
            $sectionheadings[] = new \admin_setting_heading(
                'report_ldapaccounts/ldapsettings_section_' . $i,
                get_string('settings_header_' . $i, 'report_ldapaccounts'),
                get_string('settings_header_' . $i . '_desc', 'report_ldapaccounts')
            );
        }
        $currentsection = -1;
        foreach ($this->settingsanddefaults as $key => $tuple) {
            if ($currentsection !== $tuple[2]) {
                $settings->add($sectionheadings[$tuple[2]]);
                $currentsection = $tuple[2];
            }
            if ($key === 'syncauthmethod') {
                // For authentication method, we need a selector.
                // When the current set authentication method is not available anymore,
                // still add it to the list to prevend that it is not accidently changed
                // to something else when settings are saved.
                $authmethods = self::get_available_auth_methods();
                $currentmethod = $this->get_values()[$key];
                if (!empty($currentmethod) && !\array_key_exists($currentmethod, $authmethods)) {
                    $authmethods[$currentmethod] = $currentmethod;
                }
                $settings->add(new \admin_setting_configselect(
                    'report_ldapaccounts/' . $key,
                    get_string($key, 'report_ldapaccounts'),
                    get_string($key . '_desc', 'report_ldapaccounts'),
                    $tuple[0],
                    $authmethods
                ));
            } else {
                $configclass = $tuple[1] === PARAM_BOOL ? '\admin_setting_configcheckbox' : '\admin_setting_configtext';
                $settings->add(new $configclass(
                    'report_ldapaccounts/' . $key,
                    get_string($key, 'report_ldapaccounts'),
                    get_string($key . '_desc', 'report_ldapaccounts'),
                    $tuple[0],
                    $tuple[1]
                ));
            }
        }
    }
}
