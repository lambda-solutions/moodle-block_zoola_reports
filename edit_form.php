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

require_once($CFG->dirroot . '/blocks/zoola/locallib.php');

class block_zoola_reports_edit_form extends block_edit_form {

    /**
     *
     * @global moodle_database $DB
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $CFG, $SESSION, $DB;

        $sessionKey = BLOCK_ZOOLA_REPORTS_SESSION_KEY;
        // States of currently selected reports, users, and cohorts are stored in session.
        if (!isset($SESSION->$sessionKey)) {
            $SESSION->$sessionKey = array();
        }
        if (!array_key_exists($this->block->instance->id, $SESSION->$sessionKey)) {
            // User just started to edit configuration for this block instance.
            // Populate selected lists from database.
            $config_edit_session = new stdClass();
            $config_edit_session->{BLOCK_ZOOLA_REPORTS_REPORTS_KEY} = $DB->get_records('block_zoola_reports',
                    array('blockinstanceid' => $this->block->instance->id), 'label', 'uri as id, uri, type, label');
            // For users, just add ids.
            $config_edit_session->{BLOCK_ZOOLA_REPORTS_USERS_KEY} = array_keys($DB->get_records('block_zoola_reports_user',
                    array('blockinstanceid' => $this->block->instance->id), '', 'userid'));
            $config_edit_session->{BLOCK_ZOOLA_REPORTS_COHORTS_KEY} = array_keys($DB->get_records('block_zoola_reports_cohort',
                    array('blockinstanceid' => $this->block->instance->id), '', 'cohortid'));
            $SESSION->{$sessionKey}[$this->block->instance->id] = $config_edit_session;

            \block_zoola\segment_wrapper::track('Zoola Reports Block instance configuration started', array(
                'blockInstanceId' => $this->block->instance->id
            ));
        }

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $can_edit_reports = has_any_capability(
                array('block/zoola_reports:myaddinstance', 'block/zoola_reports:addinstance'),
                $this->page->context);

        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_zoola_reports'));
        $mform->setDefault('config_title', get_string('pluginname', 'block_zoola_reports'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->addRule('config_title', 'This is mandatory field', 'required');

        if ($CFG->version >= '2015051100') {
            // Moodle 2.9 is required to show inline reports.
            $mform->addElement('advcheckbox', 'config_runreport', get_string('runreport', 'block_zoola_reports'));
            $mform->setType('config_runreport', PARAM_BOOL);

            $mform->addElement('advcheckbox', 'config_fit', get_string('fit', 'block_zoola_reports'));
            $mform->setType('config_fit', PARAM_BOOL);
            $mform->disabledIf('config_fit', 'config_runreport');

            if (!$can_edit_reports) {
                $mform->hardFreeze(array('config_runreport', 'config_fit'));
            }
        }

        MoodleQuickForm::registerElementType(
                'block_zoola_reports_reportselector',
                "$CFG->dirroot/blocks/zoola_reports/classes/block_zoola_reports_reportselector.php",
                'block_zoola_reports_reportselector');

        $mform->addElement(
                'block_zoola_reports_reportselector',
                'reportselector',
                get_string('selectreports', 'block_zoola_reports'),
                array('blockid' => $this->block->instance->id));

        MoodleQuickForm::registerElementType(
                'block_zoola_reports_userselector',
                "$CFG->dirroot/blocks/zoola_reports/classes/block_zoola_reports_userselector.php",
                'block_zoola_reports_userselector');

        $mform->addElement(
                'block_zoola_reports_userselector',
                'userselector',
                get_string('selectusers', 'block_zoola_reports'),
                array('blockid' => $this->block->instance->id));

        MoodleQuickForm::registerElementType(
                'block_zoola_reports_cohortselector',
                "$CFG->dirroot/blocks/zoola_reports/classes/block_zoola_reports_cohortselector.php",
                'block_zoola_reports_cohortselector');

        $mform->addElement(
                'block_zoola_reports_cohortselector',
                'cohortselector',
                get_string('selectcohorts', 'block_zoola_reports', strtolower(get_string('cohorts', 'cohort'))),
                array('blockid' => $this->block->instance->id));

        if (!$can_edit_reports) {
            $mform->hardFreeze(array('config_title', 'reportselector', 'userselector'));
        }

    }

    /**
     * This method is called after definition(), data submission and set_data().
     *
     * Process added and removed users and reports.
     */
    public function definition_after_data() {
        $this->_form->getElement('reportselector')->update_lists();
        $this->_form->getElement('userselector')->update_lists();
        $this->_form->getElement('cohortselector')->update_lists();
        parent::definition_after_data();
    }

    public function is_cancelled() {
        global $SESSION;
        $canceled = parent::is_cancelled();
        if ($canceled) {
            // Remove current selections from session.
            $sessionKey = BLOCK_ZOOLA_REPORTS_SESSION_KEY;
            if (isset($SESSION->$sessionKey)) {
                unset($SESSION->{$sessionKey}[$this->block->instance->id]);
            }
            \block_zoola\segment_wrapper::track('Zoola Reports Block instance configuration cancelled', array(
                'blockInstanceId' => $this->block->instance->id
            ));
        }
        return $canceled;
    }

    public function display() {
        parent::display();
        block_zoola\segment_wrapper::identify();
    }

}
