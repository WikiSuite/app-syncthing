<?php
$lang['syncthing_admins'] = 'Regardless of the Web Admin Access mode configuration, it is your responsibility to understand and ensure the proper security mechanisms are in place to ensure access to the admin utility can be peformed by the intended user(s) only.';
$lang['syncthing_app_description'] = 'Syncthing is an alternative to proprietary sync and cloud services with something open, trustworthy and decentralized. You retain complete control over where your data is stored and who you choose to share it with.';
$lang['syncthing_app_name'] = 'Syncthing';
$lang['syncthing_console_access_only'] = 'Console/localhost access only is configured.  Your users will not be able to access Syncthing\'s admin GUI without making modifications to your server.';
$lang['syncthing_danger'] = 'Danger!';
$lang['syncthing_device_id'] = 'Device ID';
$lang['syncthing_external_warning'] = 'You have configured Syncthing so that the admin user interface may be accessible from outside your network (either by listening on the external interface or using Webconfig\'s reverse proxy).  Please ensure you have set a very strong password within Syncthing to access the management page.';
$lang['syncthing_gui_access'] = 'Web Admin Access';
$lang['syncthing_gui_no_auth'] = 'The Syncthing admin interface is configured to allow remote access without a password. This can easily give local users or hackers access to read and change any files on your computer. Please set a GUI Authentication User and Password in the Settings dialog ASAP.';
$lang['syncthing_gui_on_ip'] = 'Syncthing\'s user admin interface is configured to bind to the specified IP address - a user can access the admin portal at %s.  If this is a LAN IP, any user on the LAN can potentially access someone else\'s admin GUI - this may not be what you want.  Ensure you protect access to the admin GUI by setting a strong username and password.';
$lang['syncthing_gui_via_lan'] = 'Access via LAN IP only (auto-detect)';
$lang['syncthing_gui_via_any'] = 'Access via any network (LAN & WAN)';
$lang['syncthing_gui_via_localhost'] = 'Console/localhost only';
$lang['syncthing_gui_via_webconfig'] = 'Access via Webconfig (reverse proxy)';
$lang['syncthing_gui_via_other'] = 'Self-Configured';
$lang['syncthing_max_receive_kb'] = 'Maximum Receive Limit';
$lang['syncthing_max_send_kb'] = 'Maximum Send Limit';
$lang['syncthing_no_limit'] = 'No limit';
$lang['syncthing_not_available'] = 'Syncthing is not available when the service is not running.';
$lang['syncthing_reverse_proxy_configlet_not_found'] = 'Reverse proxy configlet is missing - check user guide.';
$lang['syncthing_reverse_proxy_url'] = 'Syncthing is configured to access the GUI (file sync manager) through Webconfig\'s reverse proxy.  The URL to access this administration interface is %s.  Only users who belong to the syncthing_plugin App Policy will be allowed to configure the server\'s file sync settings.';
$lang['syncthing_tooltip_bw_limits'] = 'Use a rate limit of 0 to specify no bandwidth restriction.';
$lang['syncthing_tooltip_gui_access'] = 'For "Web Admin Access", select Access via LAN IP only if you want to access Syncthing\'s web-based user interface on the server\'s LAN IP - you will need to be on the local area network or connected via Virtual Private Network to access.';
$lang['syncthing_version'] = 'Syncthing Version';
$lang['syncthing_users'] = 'Syncthing Users';
$lang['syncthing_gui_port'] = 'GUI Port';
$lang['syncthing_renew_proxy_settings'] = 'Renew Webconfig Reverse Proxy Settings';
