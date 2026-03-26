<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_progress\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;

/**
 * User search output for progress report.
 *
 * @package   report_progress
 * @copyright  2026 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 */
class user_search implements renderable, templatable {
    /** @var moodle_url */
    protected $url;

    /** @var string */
    protected $search;

    /**
     * Constructor.
     *
     * @param moodle_url $url The base URL.
     * @param string $search The current search string.
     */
    public function __construct(moodle_url $url, string $search) {
        $this->url = clone($url);
        $this->search = $search;
    }

    /**
     * Export data for mustache template.
     *
     * @param renderer_base $output The renderer.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $searchurl = clone($this->url);
        $searchurl->remove_params(['page']);

        $clearurl = clone($this->url);
        $clearurl->remove_params(['page', 'search']);

        $hiddenfields = [];
        foreach ($searchurl->params() as $name => $value) {
            if ($name === 'search') {
                continue;
            }

            $hiddenfields[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        return [
            'action' => $searchurl->out(false),
            'search' => $this->search,
            'hiddenfields' => $hiddenfields,
            'hassearch' => $this->search !== '',
            'clearurl' => $clearurl->out(false),
            'searchlabel' => get_string('searchbyemail', 'report_progress'),
            'searchbuttonlabel' => get_string('search'),
            'clearlabel' => get_string('clear'),
            'fieldid' => 'report-progress-search',
        ];
    }
}
