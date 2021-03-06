<?php

/**
 * Syncthing summary view.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage views
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2016-2018 Avantech
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
$this->lang->load('network');

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
            $info['id'],
            ($info['enabled'] ? lang('base_enabled') : lang('base_disabled')),
            $info['status'],
            ($info['enabled'] && ($info['port'] == null || $info['status'] != lang('base_running')) ? anchor_custom('syncthing/users/start/' . $username, lang('base_start')) : $info['port'])
        )
    );

    $items[] = $item;
}

$headers = [
    lang('base_username'),
    lang('syncthing_device_id'),
    lang('base_enabled'),
    lang('base_status'),
    lang('network_port')
];

echo list_table(
    lang('syncthing_users'),
    NULL,
    $headers,
    $items,
    array(
        'no_action' => TRUE,
        'read_only' => TRUE
    )
);
