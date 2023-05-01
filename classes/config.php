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
 * Defines {@link \report_ldapaccounts\config} class. This defined and reads all settings that
 * are required for this plugin.
 *
 * @package     report_ldapaccounts
 * @copyright   2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_ldapaccounts;

require_once($CFG->libdir.'/adminlib.php');

class config
{
    /**
     * Singleton instance is stored here.
     * @var config
     */
    private static $instance;

    /**
     * The defined settings keys and the default value.
     * Internally the setting names are all without the ldap prefix, except the
     * ldapmailfield setting.
     *
     * @var array
     */
    private $settingsanddefaults = [
        'ldapserver' => '',
        'ldapuser' => '',
        'ldappass' => '',
        'ldapbasedn' => '',
        'ldapport' => 636,
        'ldapcert' => '',
        'ldapcacert' => '',
        'ldapmailfield' => 'mail',
        'ldapquery' => '',
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
    public static function getInstance(): config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Fetch values from database.
     * @return array
     * @throws \dml_exception
     */
    private function getValues(): array
    {
        if ($this->values === null) {
            $this->values = [];
            $stored = get_config('report_ldapaccounts');
            foreach ($this->settingsanddefaults as $key => $default) {
                $shortkey = ($key !== 'ldapmailfield' && $key !== 'ldapquery')
                    ? str_replace('ldap', '', $key) : $key;
                $this->values[$shortkey] = (isset($stored->{$key}) && !empty($stored->{$key}))
                    ? $stored->{$key}
                    : $default;
                if ($key === 'ldapport') { // port needs to be an int to be typesafe later.
                    $this->values[$shortkey] = (int)$this->values[$shortkey];
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
    public function getSetting(string $name) {
        return $this->getValues()[$name] ?? null;
    }

    /**
     * Get settings as an associative array.
     * @return array
     * @throws \dml_exception
     */
    public function getSettingsAsArray(): array
    {
        return $this->getValues();
    }

    /**
     * Get settings as an object.
     * @return \stdClass
     * @throws \dml_exception
     */
    public function getSettingsAsObject(): \stdClass
    {
        return (object)$this->getValues();
    }

    /**
     * Add the config to the given admin_settingpage.
     * @param \admin_settingpage $settings
     * @return void
     * @throws \coding_exception
     */
    public function addConfigToSettingsPage(\admin_settingpage $settings): void {
        foreach ($this->settingsanddefaults as $key => $defaultval) {
            $settings->add(new \admin_setting_configtext(
                'report_ldapaccounts/' . $key,
                get_string($key, 'report_ldapaccounts'),
                get_string($key . '_desc', 'report_ldapaccounts'),
                $defaultval,
                ($key === 'ldapport') ? PARAM_INT : PARAM_RAW_TRIMMED
            ));
        }
    }
}