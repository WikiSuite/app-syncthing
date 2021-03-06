<?php

/**
 * Syncthing controller.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage controllers
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2017 Avantech
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
 * @copyright  2017 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

class Users extends ClearOS_Controller
{
    /**
     * Syncthing users controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('syncthing');
        $this->load->library('syncthing/Syncthing');

        try {
            $data['users'] = $this->syncthing->get_users_config();
            if ($data['gui_access'] != SyncthingLibrary::VIA_REVERSE_PROXY && !$this->syncthing->passwords_ok())
                $data['gui_no_auth_warning'] = lang('syncthing_gui_no_auth');
        } catch (Engine_Engine_Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('syncthing/summary', $data, lang('syncthing_app_name'));
    }

    /**
     * Start Syncthing service for user
     *
     * @return view
     */

    function start($username)
    {
        // Load dependencies
        //------------------
        $this->lang->load('syncthing');
        $this->load->library('syncthing/Syncthing');
        $this->syncthing->start_user_service($username);
	redirect('/syncthing');
	return;
    }
}
