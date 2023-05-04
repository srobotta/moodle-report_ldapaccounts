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

$string['callreport'] = 'Bericht aufrufen';
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
$string['ldapbasedn_desc'] = 'Der Wurzelknoten von dem aus alle LDAP Objekte hierarchisch gesucht werden.';
$string['ldapbasedn'] = 'LDAP base DN';
$string['ldapcacert'] = 'CA cert file';
$string['ldapcacert_desc'] = 'Zertifikatsdatei der CA. Falls die Verbindung zum Server nicht funktioniert kann man das Server Zertifikat hier angeben. Dazu muss es runtergeladen und lokal gespeichert werden:
   1. Zertifikat anzeigen lassen mittels: openssl s_client -connect example.com:636.
   2. Alles zwischen (und einschliesslich) -----BEGIN CERTIFICATE----- and -----END CERTIFICATE----- kopieren.
   3. Die kopierte Zeichenkette in einer Datei auf dem Server speichern.
   4. Diese Datei (inkl. Pfad) hier in der Einstellung angeben.
';
$string['ldapcert'] = 'Cert file';
$string['ldapcert_desc'] = 'Zertifikatsdatei des eigenen Moodle Servers, falls benötigt.';
$string['ldapmailfield_desc'] = 'Der Name des Feldes in LDAP in welchem die Mailadresse der Person gespeichert wird.';
$string['ldapmailfield'] = 'E-Mail Feld in LDAP';
$string['ldappass_desc'] = 'Das Passwort des Benutzers.';
$string['ldappass'] = 'LDAP Passwort';
$string['ldapport_desc'] = 'Der Serverport auf welchem der Dienst erreichbar ist.';
$string['ldapport'] = 'LDAP Server Port';
$string['ldapquery_desc'] = 'Feste Abfrage an LDAP um Nutzer auszuwählen (z.B. `(&(objectClass=person)(objectClass=top))`). Diese Abfrage wird mit der email aus dem Benutzerdatensatz erweitert.';
$string['ldapquery'] = 'LDAP Abfrage';
$string['ldapserver_desc'] = 'Servername oder IP über welche der Dienst erreichbar ist.';
$string['ldapserver'] = 'LDAP server';
$string['ldapuser_desc'] = 'Der Name des Benutzers welcher sich mit dem Server verbinden soll.';
$string['ldapuser'] = 'LDAP Benutzer';
$string['pluginname'] = 'Moodle Benutzerkonten in LDAP';
$string['privacy:metadata'] = 'LDAP Benutzerkonten Bericht speichert keine personenbezogenen Daten.';
$string['reportldapaccountsdesc'] = 'Der Bericht Moodle Benutzerkonten in LDAP zeigt auf, welche Benutzer in Moodle, und/oder nicht in LDAP vorkommen.';
$string['resultcount'] = '{0} Einträge gefunden.';
$string['settings'] = 'Einstellungen für Moodle Benutzerkonten in LDAP';
