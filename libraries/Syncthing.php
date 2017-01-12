<?php

/**
 * Syncthing class.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage libraries
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2016 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\syncthing;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('syncthing');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\incoming_firewall\Incoming as Incoming;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('incoming_firewall/Incoming');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/File_No_Match_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Syncthing class.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage libraries
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2016 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

class Syncthing extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = "/root/.config/syncthing/config.xml";
    const FILE_REVERSE_PROXY = "/usr/clearos/sandbox/etc/httpd/conf.d/syncthing.conf";
    const PORT_PROTO_DATA = "22000:TCP";
    const PORT_PROTO_DISCOVERY = "21027:UDP";
    const PORT_GUI = 8384;
    const VIA_LOCALHOST = "localhost";
    const VIA_REVERSE_PROXY = "webconfig";

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $config = NULL;
    protected $is_loaded = FALSE;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Syncthing constructor.
     */

    function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('syncthing');
    }

    /**
     * Get send rate limit.
     *
     * @return integer
     * @throws Engine_Exception
     */

    function get_send_limit()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG, TRUE);
        $limit = $file->lookup_value("/^\s*<maxSendKbps>/");
        return preg_replace('/<\/maxSendKbps>/', '', $limit);
    }

    /**
     * Get receive rate limit.
     *
     * @return integer
     * @throws Engine_Exception
     */

    function get_receive_limit()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG, TRUE);
        $limit = $file->lookup_value("/^\s*<maxRecvKbps>/");
        return preg_replace('/<\/maxRecvKbps>/', '', $limit);
    }

    /**
     * Sets send rate limit.
     *
     * @param integer $limit send limit
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_send_limit($limit)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_max_send($limit));

        $file = new File(self::FILE_CONFIG, TRUE);
        $file->replace_lines("/<maxSendKbps>.*<\/maxSendKbps>/", "\t<maxSendKbps>$limit</maxSendKbps>\n");
    }

    /**
     * Sets receive rate limit.
     *
     * @param integer $limit receive limit
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_receive_limit($limit)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_max_receive($limit));

        $file = new File(self::FILE_CONFIG, TRUE);
        $file->replace_lines("/<maxRecvKbps>.*<\/maxRecvKbps>/", "\t<maxRecvKbps>$limit</maxRecvKbps>\n");
    }

    /**
     * Set GUI access.
     *
     * @param string $access access
     * @throws Engine_Exception
     */

    function set_gui_access($access)
    {
        clearos_profile(__METHOD__, __LINE__);
        $address = "127.0.0.1:" . self::PORT_GUI;
        // Do not change anything if set to 'other'
        if ($access == "other")
            return;
        if ($access != self::VIA_LOCALHOST && $access != self::VIA_REVERSE_PROXY)
            $address = $access . ":" . self::PORT_GUI;
        
        $file = new File(self::FILE_CONFIG, TRUE);
        $file->replace_lines_between("/<address>.*<\/address>/", "\t<address>$address</address>\n", "/<gui.*>/", "/<\/gui>/");

        $proxy = new File(self::FILE_REVERSE_PROXY, TRUE);
        if (!$proxy->exists())
            throw new File_Not_Found_Exception(clearos_exception_message(lang("syncthing_reverse_proxy_configlet_not_found")));
        if ($access == self::VIA_REVERSE_PROXY) {
            $proxy->replace_lines('/^#ProxyPass*/', "ProxyPass /syncthing/ http://127.0.0.1:" . self::PORT_GUI . "/\n");
            // If we're using reverse proxy with user (API) driven authentication, disable syncthing's Basic auth
            try {
                $file->replace_lines_between("/<user>.*<\/user>/", "\t<user></user>\n", "/<gui.*>/", "/<\/gui>/");
            } catch (File_No_Match_Exception $e) {
                // Ignore
            }
        } else {
            $proxy->replace_lines('/^ProxyPass*/', "#ProxyPass /syncthing/ http://127.0.0.1:" . self::PORT_GUI . "/\n");
        }
    }

    /**
     * Get bandwidth rate limit options.
     *
     * @return array
     * @throws Engine_Exception
     */

    function get_bw_options()
    {
        clearos_profile(__METHOD__, __LINE__);
        $options = array('0' => lang('syncthing_no_limit'));
        for ($index = 10; $index <= 100; $index+=10)
            $options[$index] = $index . ' ' . lang('base_kilobits_per_second');
        for ($index = 150; $index <=1000; $index+=50)
            $options[$index] = $index . ' ' . lang('base_kilobits_per_second');
        for ($index = 1500; $index <=10000; $index+=500)
            $options[$index] = $index . ' ' . lang('base_kilobits_per_second');
        return $options;
    }

    /**
     * Get GUI access.
     *
     * @return string
     * @throws Engine_Exception
     */

    function get_gui_access()
    {
        clearos_profile(__METHOD__, __LINE__);
        $access = self::VIA_LOCALHOST;
        $file = new File(self::FILE_CONFIG, TRUE);
        $address = preg_replace('/<\/address>/', '', $file->lookup_value_between("/^\s*<address>/", "/<gui/", "/<\/gui>/"));
        list($ip, $port) = explode(":", $address);
        if ($ip == "127.0.0.1") {
            $file = new File(self::FILE_REVERSE_PROXY, TRUE);
            if ($file->exists()) {
                try {
                    if ($file->lookup_line("/^ProxyPass*/"))
                        $access = self::VIA_REVERSE_PROXY;
                } catch (File_No_Match_Exception $e) {
                    // Ignore
                }
            }
        } else {
            $access = $ip;
        }
        return $access;
    }

    /**
     * Get GUI access options.
     *
     * @return array
     * @throws Engine_Exception
     */

    function get_gui_access_options()
    {
        clearos_profile(__METHOD__, __LINE__);
        $options = array(
            self::VIA_LOCALHOST => lang('syncthing_gui_via_localhost'),
            self::VIA_REVERSE_PROXY => lang('syncthing_gui_via_webconfig'),
        );

        // Lookup IP addresses
        $iface_manager = new Iface_Manager();
        $network_interfaces = $iface_manager->get_interface_details();
        foreach ($network_interfaces as $interface => $detail) {
            if (!$detail['configured'])
                continue;
            $options[$detail['address']] = $detail['address'];
        }
        return $options;
    }

    /**
     * Returns list of systemd services.
     *
     * @return array list of systemd services
     * @throws Engine_Exception
     */

    public function get_systemd_services()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO - Do we need to look for others?
        return array('syncthing@root.service');
    }

    /**
     * Sanity check firewall settings.
     *
     * @return  void
     * @throws Engine_Exception
     */

    public function sanity_check_fw()
    {
        clearos_profile(__METHOD__, __LINE__);
        $incoming = new Incoming();
        
        $incoming_allow = $incoming->get_allow_ports(); 
        $required_rules = array(
            explode(":", self::PORT_PROTO_DATA),
            explode(":", self::PORT_PROTO_DISCOVERY),
        );
        foreach ($incoming_allow as $info) {
            if (array_key_exists($info['port'], $required_rules) && $info['enabled']) {
                if ($required_rules[$info['port']] == $info['proto'])
                    unset($required_rules[$info['port']]);
            }
        }

        return $required_rules;
    }

    /**
     * Is GUI password protection set.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    public function is_gui_pw_set()
    {
        clearos_profile(__METHOD__, __LINE__);
        $file = new File(self::FILE_CONFIG, TRUE);
        try {
            $file->lookup_value("/^\s*<user>\w+<\/user>/");
            return TRUE;
        } catch (File_No_Match_Exception $e) {
            return FALSE;
        }
    }

    /**
     * Is bootstrapped.
     *
     * @return boolean
     * @throws Engine_Exception
     */

    public function is_bootstrapped()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG, TRUE);
        if (!$file->exists())
            return FALSE;
        return TRUE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for gui access.
     *
     * @param stirng $access GUI access
     *
     * @return mixed void if access is valid, errmsg otherwise
     */

    function validate_gui_access($access)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$access || $access == NULL)
            return lang('syncthing_gui_access') . ' - ' . lang('base_invalid');
    }

    /**
     * Validation routine for maximum bandwidth send limit.
     *
     * @param integer $max max limit
     *
     * @return mixed void if rate is valid, errmsg otherwise
     */

    function validate_max_send($max)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_numeric($max) || $max < 0)
            return lang('syncthing_max_send_kw') . ' - ' . lang('base_invalid');
    }

    /**
     * Validation routine for maximum bandwidth receive limit.
     *
     * @param integer $max max limit
     *
     * @return mixed void if rate is valid, errmsg otherwise
     */

    function validate_max_receive($max)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_numeric($max) || $max < 0)
            return lang('syncthing_max_receive_kw') . ' - ' . lang('base_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

}
