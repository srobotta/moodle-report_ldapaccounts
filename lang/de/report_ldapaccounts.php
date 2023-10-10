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
 * @copyright  2023 Stephan Robotta <stephan.robotta@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['callreport'] = 'Bericht aufrufen';
$string['enable_emailstop'] = 'Benachrichtigungen einschalten';
$string['form_csv_delimiter'] = 'CSV Trennzeichen';
$string['form_download_csv'] = 'Download Bericht als CSV';
$string['form_error_column'] = 'Das Feld {0} existiert nicht in der Benutzertabelle.';
$string['form_error_input'] = 'Eingabe ungültig.';
$string['form_filter_auth'] = 'Authentifizierung';
$string['form_filter_deleted'] = 'Gelöscht';
$string['form_filter_email'] = 'E-Mail';
$string['form_filter_emailstop'] = 'Emailstop';
$string['form_filter_firstname'] = 'Vorname';
$string['form_filter_lastname'] = 'Nachname';
$string['form_filter_ldapstatus'] = 'LDAP Status';
$string['form_filter_suspended'] = 'Suspendiert';
$string['form_filter_userdata'] = 'Filter Benutzer';
$string['form_show_cols'] = 'Spalten';
$string['form_show_userdata'] = 'Anzeige der Benutzerdaten';
$string['ldapaccounts:view'] = 'Moodle Benutzerkonten in LDAP ansehen';
$string['ldapbasedn'] = 'LDAP base DN';
$string['ldapbasedn_desc'] = 'Der Wurzelknoten von dem aus alle LDAP Objekte hierarchisch gesucht werden.';
$string['ldapcacert'] = 'CA cert file';
$string['ldapcacert_desc'] = 'Zertifikatsdatei der CA. Falls die Verbindung zum Server nicht funktioniert kann man das Server Zertifikat hier angeben. Dazu muss es runtergeladen und lokal gespeichert werden:
   1. Zertifikat anzeigen lassen mittels: openssl s_client -connect example.com:636.
   2. Alles zwischen (und einschliesslich) -----BEGIN CERTIFICATE----- and -----END CERTIFICATE----- kopieren.
   3. Die kopierte Zeichenkette in einer Datei auf dem Server speichern.
   4. Diese Datei (inkl. Pfad) hier in der Einstellung angeben.
';
$string['ldapcert'] = 'Cert file';
$string['ldapcert_desc'] = 'Zertifikatsdatei des eigenen Moodle Servers, falls benötigt.';
$string['ldapmailfield'] = 'E-Mail Feld in LDAP';
$string['ldapmailfield_desc'] = 'Der Name des Feldes in LDAP in welchem die Mailadresse der Person gespeichert wird.';
$string['ldapnotconfigured'] = 'Die LDAP Verbindung ist nicht konfiguriert. Bitte in den [link]Einstellungen[/link] ändern.';
$string['ldappass'] = 'LDAP Passwort';
$string['ldappass_desc'] = 'Das Passwort des Benutzers.';
$string['ldapport'] = 'LDAP Server Port';
$string['ldapport_desc'] = 'Der Serverport auf welchem der Dienst erreichbar ist.';
$string['ldapquery'] = 'LDAP Abfrage';
$string['ldapquery_desc'] = 'Feste Abfrage an LDAP um Nutzer auszuwählen (z.B. `(&(objectClass=person)(objectClass=top))`). Diese Abfrage wird mit der email aus dem Benutzerdatensatz erweitert.';
$string['ldapserver'] = 'LDAP server';
$string['ldapserver_desc'] = 'Servername oder IP über welche der Dienst erreichbar ist.';
$string['ldapuser'] = 'LDAP Benutzer';
$string['ldapuser_desc'] = 'Der Name des Benutzers welcher sich mit dem Server verbinden soll.';
$string['logging'] = 'Aktiviere Logs für LDAP Kommunikation';
$string['logging_desc'] = 'Alle Anfragen und Antworten zum LDAP Server werden im Log protokolliert.';
$string['permalink'] = 'Direktlink für diesen Bericht';
$string['pluginname'] = 'Moodle Benutzerkonten in LDAP';
$string['privacy:metadata'] = 'LDAP Benutzerkonten Bericht in den Standardeinstellungen keine personenbezogenen Daten. Es können aber personenbezogene Daten im Log sowie in CSV Dateien auf dem Server abgelegt werden, wenn die Option aktiviert ist.';
$string['reportldapaccountsdesc'] = 'Der Bericht Moodle Benutzerkonten in LDAP zeigt auf, welche Benutzer in Moodle, und/oder nicht in LDAP vorkommen.';
$string['resultcount'] = '{0} Einträge gefunden.';
$string['settings'] = 'Einstellungen für Moodle Benutzerkonten in LDAP';
