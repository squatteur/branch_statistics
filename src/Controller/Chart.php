<?php
/**
 * Webtrees module.
 *
 * Copyright (C) 2017  Rico Sonntag
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
 *
 * @category   Webtrees
 * @package    Module
 * @subpackage Controller
 * @author     Bestel Squatteur <bestel@squatteur.net>
 * @link       https://github.com/squatteur/branch_statistics/
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace RSO\WebtreesModule\Branch_Statistics\Controller;

use \Fisharebest\Webtrees\Controller\ChartController;
use \Fisharebest\Webtrees\Family;
use \Fisharebest\Webtrees\Filter;
use \Fisharebest\Webtrees\Functions\FunctionsEdit;
use \Fisharebest\Webtrees\Functions\FunctionsPrint;
use \Fisharebest\Webtrees\I18N;
use \Fisharebest\Webtrees\Individual;
use \Fisharebest\Webtrees\Theme;
use \Fisharebest\Webtrees\Theme\ThemeInterface;
use \Fisharebest\Webtrees\Tree;

/**
 * Fan chart controller class.
 *
 * @category   Webtrees
 * @package    Module
 * @subpackage Controller
 * @author     Bestel Squatteur <bestel@squatteur.net>
 * @license    TBD
 * @link       https://github.com/squatteur/branch_statistics/
 */
class Chart extends ChartController
{
    /**
     * Minimum number of displayable generations.
     *
     * @var int
     */
    const MIN_GENERATIONS = 2;

    /**
     * Maximum number of displayable generations.
     *
     * @var int
     */
    const MAX_GENERATIONS = 18;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Get default number of generations to display
        $defaultGenerations = $this->getTree()->getPreference('DEFAULT_PEDIGREE_GENERATIONS');

        // Extract the request parameters
        $this->generations        = Filter::getInteger('generations', self::MIN_GENERATIONS, self::MAX_GENERATIONS, $defaultGenerations);

        // Create page title
        $title = $this->translate('Branch Statistics');

        if ($this->root && $this->root->canShowName()) {
            $title = $this->translate('Branch Statistics of %s', $this->root->getFullName());
        }

        $this->setPageTitle($title);
    }

    /**
     * Get tree instance.
     *
     * @return Tree
     */
    private function getTree()
    {
        global $WT_TREE;
        return $WT_TREE;
    }

    /**
     * Get the theme instance.
     *
     * @return ThemeInterface
     */
    private function getTheme()
    {
        return Theme::theme();
    }

    /**
     * Translate a string, and then substitute placeholders.
     *
     * @return string
     */
    private function translate(/* var_args */)
    {
        // Damn ugly static methods all around :(
        return call_user_func_array(
            '\\Fisharebest\\Webtrees\\I18N::translate',
            func_get_args()
        );
    }

    /**
     * Get a symbol based on the presence of a media for the event : ° to BIRT or CHR, + to DEAT or BURI and x to MARR
     *
     * @param Individual $person Individual instance
     * @param string $event_type 
     *
     * @return string symbol
     */
    private function findEventMedia(Individual $person = null, $event_type = WT_EVENTS_BIRT)
    {
        if (preg_match_all('/\n1 (?:' . $event_type . ').*(?:\n[2-9].*)*(?:\n2 OBJE (.+))/', $person->getGedcom(), $ged_obje, PREG_SET_ORDER))
        {
            if (($event_type == 'BIRT') || ($event_type == 'CHR')) {
                return TRUE;
            } elseif (($event_type == 'DEAT') || ($event_type == 'BURI')) {
                return TRUE;
            }
        }
        if ($event_type == 'FAMS')
        {
            if (preg_match_all('/\n1 (?:' . $event_type . ').*/', $person->getGedcom(), $ged_obje, PREG_SET_ORDER)) {
                foreach ($person->getSpouseFamilies() as $family) {
                    if ($family->getFirstFact('MARR')) {
                        if ($family->getFirstFact('OBJE')) {
                            return TRUE;
                        }
                    }
                    
                }
            }
        }

        return FALSE;
    }

    /**
     * Get the individual data required for display the chart.
     *
     * @param Individual $person     Start person
     * @param int        $generation Generation the person belongs to
     *
     * @return array
     */
    private function getIndividualData(Individual $person, $generation)
    {
        $fullName        = Filter::unescapeHtml($person->getFullName());
        $alternativeName = Filter::unescapeHtml($person->getAddName());

        return array(
            'id'              => 0,
            'xref'            => $person->getXref(),
            'generation'      => $generation,
            'name'            => $fullName,
            'alternativeName' => $alternativeName,
            'sex'             => $person->getSex(),
            'born'            => $person->getBirthYear(),
            'died'            => $person->getDeathYear(),
            'complet'         => ($this->findEventMedia($person, 'BIRT') || $this->findEventMedia($person, 'CHR')) && $this->findEventMedia($person, 'FAMS') && ($this->findEventMedia($person, 'DEAT') || $this->findEventMedia($person, 'BURI')),
            'dcd'             => $person->isDead(),
            'lieu'            => $person->getBirthPlace(),
        );
    }

    /*
        * Calculates the median of the values of an array 
    */

    public function calculate_median($arr) {
        $count = count($arr); //total numbers in array
        $middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value
        if($count % 2) { // odd number, middle is the median
            $median = $arr[$middleval];
        } else { // even number, calculate avg of 2 medians
            $low = $arr[$middleval];
            $high = $arr[$middleval+1];
            $median = (($low+$high)/2);
        }
        return round($median,0);
    }

    /**
     * Recursively build the data array of the individual ancestors.
     *
     * @param Individual $person     Start person
     * @param int        $generation Current generation
     *
     * @return array
     */
    public function buildJsonTree(
        Individual $person = null, $generation = 1
    ) {
        // Maximum generation reached
        if (($generation > $this->generations)
            || !($person instanceof Individual)
        ) {
            return array();
        }

        $data   = $this->getIndividualData($person, $generation);
        $family = $person->getPrimaryChildFamily();

        if (!($family instanceof Family)) {
            return $data;
        }

        // Recursively call the method for the parents of the individual
        $fatherTree = $this->buildJsonTree($family->getHusband(), $generation + 1);
        $motherTree = $this->buildJsonTree($family->getWife(), $generation + 1);

        // Add array of child nodes
        if ($fatherTree) {
            $data['children'][] = $fatherTree;
        }

        if ($motherTree) {
            $data['children'][] = $motherTree;
        }

        return $data;
    }

    /**
     * Get the HTML link to find an individual.
     *
     * @return string
     */
    private function printFindIndividualLink()
    {
        return FunctionsPrint::printFindIndividualLink('rootid');
    }

    /**
     * Get the HTML for the "generations" input form control element.
     *
     * @return string
     */
    private function getGenerationsInputControl()
    {
        return FunctionsEdit::editFieldInteger('generations', $this->generations, self::MIN_GENERATIONS, self::MAX_GENERATIONS);
    }

    /**
     * Returns the content HTML, including form and chart placeholder.
     *
     * @return string
     */
    private function getContentHtml()
    {
        $viewFile = __DIR__ . '/../View/form.phtml';

        if (is_file($viewFile)) {
            ob_start();
            include $viewFile;
            return ob_get_clean();
        }

        return false;
    }

    /**
     * Get the raw update url. The "rootid" parameter must be the last one as
     * the url gets appended with the clicked individual id in order to load
     * the required chart data.
     *
     * @return string
     */
    private function getUpdateUrl()
    {
        $queryData = array(
            'mod'         => 'branch_statistics',
            'mod_action'  => 'update',
            'ged'         => $this->getTree()->getNameHtml(),
            'generations' => $this->generations,
            'rootid'      => '',
        );

        return 'module.php?' . http_build_query($queryData);
    }

    /**
     * Get the raw individual url. The "pid" parameter must be the last one as
     * the url gets appended with the clicked individual id in order to link
     * to the right individual page.
     *
     * @return string
     */
    private function getIndividualUrl()
    {
        $queryData = array(
            'ged' => $this->getTree()->getNameHtml(),
            'pid' => '',
        );

        return 'individual.php?' . http_build_query($queryData);
    }

    public function jsonworker()
    {
        // Encode chart parameters to json string
        $chartParams = json_encode(
            array(
                'rtl'                => I18N::direction() === 'rtl',
                'generations'        => $this->generations,
                'updateUrl'          => $this->getUpdateUrl(),
                'individualUrl'      => $this->getIndividualUrl(),
                'data'               => $this->buildJsonTree($this->root),
            )
        );
        return $chartParams;
    }


    /**
     * Render the fan chart form HTML and JSON data.
     *
     * @return string HTML snippet to include in page HTML
     */
    public function render()
    {
        // Encode chart parameters to json string
        $chartParams = json_encode(
            array(
                'rtl'                => I18N::direction() === 'rtl',
                'generations'        => $this->generations,
                'updateUrl'          => $this->getUpdateUrl(),
                'individualUrl'      => $this->getIndividualUrl(),
                'data'               => $this->buildJsonTree($this->root),
            )
        );
        return $this->getContentHtml();
    }
}
