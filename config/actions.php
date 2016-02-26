<?php
/**
 * Contains all action-ids
 * 
 * @package			PHPCheck
 * @subpackage	config
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * The start-typescan-action
 */
define('PC_ACTION_START_TYPESCAN',1);

/**
 * The start-statementscan-action
 */
define('PC_ACTION_START_STMNTSCAN',2);

/**
 * The start-analyzing-action
 */
define('PC_ACTION_START_ANALYZE',3);

/**
 * Adds a project
 */
define('PC_ACTION_ADD_PROJECT',4);

/**
 * Deletes projects
 */
define('PC_ACTION_DELETE_PROJECTS',5);

/**
 * Changes the project
 */
define('PC_ACTION_CHG_PROJECT',6);

/**
 * The start-phpref-scan-action
 */
define('PC_ACTION_START_PHPREFSCAN',7);

/**
 * Cleans a project
 */
define('PC_ACTION_CLEAN_PROJECT',8);

/**
 * Adds a requirement to a project
 */
define('PC_ACTION_ADD_REQ',9);

/**
 * Deletes a requirement from a project
 */
define('PC_ACTION_DELETE_REQ',10);

/**
 * Edits a project
 */
define('PC_ACTION_EDIT_PROJECT',11);
