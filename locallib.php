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

define('BLOCK_ZOOLA_REPORTS_SESSION_KEY', 'block_zoola_reports_config_edit');
define('BLOCK_ZOOLA_REPORTS_REPORTS_KEY', 'selectedReports');
define('BLOCK_ZOOLA_REPORTS_USERS_KEY',   'selectedUsers');
define('BLOCK_ZOOLA_REPORTS_COHORTS_KEY', 'selectedCohorts');

require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Is this block instance visible to user?
 *
 * @global moodle_database $DB
 * @param int $blockinstanceid
 * @param int $userid
 * @return boolean
 */
function block_zoola_reports_visible($blockinstanceid, $userid) {
    global $DB;

    $users = $DB->get_records('block_zoola_reports_user', array('blockinstanceid' => $blockinstanceid), null, 'userid');
    if (array_key_exists($userid, $users)) {
        // User is in list of selected users, so the block is visible.
        // No need to check cohorts.
        return true;
    }

    $cohorts = $DB->get_records('block_zoola_reports_cohort', array('blockinstanceid' => $blockinstanceid), null, 'cohortid');
    foreach ($cohorts as $cohort) {
        if (cohort_is_member($cohort->cohortid, $userid)) {
            // User belongs to some selected cohort, so the block is visible.
            return true;
        }
    }

    // User doesn't belong neither to selected users nor to selected cohorts,
    // so the block is visible only if both lists are empty.
    return (empty($users) && empty($cohorts));
}

function block_zoola_reports_emailreport($report_record, $report_params, $fromform, $zoola_token) {
    global $CFG, $USER;

    $report_engine = new block_zoola_reports\report_engine($zoola_token);
    $report_engine->input_controls($report_record->uri);
    $report = $report_engine->run_report($report_record->uri, 1, $report_params, $fromform->format);
    $attachment = tempnam($CFG->tempdir, 'zoo');
    if (!$attachment) {
        print_error('Cannot create report file');
    }
    file_put_contents($attachment, $report['content']);
    $attachname = "{$report_record->label}.{$fromform->format}";

    $user = new stdClass();
    $user->id = -1;
    $user->email = $fromform->to;
    $user->deleted = 0;
    $user->mailformat = 1;

    if ($fromform->from === 'me') {
        $from = $USER;
    } else {
        $from = 'No Reply';
    }

    $emailsent = email_to_user(
            $user,
            $from,
            $fromform->subject,
            format_text_email($fromform->message['text'], $fromform->message['format']),
            $fromform->message['text'],
            $attachment,
            $attachname);

    return $emailsent;
}

function block_zoola_reports_page_setup($courseid, $report_record) {
    global $PAGE, $USER;

    $PAGE->set_pagelayout('report');
    require_login($courseid);

    if (!$report_record) {
        print_error('reportnotfound', 'block_zoola_reports');
    }

    $PAGE->set_title($report_record->label);
    $PAGE->set_heading($report_record->label);

    if (!block_zoola_reports_visible($report_record->blockinstanceid, $USER->id)) {
        print_error('nopermissiontoshow');
    }

    if (!get_config('block_zoola', 'apikey')) {
        print_error('noapikey_error_message', 'block_zoola');
    }

    \block_zoola\segment_wrapper::identify();
    \block_zoola\segment_wrapper::page('Report page', array(
        'reportUri' => $report_record->uri,
        'reportLabel' => $report_record->label
    ));
}
