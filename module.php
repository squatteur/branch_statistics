<?php
/**
 * Webtrees module entry point.
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

// Register our namespace
$loader = new \Composer\Autoload\ClassLoader();
$loader->addPsr4(
    'RSO\\WebtreesModule\\Branch_Statistics\\',
    __DIR__ . '/src'
);
$loader->register();

// Create and return instance of the module
return new Module(__DIR__);
