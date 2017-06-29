<?php

/**
 * Syncthing settings controller.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage controllers
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\syncthing\Syncthing as SyncthingLibrary;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Syncthing settings controller.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage controllers
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2016 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

class Settings extends ClearOS_Controller
{
    /**
     * Index.
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('syncthing/Syncthing');

        // Load view data
        //---------------

        $data = array(
            'edit' => FALSE,
            'version' => $this->syncthing->get_version(),
            //'send_kb' => $this->syncthing->get_send_limit(),
            //'receive_kb' => $this->syncthing->get_receive_limit(),
            'gui_access' => $this->syncthing->get_gui_access(),
            'gui_access_options' => $this->syncthing->get_gui_access_options(),
            'bw_options' => $this->syncthing->get_bw_options(),
        );

        if ($data['gui_access'] == SyncthingLibrary::VIA_REVERSE_PROXY) {
            $data['gui_access_help'] = array('type' => 'info', 'msg' =>
                sprintf(lang('syncthing_reverse_proxy_url'), "<block><strong>https://" . $_SERVER['SERVER_NAME'] . ":" .
                $_SERVER['SERVER_PORT'] . "/syncthing/</strong></block>") . "<div style='margin-top: 15px;' class='text-center'>" .
                anchor_custom('/app/syncthing/settings/renew', lang('syncthing_renew_proxy_settings'), 'high') . "</div>"
            );
        } else if ($data['gui_access'] == SyncthingLibrary::VIA_LOCALHOST) {
            $data['gui_access_help'] = array('type' => 'warn', 'msg' => lang('syncthing_console_access_only'));
        } else {
            $hostname = $_SERVER['SERVER_NAME'];
            if ($data['gui_access'] == SyncthingLibrary::VIA_LAN)
                $hostname = $this->syncthing->get_lan_ip();
            $data['gui_access_help'] = array('type' => 'warn', 'msg' =>
                sprintf(lang('syncthing_gui_on_ip'), "https://" . $hostname . ":GUI_Port")
            );
        }

        $this->page->view_form('syncthing/settings', $data, lang('base_settings'));
    }

    /**
     * Edit settings view.
     *
     * @return view
     */

    function edit()
    {
        // Load libraries
        //---------------

        $this->load->library('syncthing/Syncthing');

        // Set validation rules
        //---------------------
       
        $this->form_validation->set_policy('gui_access', 'syncthing/Syncthing', 'validate_gui_access');
        //$this->form_validation->set_policy('send_kb', 'syncthing/Syncthing', 'validate_max_send');
        //$this->form_validation->set_policy('receive_kb', 'syncthing/Syncthing', 'validate_max_receive');
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------
        if ($form_ok) {
            try {
                $this->syncthing->set_gui_access($this->input->post('gui_access'));
                //$this->syncthing->set_send_limit($this->input->post('send_kb'));
                //$this->syncthing->set_receive_limit($this->input->post('receive_kb'));
                if ($this->syncthing->get_status() == SyncthingLibrary::STATUS_RUNNING) {
                    // Reset doesn't work on this multi-service
                    $this->syncthing->set_running_state(FALSE);
                    $this->syncthing->set_running_state(TRUE);
                }
                redirect('/syncthing');
                return;
            } catch (Exception $e) {
                $this->page->set_message(clearos_exception_message($e), 'warning');
                redirect('/syncthing/settings/edit');
                return;
            }
        }

        $data = array(
            'edit' => TRUE,
            'version' => $this->syncthing->get_version(),
            //'send_kb' => $this->syncthing->get_send_limit(),
            //'receive_kb' => $this->syncthing->get_receive_limit(),
            'gui_access' => $this->syncthing->get_gui_access(),
            'gui_access_options' => $this->syncthing->get_gui_access_options(),
            'bw_options' => $this->syncthing->get_bw_options(),
        );

        $this->page->view_form('syncthing/settings', $data, lang('base_settings'));
    }

    /**
     * Renew.
     */

    function renew()
    {
        // Load libraries
        //---------------

        $this->load->library('syncthing/Syncthing');

        $this->syncthing->update();
        redirect('/syncthing');
        return;
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
