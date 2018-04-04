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

/**
 * Base class for report selectors
 *
 * @author vukas
 */
abstract class report_selector extends selector_base {

    protected $report_engine = null;

    /**
     * Lazy load the report engine
     *
     * @global type $USER
     * @return report_engine
     */
    protected function get_report_engine() {
        global $USER;
        if ($this->report_engine == null) {
            $token = block_zoola_get_token($USER);
            $this->report_engine = new report_engine($token);
        }
        return $this->report_engine;
    }

    /**
     * Convert a user object to a string suitable for displaying as an option in the list box.
     *
     * @param object $user the user (report in our case) to display.
     * @return string a string representation of the report.
     */
    public function output_user($user) {
        return $user->label;
    }

    /**
     * Override default message "No users match '...'" when $search is given and no reports are found.
     *
     * @param string $search
     * @return array
     */
    protected function empty_array($search) {
        if (empty($search)) {
            return array();
        } else {
            return array("No reports match '$search'" => array());
        }
    }
}
