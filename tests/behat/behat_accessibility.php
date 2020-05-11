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
 * Steps definitions to open and close action menus.
 *
 * @package    core
 * @category   test
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use Behat\Mink\Exception\ExpectationException;
// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions to assist with accessibility testing.
 *
 * @package    core
 * @category   test
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * Taken from https://github.com/andrewnicols/moodle/commit/2af7d173254e47956ac86166083cb1708a1a5980
 * And modified by Rafael Becerra <rafael.becerra@openlms.com> to work with our environment.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_accessibility extends behat_base {
    /**
     * Open the action menu in
     *
     * @Given I should not see any accessibility violations
     * @return void
     */
    public function run_accessiblity_test() {
        $axeurl = (new \moodle_url('/theme/snap/tests/behat/axe.min.js'))->out(false);
        $runaxe = <<<EOF
(() => {
    // Inject the axe content.
    const axeTag = document.createElement('script');
    axeTag.src = "{$axeurl}";
    axeTag.dataset.purpose = 'axe';
    axeTag.results = {};
    axeTag.exception = null;
    axeTag.onload = () => {
        // The instaclick version of Webdriver does not know how to handle promises.
        // We queue up the results and fetch them later.
        axe.run({
            rules: {
                'color-contrast': { enabled: false }
            }
        })
        .then(results => {
            axeTag.results = results;
        })
        .catch(exception => {
            axeTag.exception = exception;
        });
    };

    document.head.append(axeTag);
})();
EOF;
        $this->getSession()->executeScript($runaxe);
        $fetchresults = <<<EOF
(() => {
    const axeScriptHeader = document.querySelector('[data-purpose="axe"]');
    let results = {};
    if (axeScriptHeader.exception !== null) {
        results.exception = axeScriptHeader.exception;
    } else if (axeScriptHeader.results !== null) {
        results.violations = axeScriptHeader.results.violations;
    }
    return JSON.stringify(results);
})();
EOF;

        // Poll for results.
        $exception = new Exception('Accessibility issues found');
        $results = $this->spin(function() use ($fetchresults) {
            $resultdata = $this->getSession()->evaluateScript($fetchresults);
            $results = json_decode($resultdata);
            if (property_exists($results, 'exception')) {
                throw new \Exception($resultdata);
            }
            if (property_exists($results, 'violations')) {
                return $results;
            }
            return false;
        }, false, false, $exception, true);
        $violations = $results->violations;
        if (!count($violations)) {
            return;
        }
        $violationdata = "Accessibility violations found:\n";
        foreach ($violations as $violation) {
            $nodedata = '';
            foreach ($violation->nodes as $node) {
                $failedchecks = [];
                foreach (array_merge($node->any, $node->all, $node->none) as $check) {
                    $failedchecks[$check->id] = $check->message;
                }
                $nodedata .= sprintf(
                    "    - %s:\n      %s\n\n",
                    implode(', ', $failedchecks),
                    implode("\n      ", $node->target)
                );
            }
            $violationdata .= sprintf(
                "  %.03d violations of '%s' (severity: %s)\n%s\n",
                count($violation->nodes),
                $violation->description,
                $violation->impact,
                $nodedata
            );
        }
        throw new ExpectationException($violationdata, $this->getSession());
    }
}
