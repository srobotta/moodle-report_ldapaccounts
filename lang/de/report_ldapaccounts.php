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
 * Lang strings.
 *
 * @package    report_ldapaccounts
 * @copyright  2016 BFH-ITS, Luca Bösch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Moodle Benutzerkonten in LDAP';
$string['ldapaccounts:view'] = 'Moodle Benutzerkonten in LDAP ansehen';
$string['reportldapaccountsdesc'] = 'Der Bericht Moodle Benutzerkonten in LDAP zeigt auf, welche Benutzer in Moodle, und/oder nicht in LDAP vorkommen.';
$string['callreport'] = 'Bericht aufrufen';
$string['privacy:metadata'] = 'LDAP Benutzerkonten Bericht speichert keine personenbezogenen Daten.';
$string['settings'] = 'Einstellungen für Moodle Benutzerkonten in LDAP';
$string['ldapserver'] = 'LDAP server';
$string['ldapserver_desc'] = 'Servername oder IP über welche der Dienst erreichbar ist.';
$string['ldapuser'] = 'LDAP Benutzer';
$string['ldapuser_desc'] = 'Der Name des Benutzers welcher sich mit dem Server verbinden soll.';
$string['ldappass'] = 'LDAP Passwort';
$string['ldappass_desc'] = 'Das Passwort des Benutzers.';
$string['ldapbasedn'] = 'LDAP base DN';
$string['ldapbasedn_desc'] = 'Der Wurzelknoten von dem aus alle LDAP Objekte hierarchisch gesucht werden.';
$string['ldapport'] = 'LDAP Server Port';
$string['ldapport_desc'] = 'Der Serverport auf welchem der Dienst erreichbar ist.';
$string['ldapcert'] = 'Cert file';
$string['ldapcert_desc'] = 'Zertifikatsdatei des eigenen Moodle Servers, falls benötigt.';
$string['ldapcacert'] = 'CA cert file';
$string['ldapcacert_desc'] = 'Zertifikatsdatei der CA. Falls die Verbindung zum Server nicht funktioniert kann man das Server Zertifikat hier angeben. Dazu muss es runtergeladen und lokal gespeichert werden:
   1. Zertifikat anzeigen lassen mittels: openssl s_client -connect example.com:636.
   2. Alles zwischen (und einschliesslich) -----BEGIN CERTIFICATE----- and -----END CERTIFICATE----- kopieren.
   3. Die kopierte Zeichenkette in einer Datei auf dem Server speichern.
   4. Diese Datei (inkl. Pfad) hier in der Einstellung angeben.
';
$string['ldapmailfield'] = 'E-Mail Feld in LDAP';
$string['ldapmailfield_desc'] = 'Der Name des Feldes in LDAP in welchem die Mailadresse der Person gespeichert wird.';
$string['ldapquery'] = 'LDAP Abfrage';
$string['ldapquery_desc'] = 'Feste Abfrage an LDAP um Nutzer auszuwählen (z.B. `(&(objectClass=person)(objectClass=top))`). Diese Abfrage wird mit der email aus dem Benutzerdatensatz erweitert.';
