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
 * Description of potential_cohorts
 *
 * @author vukas
 */
class potential_cohorts extends cohort_selector {

    /**
     * @return array the options needed to recreate this user_selector.
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'blocks/zoola_reports/classes/potential_cohorts.php';
        return $options;
    }

    /**
     * Get cohorts that match $search and are not already selected
     *
     * @global \moodle_database $DB
     * @param string $search the search string.
     * @return array An array of arrays of cohorts.
     */
    public function find_users($search) {
        global $SESSION, $DB;

        // Exclude selected cohorts.
        $selected_cohorts = $SESSION->{BLOCK_ZOOLA_REPORTS_SESSION_KEY}[$this->blockid]->{BLOCK_ZOOLA_REPORTS_COHORTS_KEY};

        $where = '1 = 1';
        $params = array();

        if (!empty($selected_cohorts)) {
            list($selected_sql, $selected_params) = $DB->get_in_or_equal($selected_cohorts, SQL_PARAMS_NAMED, 'sel', false);
            $where .= " and id $selected_sql";
            $params = array_merge($params, $selected_params);
        }
        if (!empty($search)) {
            $where .= ' and ' . $DB->sql_like('name', ':name', false, false);
            $params['name'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        $cohorts = $DB->get_records_select('cohort', $where, $params);

        if (empty($cohorts)) {
            return $this->empty_array($search);
        }

        return $this->group_by_categories($cohorts);
    }

}
