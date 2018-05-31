<?php

/**
 * Syncthing user profile configuration.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage views
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2018 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('syncthing');

///////////////////////////////////////////////////////////////////////////////
// Form Header
///////////////////////////////////////////////////////////////////////////////

echo form_header(lang('syncthing_app_name'));

///////////////////////////////////////////////////////////////////////////////
// Fields
///////////////////////////////////////////////////////////////////////////////

echo field_view(lang('syncthing_version'), $version);
echo field_view(lang('base_status'), $status['status']);
echo field_view(lang('syncthing_gui_access'), $gui_access);

///////////////////////////////////////////////////////////////////////////////
// Form footer
///////////////////////////////////////////////////////////////////////////////

echo form_footer();

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
