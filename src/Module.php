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
 * @subpackage Branch_Statistics
 * @author     Bestel Squatteur <bestel@squatteur.net>
 * @link       https://github.com/squatteur/branch_statistics/
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace RSO\WebtreesModule\Branch_Statistics;

use \RSO\WebtreesModule\Branch_Statistics\Controller\Chart;
use \Fisharebest\Webtrees\Auth;
use \Fisharebest\Webtrees\Filter;
use \Fisharebest\Webtrees\I18N;
use \Fisharebest\Webtrees\Individual;
use \Fisharebest\Webtrees\Menu;
use \Fisharebest\Webtrees\Module as WebtreesModule;
use \Fisharebest\Webtrees\Module\AbstractModule;
use \Fisharebest\Webtrees\Module\ModuleChartInterface;
use \Fisharebest\Webtrees\Tree;

/**
 * Branch_Statistics module class.
 *
 * @category   Webtrees
 * @package    Module
 * @subpackage Branch_Statistics
 * @author     Bestel Squatteur <bestel@squatteur.net>
 * @license    TBD
 * @link       https://github.com/squatteur/branch_statistics/
 */
class Module extends AbstractModule implements ModuleChartInterface
{
    /**
     * Returns whether the chart module is active or not.
     *
     * @return boolean
     */
    private function isActive()
    {
        return WebtreesModule::isActiveChart($this->getTree(), 'branch_statistics');
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
     * Get the modules static url path.
     *
     * @return string
     */
    private function getModuleUrlPath()
    {
        return WT_STATIC_URL . WT_MODULES_DIR . $this->getName();
    }

    /**
     * How should this module be labelled on tabs, menus, etc.?
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->translate('Branch statistics');
    }

    /**
     * A sentence describing what this module does.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->translate('Branch Statistics of an individual’s ancestors.');
    }

    /**
     * What is the default access level for this module?
     *
     * Some modules are aimed at admins or managers, and are not generally shown to users.
     *
     * @return int
     */
    public function defaultAccessLevel()
    {
        return Auth::PRIV_PRIVATE;
    }

    /**
     * Return a menu item for this chart.
     *
     * @param Individual $individual Current individual instance
     *
     * @return Menu
     */
    public function getChartMenu(Individual $individual)
    {
        $link = 'module.php?mod=' . $this->getName()
            . '&amp;rootid=' . $individual->getXref()
            . '&amp;ged=' . $individual->getTree()->getNameUrl();

        return new Menu(
            $this->getTitle(),
            $link,
            'menu-chart-statistics',
            array(
                'rel' => 'nofollow',
            )
        );
    }

    /**
     * Return a menu item for this chart - for use in individual boxes.
     *
     * @param Individual $individual Current individual instance
     *
     * @return Menu
     */
    public function getBoxChartMenu(Individual $individual)
    {
        return $this->getChartMenu($individual);
    }

    /**
     * This is a general purpose hook, allowing modules to respond to routes
     * of the form module.php?mod=FOO&mod_action=BAR
     *
     * @param string $modAction Module action
     *
     * @return void
     */
    public function modAction($modAction)
    {
        if ($modAction === 'update') {
            $rootId = Filter::get('rootid', WT_REGEX_XREF);
            $person = Individual::getInstance($rootId, $this->getTree());
            $chart  = new Chart();

            header('Content-Type: application/json;charset=UTF-8');

            echo json_encode($chart->buildJsonTree($person));
            exit;
        }

        global $controller;

        $urlPath = $this->getModuleUrlPath();

        $controller = new Chart();
        $controller
            ->restrictAccess($this->isActive())
            ->pageHeader()
            ->addExternalJavascript(WT_AUTOCOMPLETE_JS_URL);

            //echo '<link rel="stylesheet" type="text/css" href="'
            //. $urlPath . '/css/statistics.css">';

        echo $controller->render();
    }
}
