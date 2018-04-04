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

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . "/blocks/zoola/locallib.php");
require_once($CFG->dirroot . "/blocks/zoola_reports/locallib.php");

class block_zoola_reports_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function email_report_form_parameters() {
        return new external_function_parameters(
            array(
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the email report form, encoded as a json array'),
                'reportparams' => new external_value(PARAM_RAW, 'Report parameters')
            )
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function email_report_form_returns() {
        return new external_value(PARAM_RAW, 'success');
    }

    /**
     * Submit the email report form.
     *
     * @global moodle_database $DB
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @param string $reportparams Report parameters.
     * @return int new group id.
     */
    public static function email_report_form($jsonformdata, $reportparams) {
        global $DB;

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::email_report_form_parameters(), array(
            'jsonformdata' => $jsonformdata,
            'reportparams' => $reportparams
        ));

        $serialiseddata = json_decode($params['jsonformdata']);
        $parameters = json_decode($params['reportparams']);

        $data = array();
        parse_str($serialiseddata, $data);

        $context = context_course::instance($data['courseid']);
        self::validate_context($context);

        if (!confirm_sesskey($data['sesskey'])) {
            throw new moodle_exception('invalidsesskey');
        }

        if (!validate_email($data['to'])) {
            throw new moodle_exception('invalidemail');
        }

        $zoola_token = block_zoola_get_report_token();
        $report_record = $DB->get_record('block_zoola_reports', array('id' => $data['reportid']));
        if (!$report_record) {
            throw new moodle_exception('reportnotfound', 'block_zoola_reports');
        }

        if (!block_zoola_reports_emailreport($report_record, $parameters, (object)$data, $zoola_token)) {
            throw new moodle_exception('emailfailed', 'block_zoola_reports');
        }

        return get_string('emailsuccess', 'block_zoola_reports');
    }

    public static function email_report_form_is_allowed_from_ajax() {
        return true;
    }
}
