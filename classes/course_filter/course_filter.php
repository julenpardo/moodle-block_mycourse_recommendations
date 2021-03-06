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
 * Course filtering for determination of they "personalizabily".
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');

use block_mycourse_recommendations\database_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course_filter for determining if a course is personalizable or not.
 *
 * @package block_mycourse_recommendations
 */

class course_filter {

    const MINIMUM_WEEKS = 4;
    const MINIMUM_PREVIOUS_COURSES = 1;
    const MINIMUM_PREVIOUS_STUDENTS = 20;
    const MINIMUM_PREVIOUS_RESOURCES = 40; // It seems to be illegal: self::MINIMUM_PREVIOUS_STUDENTS * 2... This must be fixed.

    /**
     * Determines if a course is personalizable or not. The logic to determine this is divided in other
     * functions; this function only calls them. If one of these function says that doesn't meet one of
     * the parameters, there's no need to continue calling other functions.
     *
     * @see meets_minimum_weeks($courseid, $db).
     * @see meets_minimum_previous_courses($courseid, $db).
     * @see meets_minimum_resources($courseid, $db).
     * @see meets_minimum_previous_students($courseid, $db).
     * @see meets_resource_variation($courseid, $db).
     * @param int $courseid The course to determine if it is personalizable or not.
     * @param int $year The year the course teaching began.
     * @return boolean If the given course is personalizable or not.
     */
    public static function is_course_personalizable($courseid, $year) {
        $db = new database_helper();

        $personalizable = true;
        $coursestartyear = $db->get_course_start_week_and_year($courseid)['year'];

        $personalizable &= self::meets_minimum_previous_courses($courseid, $coursestartyear, $db);

        if ($personalizable) {
            $personalizable &= self::meets_minimum_weeks($courseid, $db);
        }

        if ($personalizable) {
            $personalizable &= self::meets_minimum_resources($courseid, $coursestartyear, $db);
        }

        if ($personalizable) {
            $personalizable &= self::meets_minimum_previous_students($courseid, $coursestartyear, $db);
        }

        if ($personalizable) {
            $personalizable &= self::meets_resource_variation($courseid, $db);
        }

        return $personalizable;
    }

    /**
     * Determines if the course duration, in weeks, is long enough to consider it personalizable.
     * This function will be implemented in the near future.
     *
     * @param int $courseid The course to determine if meets the minimum weeks.
     * @param database_helper $db The object with deals with database.
     * @return boolean If the duration of the given course is the needed.
     */
    public static function meets_minimum_weeks($courseid, $db) {
        return true;
    }

    /**
     * Determines if the course has had the minimum previous teachings in the past years.
     * First, looks into Moodle core tables for existing previous courses. If it doesn't find any, it tries luck in
     * plugin's historic table. If it doesn't found neither here, means that the course has not previous teachings.
     *
     * @see find_course_previous_teachings_ids($currentcourseid, $currentyear) in database_helper.php.
     * @param int $courseid The course to determine if has had the minimum teachings before.
     * @param int $currentyear The year the current course began in.
     * @param database_helper $db The object with deals with database.
     * @return boolean If the given course has had the minimum teachings before or not.
     */
    public static function meets_minimum_previous_courses($courseid, $currentyear, $db) {
        $previouscourses = $db->find_course_previous_teaching_ids_core_tables($courseid, $currentyear);

        $minimum = false;

        if (count($previouscourses) >= self::MINIMUM_PREVIOUS_COURSES) {
            $minimum = true;
        }

        if (!$minimum) {
            $previouscourses = $db->find_course_previous_teachings_ids_historic_tables($courseid, $currentyear);

            if (count($previouscourses) >= self::MINIMUM_PREVIOUS_COURSES) {
                $minimum = true;
            }
        }

        return $minimum;
    }

    /**
     * Determines if the course has the minimum number of resources.
     * First, looks into Moodle core tables for existing previous resources. If it doesn't find any, it tries luck in
     * plugin's historic table. If it doesn't found neither here, means that the course has not previous resources.
     *
     * @param int $courseid The course to determine if meets the minimum modules.
     * @param int $currentyear The year of the given current course.
     * @param \block_mycourse_recommendations\database_helper $db The object with deals with database.
     * @return boolean If the given course has the minimum resources or not.
     */
    public static function meets_minimum_resources($courseid, $currentyear, $db) {
        $previousresourcenumber = $db->get_previous_courses_resources_number_core_tables($courseid, $currentyear);

        $minimum = false;

        if ($previousresourcenumber >= self::MINIMUM_PREVIOUS_RESOURCES) {
            $minimum = true;
        }

        if (!$minimum) {
            $previousresourcenumber = $db->get_previous_courses_resources_number_historic_tables($courseid, $currentyear);

            if ($previousresourcenumber >= self::MINIMUM_PREVIOUS_RESOURCES) {
                $minimum = true;
            }
        }

        return $minimum;
    }

    /**
     * Determines if the course has had the minimum number of students in previous teachings.
     * First, looks into Moodle core tables for existing previous students. If it doesn't find any, it tries luck in
     * plugin's historic table. If it doesn't found neither here, means that the course has not previous students.
     *
     * @param int $courseid The course to determine if meets the minimum students.
     * @param int $currentyear The year of the given current course.
     * @param \block_mycourse_recommendations\database_helper $db The object with deals with database.
     * @return boolean If the given course has the minimum students or not.
     */
    public static function meets_minimum_previous_students($courseid, $currentyear, $db) {
        $previousstudents = $db->get_previous_courses_students_number_core_tables($courseid, $currentyear);

        $minimum = false;

        if ($previousstudents >= self::MINIMUM_PREVIOUS_STUDENTS) {
            $minimum = true;
        }

        if (!$minimum) {
            $previousstudents = $db->get_previous_courses_resources_number_historic_tables($courseid, $currentyear);

            if ($previousstudents >= self::MINIMUM_PREVIOUS_STUDENTS) {
                $minimum = true;
            }
        }

        return $minimum;
    }

    /**
     * Determines if the course meets the resource variation.
     * For the moment, we'll suppose every course meets this.
     *
     * @param int $courseid The course to determine if meets the resource variation.
     * @param database_helper $db The object with deals with database.
     * @return boolean If the given course meets the resource variation or not.
     */
    public static function meets_resource_variation($courseid, $db) {
        return true;
    }

}
