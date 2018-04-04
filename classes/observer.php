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

namespace block_zoola_reports;

defined('MOODLE_INTERNAL') || die();

/**
 * Listen to user_deleted and cohort_deleted events.
 *
 * @author vukas
 */
class observer
{
    /**
     *
     * @global \moodle_database $DB
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;
        if ($event->objecttable == 'user' && isset($event->objectid)) {
            // Delete user from all Zoola reports configurations.
            $DB->delete_records('block_zoola_reports_user', array('userid' => $event->objectid));
        }
    }

    /**
     *
     * @global \moodle_database $DB
     * @param \core\event\cohort_deleted $event
     */
    public static function cohort_deleted(\core\event\cohort_deleted $event) {
        global $DB;
        if ($event->objecttable == 'cohort' && isset($event->objectid)) {
            // Delete cohort from all Zoola reports configurations.
            $DB->delete_records('block_zoola_reports_cohort', array('cohortid' => $event->objectid));
        }
    }
}
