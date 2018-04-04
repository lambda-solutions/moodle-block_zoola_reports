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

require_once($CFG->dirroot . "/blocks/zoola/locallib.php");
require_once($CFG->dirroot . '/blocks/zoola_reports/locallib.php');

class block_zoola_reports extends block_base {

    static protected $zoolatoken = '';
    static protected $reportengine = null;

    public function init() {
        $this->title = get_string('pluginname', 'block_zoola_reports');
    }

    /**
     *
     * @global type $CFG
     * @global type $USER
     * @global moodle_database $DB
     * @return stdObject
     */
    public function get_content() {
        global $CFG, $USER, $DB;

        if (!isloggedin()) {
            $this->content = new stdClass();
            return $this->content;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();

        $output = '';

        try {
            if (block_zoola_reports_visible($this->instance->id, $USER->id)) {
                $reports = $DB->get_records('block_zoola_reports',
                        array('blockinstanceid' => $this->instance->id),
                        'label', 'id, label, uri, type');
                if (empty($reports)) {
                    $output = get_string('noreports', 'block_zoola_reports');
                } else if (empty($this->config->runreport) || $CFG->version < '2015051100') {
                    // Moodle 2.9 is required to show inline reports.
                    $output = $this->show_links($reports);
                } else {
                    $output = $this->show_reports($reports);
                }
            }
        } catch (Exception $ex) {
            debugging($ex->getMessage());
        }

        $this->content->text = $output;
        return $this->content;
    }

    protected function show_links(array $reports) {
        global $CFG;
        $links = array();
        foreach ($reports as $id => $report) {
            $report_url = new moodle_url(
                    $CFG->version < '2015051100' ? '/blocks/zoola_reports/report.php' : '/blocks/zoola_reports/report_vjs.php',
                    array('reportid' => $id, 'courseid' => $this->page->course->id));
            $links[] = html_writer::link($report_url, $report->label);
        }
        $output = $this->page->get_renderer('core');
        return $output->list_block_contents(array(), $links);
    }

    protected function get_report_default_filters($resource) {
        $filters = new stdClass();
        if ($resource->resourceType == 'reportOptions') {
            // Report options have predefined filters for a report.
            // Use those filters as default filters.
            foreach ($resource->reportParameters as $parameter) {
                $filters->{$parameter->name} = $parameter->value;
            }
        }
        return $filters;
    }

    protected function show_reports(array $reports) {
        $visualizeurl = rtrim(get_config('block_zoola', 'backendurl'), '/');
        $output = '';
        $renderer = $this->page->get_renderer('core');
        if ($this->page->requires->should_create_one_time_item_now('block_zoola_reports_reportrunner_init')) {
            // Only one identify per page is enough.
            \block_zoola\segment_wrapper::identify();
        }
        if (!self::$zoolatoken) {
            self::$zoolatoken = block_zoola_get_report_token();
            self::$reportengine = new \block_zoola_reports\report_engine(self::$zoolatoken);
        }
        if (!self::$reportengine->login()) {
            return get_string('connectionerror', 'block_zoola_reports');
        }
        foreach ($reports as $report) {
            $resource = self::$reportengine->resource_details($report->uri);
            if (property_exists($resource, 'errorCode')) {
                // We got an error. Display error message instead of report.
                $output .= "<div>$resource->message</div>";
                continue;
            }

            $reportid = 'report_' . $report->id;
            if ($report->type == 'dashboard') {
                $output .= $renderer->render_from_template('block_zoola_reports/dashboard', array(
                    'containerid' => 'block_zoola_reports-' . $reportid
                ));
                $this->page->requires->js_call_amd('block_zoola_reports/dashboard', 'embedDashboard',
                        array($visualizeurl, urlencode(self::$zoolatoken), $reportid, $report->uri));
            } else {
                $output .= $renderer->render_from_template('block_zoola_reports/report', array(
                    'containerid' => 'block_zoola_reports-' . $reportid
                ));
                $filters = $this->get_report_default_filters($resource);
                // For report options, run related report
                $reporturi = $resource->resourceType == 'reportOptions' ? $resource->reportUri : $resource->uri;
                $this->page->requires->js_call_amd('block_zoola_reports/report', 'embedReport',
                        array($visualizeurl, urlencode(self::$zoolatoken), $reportid, $reporturi, !empty($this->config->fit), true, $filters));
            }
            \block_zoola\segment_wrapper::track('Report viewed on LMS', array(
                'report_URI' => $report->uri,
                'report_title' => $report->label,
                'report_type' => $report->type,
                'format' => 'embedded'
            ));
        }
        return $output;
    }

    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('pluginname', 'block_zoola_reports');
            } else {
                $this->title = $this->config->title;
            }
        }
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function instance_create() {
        \block_zoola\segment_wrapper::track('Zoola Reports Block Instance Created', array());
        return parent::instance_create();
    }

    /**
     * Delete everything related to this instance if you have been using persistent storage other than the configdata field.
     *
     * @global moodle_database $DB
     * @return boolean
     */
    public function instance_delete() {
        global $DB;
        $DB->delete_records('block_zoola_reports', array('blockinstanceid' => $this->instance->id));
        $DB->delete_records('block_zoola_reports_user', array('blockinstanceid' => $this->instance->id));
        $DB->delete_records('block_zoola_reports_cohort', array('blockinstanceid' => $this->instance->id));
        \block_zoola\segment_wrapper::track('Zoola Reports Block Instance Deleted', array('blockInstanceId' => $this->instance->id));
        return parent::instance_delete();
    }

    public function before_delete() {
        \block_zoola\segment_wrapper::track('Zoola Reports Block Uninstalled', array());
        parent::before_delete();
    }

    /**
     * Copy any block-specific data when copying to a new block instance.
     *
     * @global moodle_database $DB
     * @param int $fromid the id number of the block instance to copy from
     * @return boolean
     */
    public function instance_copy($fromid) {
        global $DB;

        // Delete previous settings, even though they should not exist.
        $DB->delete_records('block_zoola_reports', array('blockinstanceid' => $this->instance->id));
        $DB->delete_records('block_zoola_reports_user', array('blockinstanceid' => $this->instance->id));
        $DB->delete_records('block_zoola_reports_cohort', array('blockinstanceid' => $this->instance->id));

        // Copy reports settings to this instance.
        $reports_config = $DB->get_records('block_zoola_reports', array('blockinstanceid' => $fromid));
        foreach ($reports_config as $config) {
            $config->blockinstanceid = $this->instance->id;
        }
        $DB->insert_records('block_zoola_reports', $reports_config);

        // Copy users settings to this instance.
        $users_config = $DB->get_records('block_zoola_reports_user', array('blockinstanceid' => $fromid));
        foreach ($users_config as $config) {
            $config->blockinstanceid = $this->instance->id;
        }
        $DB->insert_records('block_zoola_reports_user', $users_config);

        // Copy cohorts settings to this instance.
        $cohorts_config = $DB->get_records('block_zoola_reports_cohort', array('blockinstanceid' => $fromid));
        foreach ($cohorts_config as $config) {
            $config->blockinstanceid = $this->instance->id;
        }
        $DB->insert_records('block_zoola_reports_cohort', $cohorts_config);

        return parent::instance_copy($fromid);
    }

    /**
     * Serialize and store config data
     *
     * @global moodle_database $DB
     * @param type $data
     * @param type $nolongerused
     */
    public function instance_config_save($data, $nolongerused = false) {
        global $SESSION, $DB;
        $sessionKey = BLOCK_ZOOLA_REPORTS_SESSION_KEY;
        if (isset($SESSION->$sessionKey)) {
            // Delete previous settings.
            $DB->delete_records('block_zoola_reports', array('blockinstanceid' => $this->instance->id));
            $DB->delete_records('block_zoola_reports_user', array('blockinstanceid' => $this->instance->id));
            $DB->delete_records('block_zoola_reports_cohort', array('blockinstanceid' => $this->instance->id));

            // Insert new report settings. Report list contains full report objects.
            $selectedReports = $SESSION->{$sessionKey}[$this->instance->id]->{BLOCK_ZOOLA_REPORTS_REPORTS_KEY};
            array_walk($selectedReports, function($value, $key, $blockinstanceid) {
                unset($value->id);
                $value->blockinstanceid = $blockinstanceid;
            }, $this->instance->id);
            $DB->insert_records('block_zoola_reports', $selectedReports);

            // Insert new user settings. User list contains only user ids.
            $selectedUsers = $SESSION->{$sessionKey}[$this->instance->id]->{BLOCK_ZOOLA_REPORTS_USERS_KEY};
            $insertUsers = array();
            foreach ($selectedUsers as $userid) {
                $insertUsers[] = (object)array(
                    'blockinstanceid' => $this->instance->id,
                    'userid' => $userid
                );
            }
            $DB->insert_records('block_zoola_reports_user', $insertUsers);

            // Insert new cohort settings. Cohort list contains only cohort ids.
            $selectedCohorts = $SESSION->{$sessionKey}[$this->instance->id]->{BLOCK_ZOOLA_REPORTS_COHORTS_KEY};
            $insertCohorts = array();
            foreach ($selectedCohorts as $cohortid) {
                $insertCohorts[] = (object)array(
                    'blockinstanceid' => $this->instance->id,
                    'cohortid' => $cohortid
                );
            }
            $DB->insert_records('block_zoola_reports_cohort', $insertCohorts);

            // End this editing session.
            unset($SESSION->{$sessionKey}[$this->instance->id]);
            \block_zoola\segment_wrapper::track('Zoola Reports Block instance configuration saved', array(
                'blockInstanceId' => $this->instance->id,
                'blockTitle' => $data->title,
                'selectedReports' => implode(',', array_keys($selectedReports)),
                'selectedUsers' => implode(',', $selectedUsers),
                'selectedCohorts' => implode(',', $selectedCohorts)
            ));
        }
        parent::instance_config_save($data, $nolongerused);
    }
}
