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

require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/blocks/zoola_reports/locallib.php');

/**
 * Base class for block_zoola_reports selectors
 *
 * @author vukas
 */
abstract class selector_base extends \user_selector_base {
    /**
     * The id of the block instance
     * @var int
     */
    protected $blockid = null;

    public function __construct($name, $options = array()) {
        parent::__construct($name, $options);
        if (isset($options['blockid'])) {
            $this->blockid = $options['blockid'];
        }
    }

    /**
     * @return array the options needed to recreate this user_selector.
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['blockid'] = $this->blockid;
        return $options;
    }

}
