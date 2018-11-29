<?php

/**
 * Syncthing class.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage libraries
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2016-2018 Avantech
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

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\groups\Group_Factory;
use \clearos\apps\incoming_firewall\Incoming as Incoming;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\two_factor_auth\Two_Factor_Auth;
use \clearos\apps\users\User_Manager_Factory;
use \SimpleXMLElement as SimpleXMLElement;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('groups/Group_Factory');
clearos_load_library('incoming_firewall/Incoming');
clearos_load_library('network/Iface_Manager');
clearos_load_library('two_factor_auth/Two_Factor_Auth');
clearos_load_library('users/User_Manager_Factory');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Folder_Not_Found_Exception as Folder_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Folder_Not_Found_Exception');

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
 * @copyright  2016-2018 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

class Syncthing extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = "/etc/clearos/syncthing.conf";
    const FILE_USER_CONFIG = "/.config/syncthing/config.xml";
    const FILE_XML = "config.xml";
    const FILE_REVERSE_PROXY = "/usr/clearos/sandbox/etc/httpd/conf.d/syncthing.conf";
    const FILE_RESTART_MULTIUSER = "/var/clearos/framework/tmp/syncthing.restart";
    const FOLDER_HOME = "/home";
    const PORT_PROTO_DATA = "22000:TCP";
    const PORT_PROTO_DISCOVERY = "21027:UDP";
    const VIA_OTHER = "other";
    const VIA_LOCALHOST = "localhost";
    const VIA_LAN = "lan";
    const VIA_ANY = "any";
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
     * Get user settings.
     *
     * @param $selected selected username
     *
     * @return void
     * @throws Engine_Exception
     */

    public function get_users($selected = null)
    {
        clearos_profile(__METHOD__, __LINE__);
        $info = array();
        try {
            $user_factory = new User_Manager_Factory();
            $user_manager = $user_factory->create();
            $users = $user_manager->get_core_details();

            $groupobj = Group_Factory::create('syncthing_plugin');
            $group_info = $groupobj->get_info();
            foreach ($users as $username => $details) {

                if ($selected != null && $selected != $username)
                    continue;
                $status = lang('base_disabled');
                $enabled = FALSE;

                if (in_array($username, $group_info['core']['members']))
                    $enabled = TRUE;

                $file = new File(self::FOLDER_HOME . "/$username" . self::FILE_USER_CONFIG, TRUE);
                if (!$file->exists()) {
                    $size = 0;
                    if ($enabled)
                        $status = lang('syncthing_status_not_initialized');
                }

                $options['validate_exit_code'] = FALSE;
                $shell = new Shell();
                $exit_code = $shell->execute(self::COMMAND_SYSTEMCTL, "status syncthing@" . $username . ".service", FALSE, $options);

                if ($exit_code !== 0)
                    $status = lang('base_stopped');
                else
                    $status = lang('base_running');

                $info[$username] = array(
                    'enabled' => $enabled,
                    'status' => $status,
                    'size' => NULL
                );
            }
            ksort($info);
            return $info;
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Start user service.
     *
     * @param String  $username username
     *
     * @return void
     */

    public function start_user_service($username)
    {
        clearos_profile(__METHOD__, __LINE__);
        try {
            $shell = new Shell();
            $exit_code = $shell->execute(self::COMMAND_SYSTEMCTL, "start syncthing@" . $username . ".service", TRUE);
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }
    }

    /**
     * Is enabled.
     *
     * @param String  $username username
     *
     * @return void
     */

    public function is_enabled($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['validate_exit_code'] = FALSE;
        $options['env'] = 'LANG=en_US';
        $shell = new Shell();
        $shell->execute(parent::COMMAND_SYSTEMCTL, "is-enabled syncthing@$username.service", TRUE, $options);
        if ($shell->get_last_output_line() == 'enabled')
            return TRUE;
        return FALSE;
    }

    /**
     * Get is running status for user.
     *
     * @param String  $username username
     *
     * @return void
     */

    public function is_running($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['validate_exit_code'] = FALSE;
        $shell = new Shell();
        $shell->execute(parent::COMMAND_SYSTEMCTL, "is-active syncthing@$username.service", TRUE, $options);
        if ($shell->get_last_output_line() == 'active')
            return TRUE;
        return FALSE;
    }

    /**
     * Set enable/disable.
     *
     * @param String  $username username
     * @param boolean $enabled
     *
     * @return void
     */

    public function set_state($username, $enabled)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['validate_exit_code'] = FALSE;
        $shell = new Shell();
        if ($enabled) {
            $shell->execute(parent::COMMAND_SYSTEMCTL, "enabled syncthing@$username.service", TRUE, $options);
            $shell->execute(parent::COMMAND_SYSTEMCTL, "restart syncthing@$username.service", TRUE, $options);
        } else {
            $shell->execute(parent::COMMAND_SYSTEMCTL, "disabled syncthing@$username.service", TRUE, $options);
            $shell->execute(parent::COMMAND_SYSTEMCTL, "stop syncthing@$username.service", TRUE, $options);
        }
    }

    /**
     * Get send rate limit.
     *
     * @param string  $path  path
     *
     * @return integer
     * @throws Engine_Exception
     */

    function get_send_limit($path)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($path . self::FILE_USER_CONFIG, TRUE);
        $limit = $file->lookup_value("/^\s*<maxSendKbps>/");
        return preg_replace('/<\/maxSendKbps>/', '', $limit);
    }

    /**
     * Get receive rate limit.
     *
     * @param string  $path  path
     *
     * @return integer
     * @throws Engine_Exception
     */

    function get_receive_limit($path)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($path . self::FILE_USER_CONFIG, TRUE);
        $limit = $file->lookup_value("/^\s*<maxRecvKbps>/");
        return preg_replace('/<\/maxRecvKbps>/', '', $limit);
    }

    /**
     * Sets send rate limit.
     *
     * @param string  $path  path
     * @param integer $limit send limit
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_send_limit($path, $limit)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_max_send($limit));

        $file = new File($path . self::FILE_USER_CONFIG, TRUE);
        $file->replace_lines("/<maxSendKbps>.*<\/maxSendKbps>/", "\t<maxSendKbps>$limit</maxSendKbps>\n");
    }

    /**
     * Sets receive rate limit.
     *
     * @param string  $path  path
     * @param integer $limit receive limit
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_receive_limit($path, $limit)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_max_receive($limit));

        $file = new File($path . self::FILE_USER_CONFIG, TRUE);
        $file->replace_lines("/<maxRecvKbps>.*<\/maxRecvKbps>/", "\t<maxRecvKbps>$limit</maxRecvKbps>\n");
    }

    /**
     * Get LAN IP.
     *
     *
     * @return String
     * @throws Engine_Exception
     */

    public function get_lan_ip()
    {
        clearos_profile(__METHOD__, __LINE__);
        $iface_manager = new Iface_Manager();
        return $iface_manager->get_most_trusted_ips()[0];
    }

    /**
     * Restart all daemons.
     * @param $force force restart
     *
     * @throws Engine_Exception
     */

    function restart_multiuser($force = false)
    {
        clearos_profile(__METHOD__, __LINE__);
        $file = new File(self::FILE_RESTART_MULTIUSER);
        if (!$force && !$file->exists())
            return;
        $users = $this->get_users_config();
        foreach ($users as $user => $meta) {
            try {
                if ($force || ($meta['status'] && !$meta['enabled']) || (!$meta['status'] && $meta['enabled']))
                    $this->set_state($user, $meta['enabled']);
            } catch (Exception $e) {
                clearos_log('syncthing', $user . ":" . clearos_exception_message($e));
            }
        }
        if ($file->exists())
            $file->delete();
    }

    /**
     * Fix settings.
     *
     * @return null
     * @throws Engine_Exception
     */

    function override_settings()
    {
        clearos_profile(__METHOD__, __LINE__);

        // We only override settings in reverse proxy mode
        if ($this->get_gui_access() != self::VIA_REVERSE_PROXY)
            return; 

        $users = $this->get_users_config();
        foreach ($users as $user => $meta) {
            if (!$meta['enabled']) {
                $options['validate_exit_code'] = FALSE;
                $shell = new Shell();
                $shell->execute(parent::COMMAND_SYSTEMCTL, "stop syncthing@$user.service", TRUE, $options);
                continue;
            }
            $req_restart = FALSE;
            $file = new File(self::FOLDER_HOME . "/$user" . self::FILE_USER_CONFIG, TRUE);
            if (!$file->exists())
                continue;
            $xml = $file->get_contents();
            $config = new SimpleXMLElement($xml);
            if (!empty($config->gui->password)) {
                $config->gui->password = "";
                $req_restart = TRUE;
            }
            if (strtolower($config->gui['tls']) === "true") {
                $config->gui['tls'] = "false";
                $req_restart = TRUE;
            }
            $temp = new File(self::FILE_XML, TRUE);
            if ($temp->exists())
                $temp->delete();
            $temp->create($user, "allusers", "0600");
            $temp->add_lines($config->asXML());
            $temp->move_to(self::FOLDER_HOME . "/$user" . self::FILE_USER_CONFIG);
            if ($req_restart && $meta['enabled']) {
                $options['validate_exit_code'] = FALSE;
                $shell = new Shell();
                $shell->execute(parent::COMMAND_SYSTEMCTL, "restart syncthing@$user.service", TRUE, $options);
                sleep(3);
            }
        }
    }

    /**
     * Get user config.
     *
     * @return array
     * @throws Engine_Exception
     */

    function get_users_config($selected = null)
    {
        clearos_profile(__METHOD__, __LINE__);

        $data = [];
        $hostname = gethostname();
        $users = $this->get_users();
        foreach ($users as $user => $meta) {
            if ($selected != null && $selected != $user)
                continue;
            $file = new File(self::FOLDER_HOME . "/$user" . self::FILE_USER_CONFIG, TRUE);
            if (!$file->exists())
                continue;
            $xml_source = $file->get_contents();

            $xml = simplexml_load_string($xml_source);
            if ($xml === FALSE)
                continue;

            $data[$user] = [
                'ip' => NULL,
                'port' => null,
            ];
            if (preg_match("/(.*):(.*)/", $xml->gui->address, $match)) {
                $data[$user] = [
                    'ip' => $match[1],
                    'port' => $match[2]
                ];
            }
            if (empty($xml->gui->password))
                $data[$user]['password'] = TRUE;
            else
                $data[$user]['password'] = FALSE;

            $temp = $xml->xpath("//device[@name='$hostname']");
            if (!empty($temp)) {
                $data[$user]['id'] = $temp[0]['id'];
            }
        }
        $data = array_merge_recursive($users, $data);
        return $data;
    }

    /**
     * Update user config.
     *
     * @return void
     * @throws Engine_Exception
     */

    function update()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->override_settings();

        $access = $this->get_gui_access();

        $file = new File(self::FILE_RESTART_MULTIUSER);
        if (!$file->exists())
            $file->create("webconfig", "webconfig", "0644");

        $proxy = new File(self::FILE_REVERSE_PROXY, TRUE);
        if (!$proxy->exists())
            throw new File_Not_Found_Exception(clearos_exception_message(lang('syncthing_reverse_proxy_configlet_not_found')));

        $iface_manager = new Iface_Manager();
        $lan = $iface_manager->get_most_trusted_ips()[0];
        $users = $this->get_users_config();
        if ($access == self::VIA_REVERSE_PROXY) {
            // Delete any users that don't exist anymore
            $lines = $proxy->get_contents_as_array();
            foreach ($lines as $line) {
                if (preg_match("/RewriteCond %{REMOTE_USER} =(.*)/", $line, $match)) {
                    if (!array_key_exists($match[1], $users)) {
                        $proxy->delete_lines("/\s*RewriteCond.* \"" . $match[1] . "\"$/i");
                        $proxy->delete_lines("/\s*RewriteRule.* # " . $match[1] . "$/i");
                    }
                }
            }
        }
        foreach ($users as $user => $meta) {
            $file = new File(self::FOLDER_HOME . "/$user" . self::FILE_USER_CONFIG, TRUE);
            if (!$file->exists())
                continue;
            $address = "127.0.0.1:" . $meta['port'];
            if ($access == self::VIA_ANY)
                $address = "0.0.0.0:" . $meta['port'];
            else if ($access == self::VIA_LAN)
                $address = $lan . ":" . $meta['port'];
            else if ($access == self::VIA_LOCALHOST)
                $address = "127.0.0.1:" . $meta['port'];
            else if ($access == self::VIA_REVERSE_PROXY)
                $address = "127.0.0.1:" . $meta['port'];
        
            $file->replace_lines_between("/<address>.*<\/address>/", "\t<address>$address</address>\n", "/<gui.*>/", "/<\/gui>/");
            if ($access == self::VIA_REVERSE_PROXY) {
                try {
                    $proxy->lookup_line("/RewriteEngine on/i");
                } catch (File_No_Match_Exception $e) {
                    $proxy->replace_lines("/\s*RewriteEngine o.*/", "\tRewriteEngine on\n");
                }
                try {
                    $proxy->lookup_line("/\s*RewriteCond %{REMOTE_USER} =$user/i");
                    if ($meta['enabled']) {
                        $proxy->replace_lines("/\s*RewriteRule \"\\/syncthing\\/\\(\\.\\*\\)\" \"http:\\/\\/127\\.0\\.0\\.1:\d+\\/\\$1\" \\[P\\] # $user$/i", "\tRewriteRule \"/syncthing/(.*)\" \"http://127.0.0.1:" . $meta['port'] . "/$1\" [P] # $user\n");
                    } else {
                        try {
                            $proxy->delete_lines("/\s*RewriteCond.* \"$user\"$/i");
                            $proxy->delete_lines("/\s*RewriteRule.* # $user$/i");
                        } catch (File_No_Match_Exception $e) {
                            // Don't need to do anything
                        }
                    }
                } catch (File_No_Match_Exception $e) {
                    $proxy->add_lines_after("\tRewriteRule \"/syncthing/(.*)\" \"http://127.0.0.1:" . $meta['port'] . "/$1\" [P] # $user\n", "/RewriteEngine on/i");
                    $proxy->add_lines_after("\tRewriteCond %{REMOTE_USER} =$user\n", "/RewriteEngine on/i");
                }
                try {
                    // If we're using reverse proxy with user (API) driven authentication, disable syncthing's Basic auth
                    $file->replace_lines_between("/<user>.*<\/user>/", "\t<user></user>\n", "/<gui.*>/", "/<\/gui>/");
                } catch (File_No_Match_Exception $e) {
                    // Ignore
                }
            } else {
                try {
                    $proxy->lookup_line("/RewriteEngine off/i");
                } catch (File_No_Match_Exception $e) {
                    $proxy->replace_lines("/\s*RewriteEngine o.*/i", "\tRewriteEngine off\n");
                }
            }
        }
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

        // Validation
        // ----------

        Validation_Exception::is_valid($this->validate_gui_access($access));

        $this->_set_parameter('gui_access', $access);

        $this->update();
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

        if (!$this->is_loaded)
            $this->_load_config();

        if (isset($this->config['gui_access']))
            return $this->config['gui_access'];

        return self::VIA_OTHER;
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
            self::VIA_REVERSE_PROXY => lang('syncthing_gui_via_authentication'),
            self::VIA_LAN => lang('syncthing_gui_via_lan'),
            self::VIA_ANY => lang('syncthing_gui_via_any'),
            self::VIA_OTHER => lang('syncthing_gui_via_other'),
        );

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

        $groupobj = Group_Factory::create('syncthing_plugin');
        $group_info = $groupobj->get_info();

        foreach ($group_info['core']['members'] as $user)
            $services[] = 'syncthing@' . $user . '.service';

        return $services;
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
     * Is password protection set.
     *
     * @throws Engine_Exception
     */

    public function passwords_ok()
    {
        clearos_profile(__METHOD__, __LINE__);
        $users = $this->get_users_config();
        foreach ($users as $user => $meta) {
            if ($meta['enable'] == TRUE && !$meta['password'])
                return FALSE;
        }
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

    /**
     * Loads configuration files.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG, 'match', "/(\S*)\s*=\s*(.*)/");

        try {
            $this->config = $configfile->load();
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Generic set routine.
     *
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return  void
     * @throws Engine_Exception
     */

    function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG, TRUE);

            if (!$file->exists())
                $file->create('root', 'root', '0640');

            $match = $file->replace_lines("/^$key\s*=\s*/", "$key = $value\n");

            if (!$match)
                $file->add_lines("$key=\"$value\"\n");
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_ERROR);
        }

        $this->is_loaded = FALSE;
    }
}
