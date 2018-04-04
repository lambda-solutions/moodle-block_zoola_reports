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

require_once($CFG->libdir . '/formslib.php');

/**
 * Description of report_form
 *
 * @author vukas
 */
class report_form extends \moodleform {
    protected function definition() {
        $mform = $this->_form;

        $buttonarray = array();
        if (!empty($this->_customdata['input_controls'])) {
            $mform->addElement('header', 'inputcontrols', get_string('reportfilters', 'block_zoola_reports'));
            foreach ($this->_customdata['input_controls'] as $ic) {
                switch ($ic->type) {
                    case 'bool':
                        $this->zoola_bool($ic);
                        break;

                    case 'singleValue':
                    case 'singleValueText':
                        $this->zoola_single_value($ic);
                        break;

                    case 'singleValueNumber':
                        $this->zoola_single_value_number($ic);
                        break;

                    case 'singleValueDate':
                    case 'singleValueDatetime':
                    case 'singleValueTime':
                        $this->zoola_single_value_date($ic);
                        break;

                    case 'singleSelect':
                        $this->zoola_single_select($ic);
                        break;

                    case 'multiSelect':
                        $this->zoola_multi_select($ic);
                        break;

                    default:
                        debugging("Input controls of type $ic->type are not supported");
                        break;
                }
            }
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('viewreport', 'block_zoola_reports'));
        }
        $buttonarray[] = $mform->createElement('submit', 'pdf', get_string('export', 'block_zoola_reports', 'PDF'));
        $buttonarray[] = $mform->createElement('submit', 'xlsx', get_string('export', 'block_zoola_reports', 'Excel'));
        $buttonarray[] = $mform->createElement('submit', 'email', get_string('email', 'block_zoola_reports'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->disable_form_change_checker();
    }

    private function zoola_bool($input_control) {
        $name = $input_control->id;
        $label = $input_control->label;
        $this->_form->addElement('checkbox', $name, $label);
    }

    private function zoola_single_value($input_control) {
        $name = $input_control->id;
        $label = $input_control->label;
        $this->_form->addElement('text', $name, $label, array('style' => 'max-width: 400px; width: 100%;'));
        $this->_form->setType($name, PARAM_TEXT);
    }

    private function zoola_single_value_number($input_control) {
        $this->zoola_single_value($input_control);
        $this->_form->addRule($input_control->id, null, 'numeric');
    }

    private function zoola_single_value_date($input_control) {
        $name = $input_control->id;
        $label = $input_control->label;
        $this->_form->addElement('date_selector', $name, $label, array('optional' => true));
    }

    private function zoola_single_select($input_control) {
        $name = $input_control->id;
        $label = $input_control->label;
        $options = array();
        foreach ($input_control->state->options as $option) {
            $options[$option->value] = $option->label;
        }
        $this->_form->addElement('select', $name, $label, $options, array('style' => 'max-width: 400px; width: 100%;'));
    }

    private function zoola_multi_select($input_control) {
        $this->zoola_single_select($input_control);
        $this->_form->getElement($input_control->id)->setMultiple(true);
    }

}
