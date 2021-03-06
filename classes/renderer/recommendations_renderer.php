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
 * Rendering recommendations into HTML format, concretely, in an ordered list.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');

use block_mycourse_recommendations\database_helper;

/**
 * Class recommendations_renderer for rendering recommendations for user in HTML format, concretely, in an ordered list.
 *
 * @package block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class recommendations_renderer {

    const MAX_RECOMMENDATIONS = 3;

    /**
     * Generates the HTML for the given recommendations. The maximum number of recommendations is defined by
     * self::MAX_RECOMMENDATIONS constant.
     *
     * @param array $recommendations Recommendations of the week and course.
     * @return string The formatted HTML for the recommendations; or string indicating that there are no recommendations,
     * if $recommendations is empty.
     */
    public static function render_recommendations($recommendations) {
        global $COURSE;

        if (empty($recommendations)) {
            return get_string('norecommendations', 'block_mycourse_recommendations');
        }

        $modinfo = get_fast_modinfo($COURSE->id);
        $recommendations = array_values($recommendations);
        $output = '<ol>';

        for ($index = 0; $index < self::MAX_RECOMMENDATIONS && $index < count($recommendations); $index++) {
            $recommendation = $recommendations[$index];

            $moduleid = self::get_resource_moduleid($modinfo->instances, $recommendation->resourceid, $COURSE->id);

            $cminfo = $modinfo->get_cm($moduleid);

            $url = new \moodle_url('/blocks/mycourse_recommendations/redirect_to_resource.php',
                                   array('recommendationid' => $recommendation->id, 'modname' => $cminfo->modname,
                                       'moduleid' => $moduleid));

            $output .= '<li>';
            $output .= "<a href='$url' target='_blank'>";
            $output .= $cminfo->get_formatted_name();
            $output .= '</a>';
            $output .= '</li>';
        }

        $output .= '</ol>';

        return $output;
    }

    /**
     * Finds the module id from a resource instance, which is needed for the "get_cm($moduleid)" function, to get module's name.
     * For that, uses the courses instances information, retrieved using "get_fast_modinfo($courseid)" function.
     *
     * @param array $instances Key is resource type name, and value is an array of each instance of the course of that type.
     * @param int $resourceid The resource we are finding the moduleid of.
     * @param int $courseid The course the module belongs to.
     * @return int Module id.
     */
    public static function get_resource_moduleid($instances, $resourceid, $courseid) {
        $db = new database_helper();

        foreach ($instances as $instancetype => $typeinstances) {
            $typeinstancesids = array_keys($typeinstances);

            if (in_array($resourceid, $typeinstancesids)) {
                $typeid = $db->get_module_type_id_by_name($instancetype);
                $moduleid = $db->get_module_id($courseid, $resourceid, $typeid);

                break;
            }
        }

        return $moduleid;
    }
}
