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
 * This class handles the synchornization between LDAP and Moodle.
 *
 * @package     report_ldapaccounts
 * @copyright   2026 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_accounts {
    /**
     * Output for log or display.
     * @var string[]
     */
    private array $log = [];

    /**
     * The ledap field name for the sso username.
     * @var string
     */
    private $usernamefield;

    /**
     * The ldap field name for the email address.
     * @var string
     */
    private $mailfield;

    /**
     * The query prefix for LDAP to select the correct records.
     * @var string
     */
    private $queryprefix;

    /**
     * Authmethod used when creating new user accounts in Moodle.
     * @var string
     */
    private $authmethod;

    /**
     * Last sync time.
     * @var int
     */
    private $lastsync;

    /**
     * Set the field name in LDAP where the sso user is stored.
     * @param string $field
     * @return self
     */
    public function set_username_field(string $field): self {
        $this->usernamefield = $field;
        return $this;
    }

    /**
     * Get the field name in LDAP where the sso user is stored.
     * Default is the setting report_ldapaccounts | ldapusernamefield
     *
     * @return string
     */
    public function get_username_field(): string {
        if ($this->usernamefield === null) {
            return (string)config::get_instance()->get_setting('ldapusernamefield');
        }
        return $this->usernamefield;
    }

    /**
     * Set the field name in LDAP where the email of the user is stored.
     *
     * @param string $field
     * @return self
     */
    public function set_mail_field(string $field): self {
        $this->mailfield = $field;
        return $this;
    }

    /**
     * Get the field name in LDAP where the email of the user is stored.
     * Default is the setting report_ldapaccounts | ldapmailfield
     *
     * @return string
     */
    public function get_mail_field(): string {
        if ($this->mailfield === null) {
            return (string)config::get_instance()->get_setting('ldapmailfield');
        }
        return $this->mailfield;
    }

    /**
     * Set query part for LDAP to select users.
     * @param string $prefix
     * @return self
     */
    public function set_queryprefix(string $prefix): self {
        $this->queryprefix = $prefix;
        return $this;
    }

    /**
     * Get query part for LDAP to select users.
     * Default is the setting report_ldapaccounts | ldapquery
     */
    public function get_queryprefix(): string {
        if ($this->queryprefix === null) {
            return (string)config::get_instance()->get_setting('ldapquery');
        }
        return $this->queryprefix;
    }

    /**
     * Set last sync time.
     * @param mixed $time
     * @return self
     */
    public function set_lastsync($time): self {
        if (is_string($time)) {
            $time = strtotime($time);
            if ($time === false) {
                throw new \moodle_exception('synctimeinvalid', 'report_ldapaccounts');
            }
            $this->lastsync = $time;
            return $this;
        }
        $time = (int)$time;
        if ($time < 0) {
            throw new \moodle_exception('synctimeinvalid', 'report_ldapaccounts');
        }
        $this->lastsync = $time;
        return $this;
    }

    /**
     * Get last sync time. If it was not set, the setting from
     * report_ldapaccounts | lastsyncrun is used.
     * @return int
     */
    public function get_lastsync(): int {
        if ($this->lastsync === null) {
            return config::get_instance()->get_last_sync_time();
        }
        return $this->lastsync;
    }

    /**
     * Set authentication method when an account is created in Moodle.
     * @param string $authmethod Authentication method name.
     * @return self
     * @throws \moodle_exception
     */
    public function set_authmethod(string $authmethod): self {
        $authmethods = config::get_instance()->get_available_auth_methods();
        if (!\array_key_exists($authmethod, $authmethods)) {
            throw new \moodle_exception('authmethoddisabled', 'report_ldapaccounts', '', $authmethod);
        }
        $this->authmethod = $authmethod;
        return $this;
    }

    /**
     * Get authentication method when an account is created in Moodle.
     * Default is the setting in report_ldapaccounts | syncauthmethod
     * @return string
     */
    public function get_authmethod(): string {
        if ($this->authmethod === null) {
            return (string)config::get_instance()->get_setting('syncauthmethod');
        }
        return $this->authmethod;
    }

    /**
     * Run the sync.
     *
     * @param bool $dryrun
     * @return self
     */
    public function exec(bool $dryrun = false): self {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');

        $usernamefield = $this->get_username_field();
        if (empty($usernamefield)) {
            throw new \moodle_exception('Setting report_ldapaccounts | ldapusernamefield must not be empty.');
        }
        $authmethod = $this->get_authmethod();
        if (empty($authmethod)) {
            throw new \moodle_exception('Setting report_ldapaccounts | syncauthmethod must not be empty.');
        }
        $mailfield = $this->get_mail_field();
        $resultfields = [$usernamefield, $mailfield, 'givenname', 'sn', 'preferredlanguage'];

        $ldap = ldap::init_from_config();
        try {
            $ldap->search('', $resultfields, $this->build_query_for_new_records());
            $ldapusers = $ldap->get_parsed_result();
        } finally {
            $ldap->close();
        }

        foreach ($ldapusers as $ldapuser) {
            $username = trim((string)($ldapuser[$usernamefield] ?? ''));
            if ($username === '') {
                continue;
            }
            $username = \core_text::strtolower($username);
            if (get_complete_user_data('username', $username)) {
                continue;
            }

            $context = \context_system::instance();
            require_capability('moodle/user:create', $context);

            $user = (object)[
                'username' => $username,
                'email' => trim((string)($ldapuser[$mailfield] ?? '')),
                'firstname' => trim((string)($ldapuser['givenname'] ?? '')),
                'lastname' => trim((string)($ldapuser['sn'] ?? '')),
                'auth' => $authmethod,
                'confirmed' => 1,
                'deleted' => 0,
                'lang' => trim((string)($ldapuser['preferredlanguage'] ?? '')),
                'description' => get_string('userdescription', 'report_ldapaccounts', userdate(time())),
                'descriptionformat' => FORMAT_PLAIN,
            ];

            try {
                if (!$dryrun) {
                    // Newer than any 5.2.x release.
                    if ($CFG->version > 2026042020) {
                        $authplugin = \core\di::get(\core\authentication::class)->get_plugin($authmethod);
                        $newpassword = !empty($user->newpassword)
                            ? \core\di::get(\core\authentication\password::class)->hash($user->newpassword)
                            : '';
                    } else {
                        $authplugin = get_auth_plugin($authmethod);
                        $newpassword = !empty($user->newpassword) ? hash_internal_user_password($usernew->newpassword) : '';
                    }
                    unset($user->newpassword);
                    $user = $this->create_new_user($user, $authplugin, $newpassword);
                }
                $this->log[] = 'Create user: ' . json_encode($user);
            } catch (\Exception $e) {
                debugging('report_ldapaccounts: could not create user from LDAP record: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        if (!$dryrun) {
            $this->lastsync = time();
            config::get_instance()->set_last_sync_time($this->lastsync);
        }
        return $this;
    }

    /**
     * Create a new user from the standard class data containing the user data from the LDAP mapping.
     * @param \stdClass $user
     * @param object $authplugin
     * @param string $newpassword (already hashed)
     * @return \stdClass
     */
    protected function create_new_user(\stdClass $user, $authplugin, string $newpassword): \stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/editlib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $user->mnethostid = $CFG->mnet_localhost_id;
        if (!isset($user->confirmed)) {
            $user->confirmed  = 1;
        }
        $user->timecreated = time();
        $createpassword = !empty($user->createpassword);
        unset($user->createpassword);
        if ($authplugin->is_internal()) {
            if ($createpassword || empty($newpassword)) {
                $user->password = '';
            } else {
                $user->password = $newpassword;
            }
        } else {
            $user->password = AUTH_PASSWORD_NOT_CACHED;
        }
        $user->id = user_create_user($user, false, false);
        if (!$authplugin->is_internal() && $authplugin->can_change_password() && !empty($newpassword)) {
            if (!$authplugin->user_update_password($user, $newpassword)) {
                // Do not stop here, we need to finish user creation.
                debugging(get_string('cannotupdatepasswordonextauth', 'error', $user->auth), DEBUG_NONE);
            }
        }

        // Update preferences.
        useredit_update_user_preference($user);

        // Update tags.
        if (isset($user->interests)) {
            useredit_update_interests($user, $user->interests);
        }

        // Save custom profile fields data.
        profile_save_data($user);

        // Reload from db.
        $usernew = $DB->get_record('user', ['id' => $user->id]);

        if ($createpassword) {
            setnew_password_and_mail($usernew);
            unset_user_preference('create_password', $usernew);
            set_user_preference('auth_forcepasswordchange', 1, $usernew);
        }

        // Trigger create event, after all fields are stored.
        \core\event\user_created::create_from_userid($usernew->id)->trigger();
        return $usernew;
    }

    /**
     * Build the LDAP filter used to fetch records created after the last sync.
     *
     * @return string
     */
    protected function build_query_for_new_records(): string {
        $lastsync = $this->get_lastsync();
        $fixedquery = $this->get_queryprefix();
        $querystring = "({$this->get_username_field()}=*)";
        if (!empty($fixedquery)) {
            $querystring = "(&{$fixedquery}{$querystring})";
        }
        if ($lastsync > 0) {
            $date = '(createTimestamp>=' . date('YmdHis', $lastsync) . 'Z)';
            if (str_starts_with($querystring, '(&')) {
                $querystring = substr($querystring, 0, -1) . $date . ')';
            } else {
                $querystring = "(&{$date}{$querystring})";
            }
        }
        return $querystring;
    }

    /**
     * Return the log lines.
     * @return string[]
     */
    public function get_log(): array {
        return $this->log;
    }
}
