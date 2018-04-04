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
 * Description of selected_users
 *
 * @author vukas
 */
class selected_users extends selector_base {

    /**
     * @return array the options needed to recreate this user_selector.
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'blocks/zoola_reports/classes/selected_users.php';
        return $options;
    }

    /**
     *
     * @global \moodle_database $DB
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB, $SESSION;

        $selected_users = $SESSION->{BLOCK_ZOOLA_REPORTS_SESSION_KEY}[$this->blockid]->{BLOCK_ZOOLA_REPORTS_USERS_KEY};
        if (empty($selected_users)) {
            return array();
        }

        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        list($selected_sql, $selected_params) = $DB->get_in_or_equal($selected_users, SQL_PARAMS_NAMED, 'sel');
        $params = array_merge($params, $selected_params);

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
                WHERE $wherecondition AND u.id $selected_sql";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $selecteduserscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($selecteduserscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $selecteduserscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }

        return array('They can see reports' => $availableusers);
    }
}
