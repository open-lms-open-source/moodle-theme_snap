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
 * Overrides for behat forms. Modified from core behat_forms.
 *
 * @copyright  2012 David Monllaó
 * @copyright Copyright (c) 2018 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

require_once(__DIR__ . '/../../../../lib/tests/behat/behat_forms.php');

/**
 * Overrides to make behat forms steps work with Snap.
 *
 * @copyright  2012 David Monllaó
 * @copyright Copyright (c) 2018 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_snap_behat_forms extends behat_forms {

    /**
     * Expands all moodle form fieldsets if they exist.
     *
     * Snap rebuilds activity/course/section settings forms into a two-column layout and, as part of
     * that, moves the ".collapsible-actions" block (the "Expand all" control) to the end of the
     * ".snap-form-advanced" column (see theme/snap/amd/src/snap.js, the onModSettings block).
     *
     * Core's expand_all_fields() runs a single find() over a union XPath
     * (expand-all link | per-section toggles) and clicks the first match in DOCUMENT ORDER. Because
     * Snap relocates the expand-all control to the bottom of the form, the first match in document
     * order becomes a per-section toggle, so core clicks that and only ONE fieldset expands. The rest
     * stay collapsed (display:none) and later "I set the field ..." steps fail with
     * "element not interactable".
     *
     * This override finds ONLY the collapse-menu anchor (not a union) and clicks it, so document
     * order is irrelevant and the anchor's bulk-expand handler (lib/form/amd/src/collapsesections.js)
     * expands every section at once. The "Show more" advanced-field handling is kept identical to
     * core (MDL-84801).
     *
     * @throws ElementNotFoundException Thrown by behat_base::find_all
     * @return void
     */
    protected function expand_all_fields() {
        // Expand only if JS mode, else not needed.
        if (!$this->running_javascript()) {
            return;
        }

        // Click the collapse-menu anchor directly. Targeting only this element (rather than core's
        // union with the per-section toggles) means the relocation done by Snap does not matter.
        try {
            $this->wait_for_pending_js();
            $expandallxpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' collapsible-actions ')]" .
                "//a[contains(concat(' ', normalize-space(@class), ' '), ' collapsemenu ')]" .
                "[contains(concat(' ', normalize-space(@class), ' '), ' collapsed ')]";
            $collapseexpandlink = $this->find('xpath', $expandallxpath, false, false, behat_base::get_reduced_timeout());
            $collapseexpandlink->click();
            $this->wait_for_pending_js();
        } catch (ElementNotFoundException $e) {
            // No expand-all control (single section, or already fully expanded) - nothing to do here.
            // The behat_base::find() method throws an exception if there are no elements,
            // we should not fail a test because of this.
        }

        // Different try & catch as we can have expanded fieldsets with advanced fields on them.
        try {
            $this->wait_for_pending_js();
            // Expand all fields xpath.
            $showmorexpath = "//a[normalize-space(.)='" . get_string('showmore', 'form') . "']" .
                "[contains(concat(' ', normalize-space(@class), ' '), ' moreless-toggler')]";

            // We don't wait here as we already waited when getting the expand fieldsets links.
            if (!$showmores = $this->getSession()->getPage()->findAll('xpath', $showmorexpath)) {
                return;
            }

            $js = <<<EOF
            require(['core/pending'], function(Pending) {
                const query = document.evaluate("{$showmorexpath}", document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
                if (query.snapshotLength > 0) {
                    const pendingPromise = new Pending('showmore:expand');
                    for (let i = 0, length = query.snapshotLength; i < length; ++i) {
                        query.snapshotItem(i).click();
                        if (i === length - 1) {
                            pendingPromise.resolve();
                        }
                    }
                }
            });
            EOF;

            $this->execute_script($js);
            $this->wait_for_pending_js();
        } catch (ElementNotFoundException $e) {
            // We continue with the test.
        }
    }
}