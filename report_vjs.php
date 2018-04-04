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

if ($CFG->version < '2015051100') {
    print_error('moodle29required', 'block_zoola_reports');
}

$reportid = required_param('reportid', PARAM_INT);
$courseid = optional_param('courseid', SITEID, PARAM_INT);

/* @var $PAGE moodle_page */
$PAGE->set_url('/blocks/zoola_reports/report_vjs.php', array('reportid' => $reportid, 'courseid' => $courseid));
/* @var $DB moodle_database */
$report_record = $DB->get_record('block_zoola_reports', array('id' => $reportid));

block_zoola_reports_page_setup($courseid, $report_record);

$zoola_token = block_zoola_get_report_token();
if (!$zoola_token) {
    print_error('notoken_error_message', 'block_zoola');
}

$output = $PAGE->get_renderer('core'); /* @var $output core_renderer */

$track_properties = array(
    'report_URI' => $report_record->uri,
    'report_title' => $report_record->label
);

echo $output->header();
echo $output->heading($report_record->label);

$reportengine = new \block_zoola_reports\report_engine($zoola_token);
$resource = $reportengine->resource_details($report_record->uri);
if (!$resource) {
    echo get_string('connectionerror', 'block_zoola_reports');
} else if (property_exists($resource, 'errorCode')) {
    // We got an error. Display error message instead of report.
    echo $resource->message;
} else {
    $visualizeurl = rtrim(get_config('block_zoola', 'backendurl'), '/');

    if ($report_record->type == 'dashboard') {
        echo $output->render_from_template('block_zoola_reports/dashboard', array(
            'containerid' => 'block_zoola_reports-' . $reportid
        ));
        $PAGE->requires->js_call_amd('block_zoola_reports/dashboard', 'embedDashboard',
                array($visualizeurl, urlencode($zoola_token), $reportid, $report_record->uri));
    } else {
        $mform = new block_zoola_reports\emailreport_form($PAGE->url, array('label' => $report_record->label));
        $emailformbody = $mform->render();
        echo $output->render_from_template('block_zoola_reports/report', array(
            'containerid' => 'block_zoola_reports-' . $reportid
        ));
        echo $output->render_from_template('block_zoola_reports/emailform', array(
            'emailformbody' => $emailformbody
        ));
        $report_uri = $report_record->uri;
        $run_immediately = false;
        $filters = new stdClass();
        if ($report_record->type == 'reportOptions') {
            // Report options have predefined filters for a report.
            // Take those filters, and run the related report.
            $engine = new block_zoola_reports\report_engine($zoola_token);
            $options = $engine->resource_details($report_record->uri);
            $report_uri = $options->reportUri;
            foreach ($options->reportParameters as $parameter) {
                $filters->{$parameter->name} = $parameter->value;
            }
            // Since the filters are already set, run the report immediately.
            $run_immediately = true;
        }
        $PAGE->requires->js_call_amd('block_zoola_reports/report', 'embedReport',
                array($visualizeurl, urlencode($zoola_token), $reportid, $report_uri, false, $run_immediately, $filters));
    }
    \block_zoola\segment_wrapper::track('Report viewed on LMS', array(
        'report_URI' => $report_record->uri,
        'report_title' => $report_record->label,
        'report_type' => $report_record->type,
        'format' => 'embedded'
    ));
}

echo $output->footer();
