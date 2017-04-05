<?php

/**
 * Syncthing summary view.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage views
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2016 Avantech
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

$this->lang->load('syncthing');

if (isset($gui_no_auth_warning))
        echo infobox_critical(
            lang('syncthing_danger'),
            "<div>" . $gui_no_auth_warning . "</div>"
        );

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

$items = array();

foreach ($users as $username => $info) {
    $item = array(
        'title' => $username,
        'action' => '',
        'anchors' => NULL,
        'details' => array(
            $username,
            ($info['enabled'] ? lang('base_enabled') : lang('base_disabled')),
            $info['status'],
            $info['port']
        )
    );

    $items[] = $item;
}

echo summary_table(
    lang('syncthing_users'),
    NULL,
    array(lang('base_username'), lang('base_enabled'), lang('base_status'), lang('syncthing_gui_port')),
    $items,
    array(
        'no_action' => TRUE
    )
);
