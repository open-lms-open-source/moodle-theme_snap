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
 * Test TR lang pack.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2020 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class theme_snap_tr_lang_pack_test extends advanced_testcase  {
    public function test_tr_lang_pack_correct() {
        global $CFG;

        $strfile = file_get_contents($CFG->dirroot . '/theme/snap/cli/trstrings.json');
        $stringsarr = json_decode($strfile, true);

        // Array is contained in "Strings" attribute.
        $stringsarr = $stringsarr['Strings'];

        $string = [];
        $discrepancies = 0;
        $langfilelocation = $CFG->dirroot . '/theme/snap/lang/tr/theme_snap.php';
        require_once($langfilelocation);

        foreach ($stringsarr as $stringitem) {
            $stringid = $stringitem['Stringid'];
            $stringlocal = $stringitem['Local'];

            if ($string[$stringid] !== $stringlocal) {
                $discrepancies++;
            }
        }

        $message = 'There are discrepancies on the use of the tr language. ';
        $message .= 'Make sure you run theme/snap/cli/fix_tr_lang_strings.php to fix them.';
        $this->assertEmpty($discrepancies, $message);
    }
}
