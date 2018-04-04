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
$page = optional_param('page', null, PARAM_INT);

/* @var $PAGE moodle_page */
$PAGE->set_url('/blocks/zoola_reports/report.php', array('reportid' => $reportid, 'courseid' => $courseid));
/* @var $DB moodle_database */
$report_record = $DB->get_record('block_zoola_reports', array('id' => $reportid));

block_zoola_reports_page_setup($courseid, $report_record);

$zoola_token = block_zoola_get_report_token();
if ($zoola_token) {
    $output = $PAGE->get_renderer('core'); /* @var $output core_renderer */
    $report = false;

    $report_engine = new block_zoola_reports\report_engine($zoola_token);
    $track_properties = array(
        'report_URI' => $report_record->uri,
        'report_title' => $report_record->label
    );

    // First check if report exists and is accessible.
    $report_details = $report_engine->resource_details($report_record->uri);
    if (isset($report_details->message)) {
        // Report is not accessible.
        $report = (array) $report_details;
        \block_zoola\segment_wrapper::track('Report not accessible from LMS', array_merge($track_properties, $report));
    } else {
        if ($report_record->type === "reportUnit") {
            $input_controls = $report_engine->input_controls($report_record->uri);
        } else {
            $input_controls = array();
        }

        // Allow user to enter input parameters.
        $mform = new \block_zoola_reports\report_form($PAGE->url, array("input_controls" => $input_controls));
        if (isset($page)) {
            // In case of pagination get form data from session.
            if (isset($SESSION->block_zoola_reports_filters) && array_key_exists($reportid, $SESSION->block_zoola_reports_filters)) {
                $formdata = $SESSION->block_zoola_reports_filters[$reportid];
                $mform->set_data($formdata);
            }
        } else {
            $page = 0;
            $formdata = $mform->get_data();
            // Save form data for pagination.
            if (!isset($SESSION->block_zoola_reports_filters)) {
                $SESSION->block_zoola_reports_filters = array();
            }
            $SESSION->block_zoola_reports_filters[$reportid] = $formdata;
        }
        if (isset($formdata->pdf)) {
            unset($SESSION->block_zoola_reports_filters[$reportid]->pdf);
            $format = 'pdf';
        } else if (isset($formdata->xlsx)) {
            unset($SESSION->block_zoola_reports_filters[$reportid]->xlsx);
            $format = 'xlsx';
        } else if (isset($formdata->email)) {
            unset($SESSION->block_zoola_reports_filters[$reportid]->email);
            $emailurl = new moodle_url('/blocks/zoola_reports/emailreport.php', array('reportid' => $reportid, 'courseid' => $courseid));
            redirect($emailurl);
        } else {
            $format = 'html';
        }

        $track_properties['format'] = $format;
        if ($format == 'html') {
            // Pagination is used only for html reports.
            $track_properties['page'] = $page + 1;
        }
        if (empty($input_controls)) {
            // Report does not have any parameter, or it is a report option, so it should be run immediately.
            \block_zoola\segment_wrapper::track('Report ran on LMS', $track_properties);
            $report = $report_engine->run_report($report_record->uri, $page + 1, null, $format);
        } else if ($formdata) {
            // In this case you process validated data. $mform->get_data() returns data posted in form.
            $segmentFilter = (array)$formdata;
            unset($segmentFilter['pdf']);
            unset($segmentFilter['xlsx']);
            unset($segmentFilter['submitbutton']);
            $track_properties['filter'] = $segmentFilter;
            \block_zoola\segment_wrapper::track('Report ran on LMS', $track_properties);
            $report = $report_engine->run_report($report_record->uri, $page + 1, $formdata, $format);
        }
        if ($format != 'html') {
            // Just download report.
            $filename = "{$report_record->label}.{$format}";
            $mime = mimeinfo('type', $filename);
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . strlen($report['content']));
            header('Content-Type: ' . $mime);
            echo $report['content'];
            die();
        }
    }

    echo $output->header();
    echo $output->heading($report_record->label);
    if (isset($mform)) {
        $mform->display();
    }
    if ($report !== false) {
        if (array_key_exists('status', $report) && $report['status'] == 'ready') {
            if ($report['totalpages'] > 0) {
                $this_page = new moodle_url(
                    '/blocks/zoola_reports/report.php',
                    array('reportid' => $reportid)
                );
                $pagination = new paging_bar($report['totalpages'], $page, 1, $this_page);
                echo $output->render($pagination);
                echo $report['content'];
                echo $output->render($pagination);
            } else {
                echo $output->notification(get_string('emptyreport', 'block_zoola_reports'));
            }
        } else if (array_key_exists('message', $report)) {
            echo $output->notification($report['message'] . '<br>Please contact the site administrator.');
        } else {
            echo $output->notification('Unknown error occured at Zoola server!<br>Please contact the site administrator.');
        }
    }
    echo $output->footer();
} else {
    print_error('notoken_error_message', 'block_zoola');
}
