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
 * Kernel Tests
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\tests\controller;

use theme_snap\controller\kernel;
use theme_snap\controller\router;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kernel_test extends \basic_testcase {

    public function _return_string_callback() {
        return 'return phpunit';
    }

    public function _echo_string_callback() {
        echo 'echo phpunit';
    }

    public function _both_string_callback() {
        echo 'echo phpunit';
        return 'return phpunit';
    }

    public function test_resolve_controller_callback() {
        $controller = $this->getMock('\theme_snap\controller\controller_abstract', array(
            'init',
            'test_action',
            'require_capability',
        ));

        $router = $this->getMock('\theme_snap\controller\router', array('route_action'));
        $router->expects($this->once())
            ->method('route_action')
            ->will($this->returnValue(array($controller, 'test_action')));

        $kernel = new kernel($router);

        $controller->expects($this->once())
            ->method('init')
            ->with('test');

        list($routedcontroller, $method) = $kernel->resolve_controller_callback('test');

        $this->assertSame($controller, $routedcontroller);
        $this->assertEquals('test_action', $method);
    }

    public function test_execute_callback_with_return() {
        $this->expectOutputString('return phpunit');
        $kernel = new kernel(new router());
        $kernel->execute_callback(array($this, '_return_string_callback'));
    }

    public function test_execute_callback_with_echo() {
        $this->expectOutputString('echo phpunit');
        $kernel = new kernel(new router());
        $kernel->execute_callback(array($this, '_echo_string_callback'));
    }

    /**
     * @expectedException \coding_exception
     */
    public function test_execute_callback_with_both() {
        $kernel = new kernel(new router());
        $kernel->execute_callback(array($this, '_both_string_callback'));
    }
}
