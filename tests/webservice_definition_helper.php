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
namespace theme_snap;
defined('MOODLE_INTERNAL') || die();

use theme_snap\webservice\definition_helper;
use core_external\external_value;

/**
 * Testable version of definition_helper.
 * Class definition_helper_testable
 */
class definition_helper_testable extends definition_helper {
    /**
     * Magic method for getting protected / private properties.
     * @param string $name
     * @return mixed
     * @throws \core\exception\coding_exception
     */
    public function __get($name) {
        return $this->$name;
    }

    /**
     * Magic method for setting protected / private properties.
     * @param string $name
     * @param mixed $value
     * @return mixed
     * @throws \core\exception\coding_exception
     */
    public function __set($name, $value) {
        $this->$name = $value;
    }

    /**
     * Magic method to allow protected / private methods to be called.
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        $reflection = new \ReflectionObject($this);
        $parentreflection = $reflection->getParentClass();
        $method = $parentreflection->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this, $arguments);
    }
}

/**
 * Simple class for testing.
 * Class wsdocs_teeth
 */
class wsdocs_teeth {
    /**
     * @var string type of teeth
     */
    public $type;

    /**
     * @var string left or right
     */
    public $side;

    /**
     * @var boolean top if true, else bottom
     */
    public $top;
}

class wsdocs_testing {
    /**
     * @var string My head
     */
    public $head;

    /**
     * @var string My shoulders
     * @wsrequired
     */
    public $shoulders;

    /**
     * @var string
     * @wstype PARAM_ALPHA
     * @wsdesc A description of my knees.
     * @wsallownull false
     */
    public $knees;

    /**
     * @var int
     * @wstype PARAM_INT
     * @wsdescription Count of my toes.
     */
    public $toes;

    /**
     * @var string
     * @wstype PARAM_TEXT
     * @wsdesc A description of my ears.
     * @wsrequired true
     */
    public $ears;

    /**
     * @var stdClass
     * @wsparam {
     *     tongue: {
     *         type: PARAM_INT,
     *         description: "Length of tongue"
     *     },
     *     teeth: {
     *         type: wsdocs_teeth[],
     *         description: "Array of teeth"
     *     }
     * };
     */
    public $mouth;
}

class var_nodescription {
    /**
     * @var str
     */
    public $something;
}

class wsparam_notype {
    /**
     * @wsparam {
     *     doohicky: {
     *         description: "An amazing thing."
     *     }
     * };
     */
    public $something;
}

/**
 * Tests for webservice definition healper.
 * @author    Guy Thomas
 * @copyright Copyright (c) 2016 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_definition_helper extends \advanced_testcase {
    public function test_wsdocs() {
        $definitionhelper = new definition_helper('wsdocs_testing');
        $definition = $definitionhelper->get_definition();

        $expecteds = [
            'head' => [
                'instanceof' => 'external_value',
                'type' => PARAM_RAW,
                'desc' => 'My head',
                'required' => false,
                'allownull' => true,
            ],
            'shoulders' => [
                'instanceof' => 'external_value',
                'type' => PARAM_RAW,
                'desc' => 'My shoulders',
                'required' => true,
                'allownull' => true,
            ],
            'knees' => [
                'instanceof' => 'external_value',
                'type' => PARAM_ALPHA,
                'desc' => 'A description of my knees.',
                'required' => false,
                'allownull' => false,
            ],
            'toes' => [
                'instanceof' => 'external_value',
                'type' => PARAM_INT,
                'desc' => 'Count of my toes.',
                'required' => false,
                'allownull' => true,
            ],
            'ears' => [
                'instanceof' => 'external_value',
                'type' => PARAM_TEXT,
                'desc' => 'A description of my ears.',
                'required' => true,
                'allownull' => true,
            ],
        ];

        foreach ($expecteds as $name => $expected) {
            $this->assertTrue(isset($definition[$name]));
            $this->assertTrue($definition[$name] instanceof $expected['instanceof']);
            $this->assertEquals($expected['type'], $definition[$name]->type);
            $this->assertEquals($expected['desc'], $definition[$name]->desc);
            $this->assertEquals($expected['required'], $definition[$name]->required);
            $this->assertEquals($expected['allownull'], $definition[$name]->allownull);
        }

    }

    public function test_convert_ws_param_no_type() {
        $this->expectException('\\core\\exception\\coding_exception');
        $this->expectExceptionMessage('Type not specified');
        new definition_helper_testable(new wsparam_notype());
    }

    public function test_convert_var_no_description() {
        $helper = new definition_helper_testable(new var_nodescription());
        $definition = $helper->get_definition();
        $this->assertArrayHasKey('something', $definition);
        $something = $definition['something'];
        $this->assertTrue($something instanceof external_value);
        $this->assertEmpty($something->desc);
    }

    public function test_cache_definition() {
        $classname = 'wsdocs_testing';
        $helper = new definition_helper_testable($classname);
        $definition = $helper->get_definition();

        // Wipe cache so we can test nothing in cache.
        $cache = \cache::make('theme_snap', 'webservicedefinitions');
        $data = $cache->delete($classname);

        // Test empty cache.
        $cached = $helper->get_definition_from_cache($classname);
        $this->assertFalse($cached);

        // Test recover from cache.
        $helper->cache_definition($classname, $definition);
        $cached = $helper->get_definition_from_cache($classname);
        $this->assertNotEmpty($cached);
    }

}
