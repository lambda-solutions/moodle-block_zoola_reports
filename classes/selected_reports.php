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
 * Description of selected_reports
 *
 * @author vukas
 */
class selected_reports extends report_selector {

    /**
     * @return array the options needed to recreate this user_selector.
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'blocks/zoola_reports/classes/selected_reports.php';
        return $options;
    }

    /**
     *
     * @global type $SESSION
     * @param string $search the search string.
     * @return array An array of arrays of reports.
     */
    public function find_users($search) {
        global $SESSION;

        $reports = array();
        $search = strtolower($search);
        $selected_reports = $SESSION->{BLOCK_ZOOLA_REPORTS_SESSION_KEY}[$this->blockid]->{BLOCK_ZOOLA_REPORTS_REPORTS_KEY};
        foreach ($selected_reports as $report) {
            if (empty($search) || strpos(strtolower($report->label), $search) !== false) {
                $reports[] = $report;
            }
        }

        if (empty($reports)) {
            return $this->empty_array($search);
        }

        usort($reports, function($a, $b) {
            return strcmp($a->label, $b->label);
        });

        $result = array(
            'Selected reports' => array(),
            'Selected dashboards' => array()
        );
        foreach ($reports as $report) {
            if ($report->type == 'dashboard') {
                $result['Selected dashboards'][] = $report;
            } else {
                $result['Selected reports'][] = $report;
            }
        }

        return $result;
    }

    /**
     * Get the list of reports that were selected by doing optional_param then validating the result.
     *
     * @return array of report objects.
     */
    protected function load_selected_users() {
        global $SESSION;

        // See if we got anything.
        if ($this->multiselect) {
            // Original function uses PARAM_INT, but we need uri here.
            $reportUris = optional_param_array($this->name, array(), PARAM_PATH);
        } else if ($reportUri = optional_param($this->name, 0, PARAM_PATH)) {
            $reportUris = array($reportUri);
        }

        // If there are no reports there is nothing to load.
        if (empty($reportUris)) {
            return array();
        }

        $selected_reports = $SESSION->{BLOCK_ZOOLA_REPORTS_SESSION_KEY}[$this->blockid]->{BLOCK_ZOOLA_REPORTS_REPORTS_KEY};
        $reports = array();
        foreach ($reportUris as $uri) {
            if (isset($selected_reports[$uri])) {
                $reports[$uri] = $selected_reports[$uri];
            }
        }

        // If we are only supposed to be selecting a single report, make sure we do.
        if (!$this->multiselect && count($reports) > 1) {
            $reports = array_slice($reports, 0, 1);
        }

        return $reports;
    }

}
