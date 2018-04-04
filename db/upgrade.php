<?php
// This file is part of Zoola Analytics block plugin for Moodle.
//
// Zoola Analytics block plugin for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Zoola Analytics block plugin for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Zoola Analytics block plugin for Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package block_zoola_reports
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Branko Vukasovic <branko.vukasovic@lambdasolutions.net>
 * @copyright (C) 2017 onwards Lambda Solutions, Inc. (https://www.lambdasolutions.net)
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_zoola_reports_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2017052300) {

        // Define table block_zoola_reports_cohort to be created.
        $table = new xmldb_table('block_zoola_reports_cohort');

        // Adding fields to table block_zoola_reports_cohort.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('blockinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_zoola_reports_cohort.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('blockinstance', XMLDB_KEY_FOREIGN, array('blockinstanceid'), 'block_instances', array('id'));
        $table->add_key('cohort', XMLDB_KEY_FOREIGN, array('cohortid'), 'cohort', array('id'));

        // Adding indexes to table block_zoola_reports_cohort.
        $table->add_index('blockinstancecohort', XMLDB_INDEX_UNIQUE, array('blockinstanceid', 'cohortid'));

        // Conditionally launch create table for block_zoola_reports_cohort.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Zoola_reports savepoint reached.
        upgrade_block_savepoint(true, 2017052300, 'zoola_reports');
    }

    return true;
}
