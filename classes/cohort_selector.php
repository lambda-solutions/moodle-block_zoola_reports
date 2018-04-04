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

require_once($CFG->dirroot . '/blocks/zoola/locallib.php');
require_once($CFG->libdir . '/coursecatlib.php');

/**
 * Base class for cohort selectors
 *
 * @author vukas
 */
abstract class cohort_selector extends selector_base {

    /**
     * Convert a user object to a string suitable for displaying as an option in the list box.
     *
     * @param object $user the user (cohort in our case) to display.
     * @return string a string representation of the report.
     */
    public function output_user($user) {
        return $user->name;
    }

    /**
     * Override default message "No users match '...'" when $search is given and no cohorts are found.
     *
     * @param string $search
     * @return array
     */
    protected function empty_array($search) {
        if (empty($search)) {
            return array();
        } else {
            $cohorts = strtolower(get_string('cohorts', 'cohort'));
            return array("No $cohorts match '$search'" => array());
        }
    }

    protected function group_by_categories($cohorts) {
        // Mimic user objects, required by \user_selector_base class.
        // $grouped array will be grouped by course categories.
        $grouped = array();

        // Lookup context id => category name.
        $categories = array();

        // First, add system context as category.
        $syscontext = \context_system::instance();
        $categories[$syscontext->id] = $syscontext->get_context_name();
        $grouped[$categories[$syscontext->id]] = array();

        // Now, add all course categories.
        $displaylist = \coursecat::make_categories_list();
        foreach ($displaylist as $cid => $name) {
            $context = \context_coursecat::instance($cid);
            $categories[$context->id] = $name;
            $grouped[$name] = array();
        }

        // Add cohorts to course categories.
        foreach ($cohorts as $cohort) {
            $grouped[$categories[$cohort->contextid]][$cohort->id] = $cohort;
        }

        // Returnt nonempty categories.
        return array_filter($grouped);
    }
}
