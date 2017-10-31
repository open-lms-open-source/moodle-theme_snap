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
 * Overrides for behat navigation.
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Element\NodeElement as NodeElement;

require_once(__DIR__ . '/../../../../lib/tests/behat/behat_navigation.php');

/**
 * Overrides to make behat navigation work with Snap.
 *
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_snap_behat_navigation extends behat_navigation {

    /**
     * Check that the browser js engine can target things via xpath (document.evaluate).
     * @return boolean
     */
    private function browser_supports_document_evaluate() {
        static $supportsxpath = null;

        if ($supportsxpath === null) {
            $session = $this->getSession();
            $retstr = $session->evaluateScript('return (document.evaluate !== undefined);');
            $retstr = trim(strtolower($retstr));
            $supportsxpath = !empty($retstr);
        }

        return $supportsxpath;
    }

    /**
     * Attempt to trigger click event on node instead of actually clicking on it.
     * This stops the Navigation or Administration tree from clicking on a link inside the expandable node (p tag)
     * when the config value linkadmincategories is enabled.
     * @param NodeElement $node
     */
    protected function js_trigger_click($node) {
        $session = $this->getSession();
        $xpath = addslashes_js($node->getXpath());

        $supportsxpath = $this->browser_supports_document_evaluate();
        if ($supportsxpath) {
            $script = <<<EOF
                var node = document.evaluate(
                    "$xpath", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null
                ).singleNodeValue;            
                node.click();
EOF;
            $session->executeScript($script);
        } else {
            $node->click();
        }
    }

    /**
     * Override core behat find_node_in_navigation so that expanding navigation menu is faster and works in Snap.
     * Related core issue: MDL-58023.
     */
    protected function find_node_in_navigation($nodetext, $parentnodes, $nodetype = 'link') {
        // Site admin is different and needs special treatment.
        $siteadminstr = get_string('administrationsite');

        // Create array of all parentnodes.
        $countparentnode = count($parentnodes);

        // If JS is disabled and Site administration is not expanded we
        // should follow it, so all the lower-level nodes are available.
        if (!$this->running_javascript()) {
            if ($parentnodes[0] === $siteadminstr) {
                // We don't know if there if Site admin is already expanded so
                // don't wait, it is non-JS and we already waited for the DOM.
                $siteadminlink = $this->getSession()->getPage()->find('named_exact', array('link', "'" . $siteadminstr . "'"));
                if ($siteadminlink) {
                    $siteadminlink->click();
                }
            }
        }

        // Get top level node.
        $node = $this->get_top_navigation_node($parentnodes[0]);

        // Expand all nodes.
        for ($i = 0; $i < $countparentnode; $i++) {
            if ($i > 0) {
                // Sub nodes within top level node.
                $node = $this->get_navigation_node($parentnodes[$i], $node);
            }

            // The p node contains the aria jazz.
            $pnodexpath = "/p[contains(concat(' ', normalize-space(@class), ' '), ' tree_item ')]";
            $pnode = $node->find('xpath', $pnodexpath);

            // Keep expanding all sub-parents if js enabled.
            if ($pnode && $this->running_javascript() && $pnode->hasAttribute('aria-expanded') &&
                ($pnode->getAttribute('aria-expanded') == "false")) {

                $this->ensure_node_is_visible($pnode);

                if ($this->browser_supports_document_evaluate()) {
                    $this->js_trigger_click($pnode);
                } else {
                    // If node is a link then some driver click in the middle of the node, which click on link and
                    // page gets redirected. To ensure expansion works in all cases, check if the node to expand is a
                    // link and if yes then click on link and wait for it to navigate to next page with node expanded.
                    $nodetoexpandliteral = behat_context_helper::escape($parentnodes[$i]);
                    $nodetoexpandxpathlink = $pnodexpath . "/a[normalize-space(.)=" . $nodetoexpandliteral . "]";

                    if ($nodetoexpandlink = $node->find('xpath', $nodetoexpandxpathlink)) {
                        $behatgeneralcontext = behat_context_helper::get('behat_general');
                        $nodetoexpandlink->click();
                        $behatgeneralcontext->wait_until_the_page_is_ready();
                    } else {
                        $pnode->click();
                    }
                }

                // Wait for node to load, if not loaded before.
                if ($pnode->hasAttribute('data-loaded') && $pnode->getAttribute('data-loaded') == "false") {
                    $jscondition = '(document.evaluate("' . addslashes_js($pnode->getXpath()) . '", document, null, '.
                        'XPathResult.ANY_TYPE, null).iterateNext().getAttribute(\'data-loaded\') == "true")';

                    $this->getSession()->wait(self::EXTENDED_TIMEOUT * 1000, $jscondition);
                }
            }
        }

        // Finally, click on requested node under navigation.
        $nodetextliteral = behat_context_helper::escape($nodetext);
        $tagname = ($nodetype === 'link') ? 'a' : 'span';
        $xpath = "/ul/li/p[contains(concat(' ', normalize-space(@class), ' '), ' tree_item ')]" .
            "/{$tagname}[normalize-space(.)=" . $nodetextliteral . "]";
        return $node->find('xpath', $xpath);
    }

    /**
     * Override core function so that admin menu can be opened before trying to navigate through tree.
     */
    public function i_navigate_to_in_site_administration($nodetext) {
        $node = $this->find('css', '.block_settings');
        // Only attempt to open the admin menu if its not already open.
        if (!$node->isVisible()) {
            $this->execute('behat_general::i_click_on', ['#admin-menu-trigger', 'css_element']);
        }
        $parentnodes = array_map('trim', explode('>', $nodetext));
        array_unshift($parentnodes, get_string('administrationsite'));
        $lastnode = array_pop($parentnodes);
        $this->select_node_in_navigation($lastnode, $parentnodes);
    }
}
