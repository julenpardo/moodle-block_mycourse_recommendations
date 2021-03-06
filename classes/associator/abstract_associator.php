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

/**
 * Abstract associator interface definition.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

/**
 * Interface abstract_associator for the definition of method for creating association matrix between users.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

interface abstract_associator {

    /**
     * Given the data of the historic users and the current ones, creates a matrix of association coefficients, with the
     * current users as rows, and the historic user as columns.
     *
     * @param array $currentdata Current users' views.
     * @param array $historicdata Historic users' views.
     * @param \text_progress_trace $trace Text output trace.
     * @return array The association matrix.
     */
    public function create_associations_matrix($currentdata, $historicdata, $trace);
}
