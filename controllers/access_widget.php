<?php

/**
 * Syncthing controller.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage controllers
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\syncthing\Syncthing as SyncthingLibrary;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Syncthing controller.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage controllers
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2018 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

class Access_Widget extends ClearOS_Controller
{
    /**
     * Syncthing users controller
     *
     * @return view
     */

    function index()
    {
        // Bail if root
        //-------------

        $username = $this->session->userdata('username');

        if ($username === 'root')
            return;

        // Bail if not a syncthing users
        //------------------------------

        $this->load->factory('users/User_Factory', $username);

        $user_info = $this->user->get_info();

        if (!isset($user_info['plugins']['syncthing']) || !$user_info['plugins']['syncthing'])
            return;

        // Load dependencies
        //------------------

        $this->lang->load('syncthing');
        $this->load->library('syncthing/Syncthing');

        // Load the view data
        //-------------------

        try {
            $data['status'] = $this->syncthing->get_users_config($this->session->userdata('username'))[$this->session->userdata('username')];
            if ($data['gui_access'] != SyncthingLibrary::VIA_REVERSE_PROXY && !$this->syncthing->passwords_ok())
                $data['gui_no_auth_warning'] = lang('syncthing_gui_no_auth');
            $data['version'] = $this->syncthing->get_version();
            $data['gui_access'] = $this->syncthing->get_gui_access();
            $data['gui_access_options'] = $this->syncthing->get_gui_access_options();
        } catch (Engine_Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Handle URL
        //-----------

        if ($data['gui_access'] == SyncthingLibrary::VIA_REVERSE_PROXY) {
            $url = "https://" . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . "/syncthing/";
            $data['gui_access'] = "<a href='$url' target='_blank'>$url</a>";
        } else if ($data['gui_access'] == SyncthingLibrary::VIA_LOCALHOST) {
            $data['gui_access'] = lang('syncthing_console_access_only');
        } else {
            $hostname = $_SERVER['SERVER_NAME'];
            if ($data['gui_access'] == SyncthingLibrary::VIA_LAN)
                $hostname = $this->syncthing->get_lan_ip();
            $url = "https://" . $hostname . ":" . $data['status']['port'];
            $data['gui_access'] = "<a href='$url' target='_blank'>$url</a>";
        }

        // Load views
        //-----------

        $this->page->view_form('syncthing/user_profile', $data, lang('syncthing_app_name'));
    }
}
