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

require_once($CFG->libdir.'/adminlib.php');

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
     * The defined settings keys and default value, type of setting.
     * Internally the setting names are all without the ldap prefix, except the
     * ldapmailfield, and ldapquery setting.
     *
     * @var array
     */
    private $settingsanddefaults = [
        'ldapserver' => ['', PARAM_RAW_TRIMMED],
        'ldapuser' => ['', PARAM_RAW_TRIMMED],
        'ldappass' => ['', PARAM_RAW_TRIMMED],
        'ldapbasedn' => ['', PARAM_RAW_TRIMMED],
        'ldapport' => [636, PARAM_INT],
        'ldapcert' => ['', PARAM_RAW_TRIMMED],
        'ldapcacert' => ['', PARAM_RAW_TRIMMED],
        'ldapmailfield' => ['mail', PARAM_RAW_TRIMMED],
        'ldapquery' => ['', PARAM_RAW_TRIMMED],
        'logging' => [false, PARAM_BOOL],
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
                $shortkey = ($key !== 'ldapmailfield' && $key !== 'ldapquery')
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
     * Add the config to the given admin_settingpage.
     * @param \admin_settingpage $settings
     * @return void
     * @throws \coding_exception
     */
    public function add_config_to_settings_page(\admin_settingpage $settings): void {
        foreach ($this->settingsanddefaults as $key => $tuple) {
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
