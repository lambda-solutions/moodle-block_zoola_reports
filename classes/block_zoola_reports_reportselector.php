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

require_once(__DIR__ . "/block_zoola_reports_selector_input.php");

/**
 * Description of block_zoola_reports_reportselector
 *
 * @author vukas
 */
class block_zoola_reports_reportselector extends block_zoola_reports_selector_input {

    public function __construct($elementName = null, $elementLabel = null, $options = null, $attributes = null) {
        parent::__construct($elementName, $elementLabel, $options, $attributes);
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     */
    public function block_zoola_reports_reportselector($elementName = null, $elementLabel = null, $options = null, $attributes = null) {
        self::__construct($elementName, $elementLabel, $options, $attributes);
    }

    protected function initialize_selector($elementName = null, $options = null) {
        $this->_type = 'block_zoola_reports_reportselector';
        if ($elementName) {
            $this->available_selector = new block_zoola_reports\potential_reports($elementName . "_available", $options);
            $this->selected_selector = new block_zoola_reports\selected_reports($elementName . "_selected", $options);
        }
        $this->itemsKey = BLOCK_ZOOLA_REPORTS_REPORTS_KEY;
    }

    protected function remove_from_list(array $elements) {
        global $SESSION;
        $sessionKey = BLOCK_ZOOLA_REPORTS_SESSION_KEY;
        $newelements = array_diff_key($SESSION->{$sessionKey}[$this->blockinstanceid]->{$this->itemsKey}, $elements);
        $SESSION->{$sessionKey}[$this->blockinstanceid]->{$this->itemsKey} = $newelements;
    }

    protected function add_to_list(array $elements) {
        global $SESSION;
        $sessionKey = BLOCK_ZOOLA_REPORTS_SESSION_KEY;
        $newelements = array_merge($SESSION->{$sessionKey}[$this->blockinstanceid]->{$this->itemsKey}, $elements);
        $SESSION->{$sessionKey}[$this->blockinstanceid]->{$this->itemsKey} = $newelements;
    }

}
