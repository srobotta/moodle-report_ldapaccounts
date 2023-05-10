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
 * Defines {@link \report_ldapaccounts\ldap} class.
 * This class handles the LDAP communication and encapsulates it from the caller.
 *
 * @package     report_ldapaccounts
 * @copyright   2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_ldapaccounts;

use LDAP\Connection;

class ldap {

    /**
     * LDAP username.
     * @var string
     */
    private $username;

    /**
     * The LDAP server.
     * @var string
     */
    private $server;

    /**
     * The LDAP password.
     * @var string
     */
    private $password;

    /**
     * The LDAP port.
     * @var int
     */
    private $port = 636;

    /**
     * The LDAP basedn. Base DN refers to the top level OU on the LDAP server.
     * @var string
     */
    private $basedn;

    /**
     * Cert file.
     * @var string
     */
    private $certfile;

    /**
     * CA cert file.
     * @var string
     */
    private $cacertfile;

    /**
     * The LDAP connection once established.
     * @var resource|Connection
     */
    private $ldap;

    /**
     * The result from the last query that is received with ldap_get_entries().
     * @var array
     */
    private $result;

    /**
     * Number of results of the last query;
     * @var int
     */
    private $count;

    /**
     * Whether logs are enabled or not.
     * @var bool
     */
    private $logging;

    /**
     * @param string $server
     * @param string $basedn
     * @param string $username
     * @param string|null $password
     * @param int|null $port
     * @return void
     */
    public function __construct(string $server, string $basedn, string $username, string $password = null, int $port = null) {
        $this->server = strpos($server, '://') === false ? 'ldaps://' . $server : $server;
        $this->basedn = $basedn;
        $this->username = $username;
        if (!empty($password)) {
            $this->password = $password;
        }
        if (!is_null($port)) {
            $this->port = $port;
        }
        $this->result = [];
        $this->count = 0;
    }

    /**
     * Instantiate a new ldap object from the settings of the db.
     * @return ldap
     * @throws \dml_exception
     */
    public static function init_from_config(): ldap {
        $cfg = config::get_instance()->get_settings_as_object();
        $ldap = new self($cfg->server, $cfg->basedn, $cfg->user, $cfg->pass, $cfg->port);
        if (!empty($cfg->cert)) {
            $ldap->set_certfile($cfg->cert);
        }
        if (!empty($cfg->cacert)) {
            $ldap->set_cacertfile($cfg->cacert);
        }
        if ($cfg->logging) {
            $ldap->enable_logging();
        } else {
            $ldap->disable_logging();
        }
        return $ldap;
    }

    /**
     * Enable logging of all LDAP communication.
     * @return ldap
     */
    public function enable_logging(): ldap {
        $this->logging = true;
        return $this;
    }

    /**
     * Disable logging of all LDAP communication.
     * @return ldap
     */
    public function disable_logging(): ldap {
        $this->logging = false;
        return $this;
    }

    /**
     * @param string $certfile
     * @return ldap
     */
    public function set_certfile(string $certfile): ldap {
        $this->certfile = $certfile;
        return $this;
    }

    /**
     * @param string $cacertfile
     * @return ldap
     */
    public function set_cacertfile(string $cacertfile): ldap {
        $this->cacertfile = $cacertfile;
        return $this;
    }

    /**
     * Prior to PHP8.1 a resource is returned.
     * @return resource|Connection
     */
    protected function get_connection() {
        if ($this->ldap === null) {
            if (!empty($this->certfile) && file_exists($this->certfile)) {
                ldap_set_option(null, LDAP_OPT_X_TLS_CERTFILE, $this->certfile);
            }
            if (!empty($this->cacertfile)) {
                ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, $this->cacertfile);
            }

            $this->ldap = ldap_connect($this->server, $this->port);
            if ($this->ldap === false) {
                throw new \RuntimeException('Could not connect to server');
            }
            if (!ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3)) {
                throw new \RuntimeException('could not set protocol to v3');
            }
            ldap_set_option($this->ldap, LDAP_OPT_REFERRALS, false);
            $bind = ldap_bind($this->ldap, $this->username, $this->password);

            if (!$bind) {
                if (ldap_get_option($this->ldap, LDAP_OPT_DIAGNOSTIC_MESSAGE, $error)) {
                    throw new \RuntimeException("Error Binding to LDAP: $error");
                } else {
                    throw new \RuntimeException('Error Binding to LDAP: No additional information is available.');
                }
            }
        }
        return $this->ldap;
    }

    /**
     * @param string $filter
     * @param array $justthese
     * @param array|string $result
     * @return void
     * @throws \coding_exception
     */
    protected function log(string $filter, array $justthese, $result)
    {
        if (!$this->logging) {
            return;
        }
        $data = [
            'other' => [
                'filter' => $filter,
                'justthese' => implode(',', $justthese),
                'result' => !is_string($result) ? json_encode($result) : $result,
            ]
        ];
        $logger = event\log_debug::create($data);
        $logger->trigger();
    }

    /**
     * @param array|string $searchfields
     * @param array|string|null $resultfields
     * @param string|null $fixedquerypart
     * @return ldap
     */
    public function search($searchfields, $resultfields = null, string $fixedquerypart = null): ldap {
        $filter = $fixedquerypart . $this->get_filter($searchfields);
        $justthese = $this->get_result_fields($resultfields);
        $search = ldap_search($this->get_connection(), $this->basedn, $filter, $justthese);
        if (!$search) {
            if (ldap_get_option($this->ldap, LDAP_OPT_DIAGNOSTIC_MESSAGE, $error)) {
                $this->log($filter, $justthese, 'Error searching LDAP: ' . $error);
                throw new \RuntimeException("Error searching in LDAP: $error");
            } else {
                $this->log($filter, $justthese, 'Error searching LDAP: No additional information is available.');
                throw new \RuntimeException('Error searching in LDAP: No additional information is available.');
            }
        }

        $this->count = ldap_count_entries($this->get_connection(), $search);
        if ($this->count > 0) {
            $this->result = ldap_get_entries($this->get_connection(), $search);
        } else {
            $this->result = [];
        }
        $this->log($filter, $justthese, $this->get_raw_result());
        ldap_free_result($search);
        return $this;
    }

    /**
     * Parse the result from the last successful ldap search and return an associative array with key the name
     * of the field and value the field value. The keys should be the ones that were defined before in the
     * $resultfields.
     *
     * @return array
     */
    public function get_parsed_result(): array {
        $data = [];
        if (!empty($this->result)) {
            for ($i = 0; $i < $this->result['count']; $i++) {
                foreach ($this->result[$i] as $key => $val) {
                    if (is_string($key) && $key !== 'count') {
                        if (is_array($val)) {
                            $data[$i][$key] = $val[0] ?? '';
                        } else {
                            $data[$i][$key] = $val;
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Get the raw result of the last successful query as it is received from ldap_get_entries().
     * @return array
     */
    public function get_raw_result(): array {
        return $this->result;
    }

    /**
     * Get number of results from last query.
     * @return int
     */
    public function get_count(): int {
        return $this->count;
    }

    /**
     * From the input format a query string for the ldap search.
     * @param $searchfields
     * @return string
     */
    protected function get_filter($searchfields): string {
        $filter = '';
        if (is_string($searchfields)) {
            $filter = $searchfields;
        } else if (is_array($searchfields)) {
            if (count($searchfields) > 1) {
                $filter = '(&';
            }
            foreach ($searchfields as $key => $value) {
                if (is_array($value)) {
                    $filter .= '(|';
                    foreach ($value as $val) {
                        $filter .= '(' . $key . '=' . $val . ')';
                    }
                    $filter .= ')';
                } else {
                    $filter .= '(' . $key . '=' . $value . ')';
                }
            }
            if (count($searchfields) > 1) {
                $filter .= ')';
            }
        }
        return $filter;
    }

    /**
     * Format the result fields into an array that can be used in a ldap query.
     * @param $resultfields
     * @return array
     */
    protected function get_result_fields($resultfields = null): array {
        $justthese = [];
        if (is_string($resultfields)) {
            $resultfields = trim($resultfields);
            if (!empty($resultfields)) {
                $justthese[] = trim($resultfields);
            }
        } else if (is_array($resultfields)) {
            $justthese = array_filter(
                array_map(
                    function ($i) {
                        return trim($i);
                    },
                    array_values($resultfields)
                ),
            );
        }
        return $justthese;
    }

    /**
     * Close ldap connection.
     * @return void
     */
    public function close(): void {
        if (!empty($this->ldap)) {
            ldap_close($this->ldap);
            $this->ldap = null;
        }
    }
}
