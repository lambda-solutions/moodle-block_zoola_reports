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

/**
 * Allow Zoola Administrators to add block instances.
 *
 * @global moodle_database $DB
 */
function block_zoola_reports_role_setup() {
    global $DB;

    $context = context_system::instance();
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'zoola_administrator'));
    if ($roleid) {
        role_change_permission($roleid, $context, 'block/zoola_reports:addinstance', CAP_ALLOW);
        role_change_permission($roleid, $context, 'block/zoola_reports:myaddinstance', CAP_ALLOW);
    }
}
