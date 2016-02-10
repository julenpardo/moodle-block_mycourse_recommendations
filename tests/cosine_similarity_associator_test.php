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
 * Unit tests for cosine similarity associations of mycourse_recommendations block.
 *
 * @package    block_mycourse_recommendations
 * @category   phpunit
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/associator/cosine_similarity_associator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/decimal_matrix.php');

use block_mycourse_recommendations\cosine_similarity_associator;
use block_mycourse_recommendations\decimal_matrix;

/**
 * Test cases for block_mycourse_recommendations for cosine similarty associations.
 */
class block_mycourse_recommendations_cosine_similarity_associator_testcase extends advanced_testcase {

    protected $associator;
    protected $matrix;

    /**
     * Set up the test environment.
     */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();

        $this->matrix = new decimal_matrix();
        $this->associator = new cosine_similarity_associator($this->matrix);
    }

    protected function tearDown() {
        $this->databasehelper = null;
        $this->course = null;
        $this->users = null;
        $this->resource = null;
        parent::tearDown();
    }

    protected static function get_method($name) {
        $class = new \ReflectionClass('cosine_similarity_associator');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function test_dot_product() {
        $dotproduct = self::get_method('dot_product');
        $vector1 = array();
        $vector2 = array();
        $expected = 10806;

        $vector1[0] = 1;
        $vector1[1] = 5;
        $vector1[2] = 67;
        $vector1[3] = 14;
        $vector2[0] = 7;
        $vector2[1] = 71;
        $vector2[2] = 154;
        $vector2[3] = 9;

        $output = $dotproduct->invokeArgs($this->associator, array($vector1, $vector2));

        $this->assertEquals($output, $expected);
    }

    public function test_vector_module() {
        $vectormodule = self::get_method('vector_module');
        $vector = array();
        $expected = 68.6367;
        $delta = 0.0001;

        $vector[0] = 1;
        $vector[1] = 5;
        $vector[2] = 67;
        $vector[3] = 14;

        $output = $this->associator->vector_module($vector);
        $output = $vectormodule->invokeArgs($this->associator, array($vector));

        $this->assertEquals($output, $expected, '', $delta);
    }

    public function test_cosine_similarity() {
        $cosinesimilarity = self::get_method('cosine_similarity');
        $vector1 = array();
        $vector2 = array();
        $expected = 0.9263;
        $delta = 0.0001;

        $vector1[0] = 1;
        $vector1[1] = 5;
        $vector1[2] = 67;
        $vector1[3] = 14;
        $vector2[0] = 7;
        $vector2[1] = 71;
        $vector2[2] = 154;
        $vector2[3] = 9;

        $output = $this->associator->cosine_similarity($vector1, $vector2);
        $output = $cosinesimilarity->invokeArgs($this->associator, array($vector1, $vector2));

        $this->assertEquals($output, $expected, '', $delta);
    }

}
