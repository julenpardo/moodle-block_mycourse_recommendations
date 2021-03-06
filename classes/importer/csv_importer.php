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
 * Importation of data from CSV.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/csvlib.class.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');

use block_mycourse_recommendations\database_helper;

/**
 * The class that imports data from a CSV file to the historic tables.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_importer {

    /**
     * The count of inserted last courses.
     * @var int
     */
    private static $lastinsertedcourses = 0;

    /**
     * The count of last inserted users.
     * @var int
     */
    private static $lastinsertedusers = 0;

    /**
     * The count of last inserted logs.
     * @var int
     */
    private static $lastinsertedlogs = 0;

    /**
     * Receiving the three required CSVs, performs the importation of the data of a course: course info, enrolled users,
     * and log info. The data is imported to a total of three different tables. All operations are done in a unique
     * transaction: if something fails (a CSV does not respect the format, an importing course exists, etc.), nothing
     * will be saved.
     *
     * After the importation, associates the just created historic course with the current course, and sets this course as
     * active and personalizable, just in case it was marked as inactive and/or not personalizable.
     *
     * @param object $formdata The data submited in form.
     * @param object $coursefile The CSV file with the information about the course.
     * @param object $usersfile The CSV file with the information about the users enrolled in courses.
     * @param object $logsfile The CSV file with the information about the log views of the users.
     * @param int $currentcourseid The course id of the course for which the csv is being imported.
     * @throws \Exception If something bad happened when trying to insert the data. The exception is thrown after doing the
     * rollback.
     */
    public static function import_data($formdata, $coursefile, $usersfile, $logsfile, $currentcourseid) {
        $db = new database_helper();

        self::$lastinsertedcourses = 0;
        self::$lastinsertedusers = 0;
        self::$lastinsertedusers = 0;

        $transaction = $db->start_transaction();

        try {
            $generatedcourseid = self::import_course($coursefile, $formdata, $db);
            self::import_users($usersfile, $formdata, $generatedcourseid, $db);
            self::import_logs($logsfile, $formdata, $generatedcourseid, $db);

            $db->associate_current_course_with_historic($currentcourseid, $generatedcourseid);
            $db->set_course_active($currentcourseid);
            $db->set_course_personalizable($currentcourseid);

            $db->commit_transaction($transaction);
        } catch (\Exception $e) {
            $db->rollback_transaction($transaction, $e);
            throw $e;
        }
    }

    /**
     * Imports the course defined in the csv file, and then returns the generated identifier for it. This is made under the
     * transaction initiated in import_data function.
     *
     * @param object $coursefile Course csv file.
     * @param object $formdata Submitted form data, needed to load the csv.
     * @param \block_mycourse_recommendations\database_helper $db Database handler object, passed as argument to instance it
     * again.
     * @return int Course id generated.
     */
    public static function import_course($coursefile, $formdata, $db) {

        $iid = \csv_import_reader::get_new_iid('coursefile');
        $csvreader = new \csv_import_reader($iid, 'coursefile');

        $csvreader->load_csv_content($coursefile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();

        while ($fields) {
            $fullname = $fields[0];
            $shortname = $fields[1];
            $startdate = $fields[2];
            $idnumber = $fields[3];
            $category = $fields[4];

            $courseid = $db->insert_historic_course($fullname, $shortname, $startdate, $idnumber, $category);
            self::$lastinsertedcourses++;

            $fields = $csvreader->next();
        }

        $csvreader->close();

        return $courseid;
    }

    /**
     * Imports the users defined in the csv file, iterating each row. This is made under the transaction initiated in import_data
     * function.
     *
     * @param object $usersfile Course csv file.
     * @param object $formdata Submitted form data, needed to load the csv.
     * @param int $courseid Generated course id in this transaction.
     * @param \block_mycourse_recommendations\database_helper $db Database handler object, passed as argument to instance it
     * again.
     */
    public static function import_users($usersfile, $formdata, $courseid, $db) {
        $iid = \csv_import_reader::get_new_iid('usersfile');
        $csvreader = new \csv_import_reader($iid, 'usersfile');

        $csvreader->load_csv_content($usersfile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();

        while ($fields) {
            $userid = $fields[0];
            $grade = $fields[1];

            $db->insert_historic_user_enrol($userid, $grade, $courseid);
            self::$lastinsertedusers++;

            $fields = $csvreader->next();
        }

        $csvreader->close();
    }

    /**
     * Imports the log views defined in the csv file, iterating each row. This is made under the transaction initiated in
     * import_data function.
     *
     * @param object $logsfile Course csv file.
     * @param object $formdata Submitted form data, needed to load the csv.
     * @param int $courseid Generated course id in this transaction.
     * @param \block_mycourse_recommendations\database_helper $db Database handler object, passed as argument to instance it
     * again.
     */
    public static function import_logs($logsfile, $formdata, $courseid, $db) {
        $iid = \csv_import_reader::get_new_iid('logsfile');
        $csvreader = new \csv_import_reader($iid, 'logsfile');

        $csvreader->load_csv_content($logsfile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();

        while ($fields) {
            $userid = $fields[0];
            $resourcename = $fields[1];
            $resourcetype = $fields[2];
            $resourceid = $fields[3];
            $views = $fields[4];
            $timecreated = $fields[5];

            $db->insert_historic_logs($userid, $courseid, $resourcename, $resourcetype, $resourceid, $views, $timecreated);
            self::$lastinsertedlogs++;

            $fields = $csvreader->next();
        }

        $csvreader->close();
    }

    /**
     * Returns the number of last inserted courses.
     *
     * @return int Number of last inserted courses.
     */
    public static function get_lastinsertedcourses() {
        return self::$lastinsertedcourses;
    }

    /**
     * Returns the number of last inserted users.
     *
     * @return int Number of last inserted users.
     */
    public static function get_lastinsertedusers() {
        return self::$lastinsertedusers;
    }

    /**
     * Returns the number of last inserted logs.
     *
     * @return int Number of last inserted logs.
     */
    public static function get_lastinsertedlogs() {
        return self::$lastinsertedlogs;
    }
}
