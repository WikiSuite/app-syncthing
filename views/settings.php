<?php

/**
 * Syncthing settings configuration.
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

$this->lang->load('base');
$this->lang->load('syncthing');

if (isset($gui_access_help)) {
    if ($gui_access_help['type'] == 'warn')
        echo infobox_warning(
            lang('base_warning'),
            "<div>" . $gui_access_help['msg'] . "</div>"
        );
    else
        echo infobox_info(
            lang('base_information'),
            "<div>" . $gui_access_help['msg'] . "</div>"
        );
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('syncthing/settings/edit', array('id' => 'syncthing-settings'));
echo form_header(lang('base_settings'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

if ($edit) {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/syncthing')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/syncthing/settings/edit'),
    );
}

echo field_input('version', $version, lang('syncthing_version'), TRUE);
echo field_dropdown('gui_access', $gui_access_options, $gui_access, lang('syncthing_gui_access'), $read_only);
//echo field_dropdown('send_kb', $bw_options, $send_kb, lang('syncthing_max_send_kb'), $read_only);
//echo field_dropdown('receive_kb', $bw_options, $receive_kb, lang('syncthing_max_receive_kb'), $read_only);
echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
