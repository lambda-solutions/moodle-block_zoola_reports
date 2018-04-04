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

require_once($CFG->libdir . '/filelib.php');

/**
 * Description of report_engine
 *
 * @author vukas
 */
class report_engine {

    /**
     *
     * @var string
     */
    protected $cookie;

    /**
     *
     * @var string Zoola token
     */
    protected $token;

    /**
     *
     * @var string Zoola URL
     */
    protected $zoola_url;

    /**
     *
     * @var array Report input controls
     */
    protected $input_controls = array();

    /**
     *
     * @param string $token Zoola token
     */
    public function __construct($token) {
        $this->zoola_url = rtrim(get_config('block_zoola', 'backendurl'), '/') . '/rest_v2';
        $this->token = $token;
    }

    /**
     *
     * @return boolean Was connection successful
     */
    public function login() {
        if (!empty($this->cookie)) {
            return true;
        }

        $url = rtrim(get_config('block_zoola', 'backendurl'), '/') . '/';
        $params = array('pp' => $this->token, 'userTimezone' => get_user_timezone());
        $options = array('CURLOPT_FOLLOWLOCATION' => false);

        $curl = new \curl();
        $curl->setHeader('Accept: application/json');
        $curl->get($url, $params, $options);
        $response = $curl->getResponse();
        $loginsuccessful = false;
        if (isset($response['Location']) && isset($response['Set-Cookie'])) {
            foreach ($response['Set-Cookie'] as $cookie) {
                if (strpos(strtoupper($cookie), 'JSESSIONID=') === 0) {
                    $this->cookie = $cookie;
                    $loginsuccessful = true;
                    break;
                }
            }
        }
        if (!$loginsuccessful) {
            debugging("Login unsuccessful. Check that your Zoola API Key is set correctly.");
        }
        return $loginsuccessful;
    }

    public function input_controls($report_uri) {
        if ($this->login()) {
            $curl = new \curl(array('debug' => false));

            $curl->setHeader("Accept: application/json");
            $params = array();
            $options = array('CURLOPT_COOKIE' => $this->cookie);
            $result = $curl->get("$this->zoola_url/reports{$report_uri}/inputControls/", $params, $options);

            $json = json_decode($result);
            if (isset($json->inputControl)) {
                foreach ($json->inputControl as $ic) {
                    $this->input_controls[$ic->id] = $ic;
                }
            }
        }
        return $this->input_controls;
    }

    private function prepare_filters($filters) {
        $inputs = array();
        foreach ($filters as $param => $value) {
            if (empty($value)) {
                continue;
            }
            if (array_key_exists($param, $this->input_controls)) {
                $ic = $this->input_controls[$param];
                $input = new \stdClass();
                $input->name = $param;
                switch ($ic->type) {
                    case 'bool':
                        $input->value = array("true");
                        break;
                    case 'singleValueDate':
                        $input->value = is_long($value) ? array(date("Y-m-d", $value)) : $value;
                        break;
                    case 'singleValueDatetime':
                        $input->value = is_long($value) ? array(date("Y-m-d\TH:i:s", $value)) : $value;
                        break;
                    case 'singleValueTime':
                        $input->value = is_long($value) ? array(date("H:i:s", $value)) : $value;
                        break;
                    default:
                        $input->value = (array) $value;
                }
                $inputs[] = $input;
            }
        }
        return $inputs;
    }

    protected function fix_images($report, $requestId, $export, $curl) {
        $result = $report;
        if (isset($export->attachments) && is_array($export->attachments)) {
            foreach ($export->attachments as $attachment) {
                $url = "$this->zoola_url/reportExecutions/$requestId/exports/$export->id/attachments/$attachment->fileName";
                $file = $curl->get($url);
                $result = str_replace(
                        "$requestId|$export->id|$attachment->fileName",
                        "data:$attachment->contentType;base64," . base64_encode($file),
                        $result);
            }
        }
        return $result;
    }

    /**
     *
     * @param string $report_uri
     * @param int $page
     * @param object $filters
     * @param string $format Specifies the desired output format: pdf, html, xls, xlsx, rtf, csv, xml, docx, odt, ods, jrprint.
     */
    public function run_report($report_uri, $page = 1, $filters = null, $format = 'html') {
        $report_result = array();
        if ($this->login()) {
            $curl = new \curl(array('debug' => false));

            $params = array();
            $params['reportUnitUri'] = $report_uri;
            $params['outputFormat'] = $format;
            $params['async'] = false;
            $params['interactive'] = false;
            $params['allowInlineScripts'] = true;
            $params['baseUrl'] = rtrim(get_config('block_zoola', 'backendurl'), '/');
            $params['attachmentsPrefix'] = '{reportExecutionId}|{exportExecutionId}|';
            if ($format == 'html') {
                // Pagination is used only for html format.
                $params['pages'] = $page;
            } else if ($format == 'xlsx') {
                // Do not paginate when exporting to xlsx.
                $params['ignorePagination'] = true;
            }

            if (isset($filters)) {
                unset($filters->reporturi);
                unset($filters->submitbutton);
                $params['parameters'] = array("reportParameter" => $this->prepare_filters($filters));
            }

            $curl->setHeader("Content-Type: application/json");
            $curl->setHeader("Accept: application/json");
            $options = array('CURLOPT_COOKIE' => $this->cookie);
            $result = $curl->post("$this->zoola_url/reportExecutions", json_encode($params), $options);

            $execution = json_decode($result);
            if (isset($execution->status) && $execution->status == 'ready') {
                $report_result['status'] = $execution->status;
                $report_result['totalpages'] = $execution->totalPages;
                foreach ($execution->exports as $export) {
                    if ($export->status === 'ready') {
                        $report = $curl->get("$this->zoola_url/reportExecutions/$execution->requestId/exports/$export->id/outputResource");
                        $report_result['content'] = $this->fix_images($report, $execution->requestId, $export, $curl);
                    }
                }
            } else {
                $report_result = (array) $execution;
            }
        }
        return $report_result;
    }

    /**
     *
     * @param string $search
     * @return array Array of reports and report options having the specified text in the name
     */
    public function list_reports($search) {
        $reports = array();
        if ($this->login()) {
            $curl = new \curl(array('debug' => false));
            $curl->setHeader("Content-Type: application/json");
            $curl->setHeader("Accept: application/json");
            $options = array('CURLOPT_COOKIE' => $this->cookie);
            $query = empty($search) ? '' : "&q=$search";
            $result = $curl->get("$this->zoola_url/resources?type=reportUnit&type=reportOptions&type=dashboard&limit=0$query", null, $options);
            $resourceLookup = json_decode($result);
            if (isset($resourceLookup->resourceLookup)) {
                foreach ($resourceLookup->resourceLookup as $report) {
                    // REST API returns resources having the specified text in the name or description.
                    // We need only those having the specified text in the name.
                    if (!empty($search) && stripos($report->label, $search) === false) {
                        continue;
                    }
                    // Skip temp folder.
                    if (substr($report->uri, 0, 6) !== '/temp/') {
                        $reports[$report->uri] = $report;
                    }
                }
            }
        }
        return $reports;
    }

    public function get_resources() {
        $resources = array();
        if ($this->login()) {
            $curl = new \curl(array('debug' => false));
            $curl->setHeader("Content-Type: application/json");
            $curl->setHeader("Accept: application/json");
            $options = array('CURLOPT_COOKIE' => $this->cookie);
            $result = $curl->get("$this->zoola_url/resources?type=reportUnit&type=reportOptions&type=folder&limit=0", null, $options);
            $resourceLookup = json_decode($result);
            if (isset($resourceLookup->resourceLookup)) {
                $resources = $resourceLookup->resourceLookup;
            }
        }
        return $resources;
    }

    public function resource_details($uri) {
        $return = null;
        if ($this->login()) {
            $curl = new \curl(array('debug' => false));
            $curl->setHeader("Accept: application/json");
            $options = array('CURLOPT_COOKIE' => $this->cookie);
            $result = $curl->get("$this->zoola_url/resources$uri", null, $options);
            $response = $curl->getResponse();
            $return = json_decode($result);
            if (isset($response['Content-Type']) && strpos($response['Content-Type'], 'application/repository') === 0) {
                // Extract resource type from HTTP response.
                $return->resourceType = str_replace(
                        array('application/repository.', '+json'),
                        array('', ''),
                        $response['Content-Type']);
            }
        }
        return $return;
    }

    public function get_organization_name() {
        $return = null;
        if ($this->login()) {
            $curl = new \curl(array('debug' => false));

            $curl->setHeader("Content-Type: application/json");
            $curl->setHeader("Accept: application/json");
            $options = array('CURLOPT_COOKIE' => $this->cookie);
            $result = $curl->get("$this->zoola_url/organizations/" . get_config('block_zoola', 'organization'), null, $options);
            $organization = json_decode($result);
            $return = $organization->tenantName;
        }
        return $return;
    }

    public function report_options($report_uri) {
        $report_options = array();
        if ($this->login()) {
            $curl = new \curl(array('debug' => false));

            $parameters = array();

            $curl->setHeader("Content-Type: application/json");
            $curl->setHeader("Accept: application/json");
            $options = array('CURLOPT_COOKIE' => $this->cookie);

            $result = $curl->get("{$this->zoola_url}/reports{$report_uri}/options", $parameters, $options);
            $resources = json_decode($result);
            if (isset($resources->reportOptionsSummary)) {
                foreach ($resources->reportOptionsSummary as $option) {
                    $report_options["$option->uri:reportOption:$option->label"] = $option->label;
                }
            }

        }
        return $report_options;
    }
}
