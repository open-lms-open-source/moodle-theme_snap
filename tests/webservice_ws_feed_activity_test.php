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

defined('MOODLE_INTERNAL') || die();

use theme_snap\webservice\ws_feed;

/**
 * Test ws_feed web service
 * @author    Oscar Nadjar <oscar.nadjar@blackboard.com>
 * @copyright Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_ws_feed_test extends \advanced_testcase {

    public function test_service_parameters() {
        $params = ws_feed::service_parameters();
        $this->assertTrue($params instanceof external_function_parameters);
    }

    public function test_service_returns() {
        $returns = ws_feed::service_returns();
        $this->assertTrue($returns instanceof external_multiple_structure);
    }

    public function test_service_message() {
        $this->resetAfterTest();

        $userfrom = $this->getDataGenerator()->create_user();
        $userto = $this->getDataGenerator()->create_user();
        $this->setUser($userto);

        $message = 'Message';
        for ($messagen = 1; $messagen <= 4; $messagen++) {
            $this->create_message([$userfrom, $userto], \core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
                $message . $messagen, $messagen);
        }
        $serviceresult = ws_feed::service('messages');
        $this->assertTrue(is_array($serviceresult));
        $this->assertCount(3, $serviceresult);
        $this->assertEquals($serviceresult[0]['subTitle'], 'Message4');
        $itemid = $serviceresult[0]['itemId'];

        $this->create_message([$userfrom, $userto], \core_message\api::MESSAGE_CONVERSATION_TYPE_INDIVIDUAL,
            $message . $messagen, $messagen);

        $serviceresult = ws_feed::service('messages', 1, 3, $itemid);
        $this->assertCount(1, $serviceresult);
        $this->assertEquals($serviceresult[0]['subTitle'], 'Message1');
        $itemid = $serviceresult[0]['itemId'];

        $serviceresult = ws_feed::service('messages', 1, 3, $itemid);
        $this->assertEmpty($serviceresult);

        $serviceresult = ws_feed::service('messages');
        $this->assertCount(3, $serviceresult);
        $this->assertEquals($serviceresult[0]['subTitle'], 'Message5');
    }

    public function create_message(array $users, $messagetype, $message, $time, $subject = 'No subject') {
        global $DB;

        $userids = [];
        foreach ($users as $user) {
            $userids[] = $user->id;
        }
        $conversation = \core_message\api::create_conversation(
            $messagetype,
            $userids);

        // Ok, send the message.
        $record = new stdClass();
        $record->useridfrom = $users[0]->id;
        $record->conversationid = $conversation->id;
        $record->subject = $subject;
        $record->fullmessage = $message;
        $record->smallmessage = $message;
        $record->timecreated = time() + $time;
        $DB->insert_record('messages', $record);
    }
}
