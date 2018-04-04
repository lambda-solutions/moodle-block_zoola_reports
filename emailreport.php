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

require_once('../../config.php');
require_once($CFG->dirroot . "/blocks/zoola/locallib.php");
require_once($CFG->dirroot . "/blocks/zoola_reports/locallib.php");

$reportid = required_param('reportid', PARAM_INT);
$courseid = optional_param('courseid', SITEID, PARAM_INT);

/* @var $PAGE moodle_page */
$PAGE->set_url('/blocks/zoola_reports/emailreport.php', array('reportid' => $reportid, 'courseid' => $courseid));
$PAGE->set_pagelayout('report');

require_login($courseid);

/* @var $DB moodle_database */
$report_record = $DB->get_record('block_zoola_reports', array('id' => $reportid));
if (!$report_record) {
    print_error('reportnotfound', 'block_zoola_reports');
}
$PAGE->set_title('Send report');
$PAGE->set_heading('Send report');

if (!block_zoola_reports_visible($report_record->blockinstanceid, $USER->id)) {
    print_error('nopermissiontoshow');
}

if (!get_config('block_zoola', 'apikey')) {
    print_error('noapikey_error_message', 'block_zoola');
}

$zoola_token = block_zoola_get_report_token();

if (empty($zoola_token)) {
    print_error('notoken_error_message', 'block_zoola');
}

$mform = new block_zoola_reports\emailreport_form($PAGE->url, array('label' => $report_record->label));

$returnurl = new moodle_url('/blocks/zoola_reports/report.php', array('reportid' => $reportid, 'courseid' => $courseid, 'page' => 0));
if ($mform->is_cancelled()) {
    redirect($returnurl);
}

$fromform = $mform->get_data();
if ($fromform) {
    $emailsent = block_zoola_reports_emailreport($report_record, $SESSION->block_zoola_reports_filters[$reportid], $fromform, $zoola_token);
    if ($emailsent) {
        redirect($returnurl, get_string('emailsuccess', 'block_zoola_reports'));
    } else {
        print_error('emailfailed', 'block_zoola_reports', $returnurl->out(false));
    }

} else {
    $output = $PAGE->get_renderer('core'); /* @var $output core_renderer */
    echo $output->header();
    $mform->display();
    echo $output->footer();
}
