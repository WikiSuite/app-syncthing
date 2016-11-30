<?php

/**
 * Javascript helper for Syncthing.
 *
 * @category   apps
 * @package    syncthing
 * @subpackage javascript
 * @author     eGloo <developer@egloo.ca>
 * @copyright  2016 Avantech
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.egloo.ca/clearos/marketplace/apps/syncthing
 */

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('registration');
clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type: application/x-javascript');

?>
var lang_error = '<?php echo lang('base_error'); ?>';

$(document).ready(function() {
    if ($('#syncthing-settings').length == 0 && ($(location).attr('href').match('.*app\/syncthing$') != null))
        is_running();
});

function is_running() {
    $.ajax({
        url: '/app/syncthing/server/status',
        method: 'GET',
        dataType: 'json',
        success : function(json) {
            if (json.status == 'running') {
                window.location = '/app/syncthing';
                return;
            }
            window.setTimeout(is_running, 1000);
        }
    });
}
// vim: syntax=javascript ts=4

