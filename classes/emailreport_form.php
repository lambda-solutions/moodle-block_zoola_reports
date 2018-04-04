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
 * Description of emailreport_form
 *
 * @author vukas
 */
class emailreport_form extends \moodleform {

    protected function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        $radiofrom = array();
        $radiofrom[] = $mform->createElement('radio', 'from', '', "{$USER->firstname} {$USER->lastname} &lt;{$USER->email}&gt;", 'me');
        $radiofrom[] = $mform->createElement('radio', 'from', '', $CFG->noreplyaddress, 'noreply');
        $mform->addGroup($radiofrom, 'radiofrom', get_string('from'), '<br>', false);
        $mform->setDefault('from', 'me');

        $mform->addElement('text', 'to', get_string('to'));
        $mform->setType('to', PARAM_EMAIL);
        $mform->addRule('to', get_string('missingemail'), 'required', null, 'client');

        $mform->addElement('text', 'subject', get_string('subject', 'block_zoola_reports'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', 'Subject is missing', 'required', null, 'client');
        $mform->setDefault('subject', 'Zoola Analytics - ' . $this->_customdata['label']);

        $mform->addElement('editor', 'message', get_string('message', 'block_zoola_reports'));
        $mform->setType('message', PARAM_RAW);

        $radioformat = array();
        $radioformat[] = $mform->createElement('radio', 'format', '', 'PDF', 'pdf');
        $radioformat[] = $mform->createElement('radio', 'format', '', 'Excel', 'xlsx');
        $mform->addGroup($radioformat, 'radioformat', get_string('attachformat', 'block_zoola_reports'), ' ', false);
        $mform->setDefault('format', 'pdf');

        $this->add_action_buttons(true, 'Send');
    }

}
