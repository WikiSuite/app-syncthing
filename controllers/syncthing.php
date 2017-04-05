<?php

/**
 * Syncthing controller.
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
 * Syncthing controller.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage controllers
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2016 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

class Syncthing extends ClearOS_Controller
{

    /**
     * Syncthing default controller
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->load->library('syncthing/Syncthing');
        $this->lang->load('syncthing');

        // Load views
        //-----------

        $views = array('syncthing/server', 'syncthing/network', 'syncthing/settings', 'syncthing/users');
        if ($this->syncthing->get_gui_access() == SyncthingLibrary::VIA_REVERSE_PROXY)
            $views[] = 'syncthing/policy';

        $this->page->view_forms($views, lang('syncthing_app_name'));
    }
}
