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

require_once('HTML/QuickForm/input.php');
require_once(dirname(__DIR__) . "/locallib.php");

/**
 * Base class for block_zoola_reports_selector_* classes
 *
 * @author vukas
 */
abstract class block_zoola_reports_selector_input extends HTML_QuickForm_input {

    /**
     *
     * @var block_zoola_reports\selector_base
     */
    protected $available_selector;

    /**
     *
     * @var block_zoola_reports\selector_base
     */
    protected $selected_selector;

    protected $itemsKey = '';

    protected $add_button;
    protected $remove_button;
    protected $strHtml;
    protected $blockinstanceid;

    public function __construct($elementName = null, $elementLabel = null, $options = null, $attributes = null) {
        parent::__construct($elementName, $elementLabel, $attributes);
        if ($elementName) {
            $this->add_button = $elementName . "_add";
            $this->remove_button = $elementName . "_remove";
            if (isset($options['blockid'])) {
                $this->blockinstanceid = $options['blockid'];
            }
        }
        $this->initialize_selector($elementName, $options);
    }

    protected abstract function initialize_selector($elementName = null, $options = null);

    /**
     * Returns the input field in HTML
     *
     * @access    public
     * @return    string
     */
    public function toHtml() {
        global $OUTPUT;
        $output = '';

        $output .= html_writer::start_div('block_zoola_reports');

        if ($this->_flagFrozen) {
            $output .= $this->getFrozenHtml();
        } else {
            $existingcell = new html_table_cell();
            $existingcell->text = $this->selected_selector->display(true);
            $existingcell->attributes['class'] = 'existing';
            $actioncell = new html_table_cell();
            $actioncell->text = html_writer::start_tag('p', array('class' => 'arrow_button'));
            $actioncell->text .= html_writer::empty_tag('input', array(
                        'type' => 'submit',
                        'name' => $this->getName() . '_add',
                        'value' => $OUTPUT->larrow() . ' ' . get_string('add'),
                        'class' => 'actionbutton',
                        'disabled' => ($this->_flagFrozen ? 'disabled' : null))
            );
            $actioncell->text .= html_writer::empty_tag('br');
            $actioncell->text .= html_writer::empty_tag('input', array(
                        'type' => 'submit',
                        'name' => $this->getName() . '_remove',
                        'value' => get_string('remove') . ' ' . $OUTPUT->rarrow(),
                        'class' => 'actionbutton',
                        'disabled' => ($this->_flagFrozen ? 'disabled' : null))
            );
            $actioncell->text .= html_writer::end_tag('p', array());
            $actioncell->attributes['class'] = 'actions';
            $potentialcell = new html_table_cell();
            $potentialcell->text = $this->available_selector->display(true);
            $potentialcell->attributes['class'] = 'potential';

            $table = new html_table();
            $table->attributes['class'] = 'generaltable boxaligncenter';
            $table->data = array(new html_table_row(array($existingcell, $actioncell, $potentialcell)));
            $output .= html_writer::table($table);
        }
        $output .= html_writer::end_div();

        $this->strHtml = $output;
        return $this->strHtml;
    }

    public function getFrozenHtml() {
        $output = '';
        $groups = $this->selected_selector->find_users('');
        foreach ($groups as $group => $items) {
            $output .= "<p>$group</p>";
            $output .= html_writer::alist(array_map(array($this->selected_selector, 'output_user'), $items));
        }
        return $output;
    }

    public function onQuickFormEvent($event, $arg, &$caller) {
        $return = parent::onQuickFormEvent($event, $arg, $caller);
        if ($event == 'createElement') {
            if (!empty($this->add_button) && !empty($this->remove_button)) {
                $caller->registerNoSubmitButton($this->add_button);
                $caller->registerNoSubmitButton($this->remove_button);
            }
        }
        return $return;
    }

    protected abstract function remove_from_list(array $elements);

    protected abstract function add_to_list(array $elements);

    public function update_lists() {
        $add_users = optional_param($this->add_button, false, PARAM_TEXT);
        $remove_users = optional_param($this->remove_button, false, PARAM_TEXT);
        if ($add_users) {
            $to_add = $this->available_selector->get_selected_users();
            if (!empty($to_add)) {
                $this->add_to_list($to_add);
            }
            $this->available_selector->invalidate_selected_users();
            $this->selected_selector->invalidate_selected_users();
        }
        if ($remove_users) {
            $to_remove = $this->selected_selector->get_selected_users();
            if (!empty($to_remove)) {
                $this->remove_from_list($to_remove);
            }
            $this->available_selector->invalidate_selected_users();
            $this->selected_selector->invalidate_selected_users();
        }
    }

}
