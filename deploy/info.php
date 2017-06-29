<?php

/////////////////////////////////////////////////////////////////////////////
// General information
///////////////////////////////////////////////////////////////////////////// 
$app['basename'] = 'syncthing';
$app['version'] = '1.1.0';
$app['release'] = '1';
$app['vendor'] = 'WikiSuite';
$app['packager'] = 'eGloo';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('syncthing_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('syncthing_app_name');
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_file');
$app['tooltip'] = array(
    lang('syncthing_tooltip_gui_access'),
    lang('syncthing_admins'),
);

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['syncthing']['title'] = $app['name'];

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-syncthing-plugin-core',
    'syncthing',
    'mod_authnz_external-webconfig',
    'mod_authz_unixgroup-webconfig',
);

$app['core_file_manifest'] = array(
    'syncthing.php' => array('target' => '/var/clearos/base/daemon/syncthing.php'),
    'syncthing.conf' => array(
        'target' => '/etc/clearos/syncthing.conf',
        'mode' => '0640',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'app-syncthing.cron' => array(
        'target' => '/etc/cron.d/app-syncthing',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    ),
    'syncthing-webconfig-proxy.conf' => array(
        'target' => '/usr/clearos/sandbox/etc/httpd/conf.d/syncthing.conf',
        'mode' => '0640',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);

$app['delete_dependency'] = array(
    'app-syncthing-plugin-core',
    'app-syncthing-core',
    'syncthing'
);
