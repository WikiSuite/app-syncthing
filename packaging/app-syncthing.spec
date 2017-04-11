
Name: app-syncthing
Epoch: 1
Version: 1.0.6
Release: 1%{dist}
Summary: Syncthing
License: GPLv3
Group: ClearOS/Apps
Packager: eGloo
Vendor: Avantech
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
Syncthing is an alternative to proprietary sync and cloud services with something open, trustworthy and decentralized. You retain complete control over where your data is stored and who you choose to share it with.

%package core
Summary: Syncthing - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-syncthing-plugin-core
Requires: syncthing
Requires: mod_authnz_external-webconfig
Requires: mod_authz_unixgroup-webconfig

%description core
Syncthing is an alternative to proprietary sync and cloud services with something open, trustworthy and decentralized. You retain complete control over where your data is stored and who you choose to share it with.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/syncthing
cp -r * %{buildroot}/usr/clearos/apps/syncthing/

install -D -m 0644 packaging/app-syncthing.cron %{buildroot}/etc/cron.d/app-syncthing
install -D -m 0644 packaging/syncthing-webconfig-proxy.conf %{buildroot}/usr/clearos/sandbox/etc/httpd/conf.d/syncthing.conf
install -D -m 0640 packaging/syncthing.conf %{buildroot}/etc/clearos/syncthing.conf
install -D -m 0644 packaging/syncthing.php %{buildroot}/var/clearos/base/daemon/syncthing.php

%post
logger -p local6.notice -t installer 'app-syncthing - installing'

%post core
logger -p local6.notice -t installer 'app-syncthing-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/syncthing/deploy/install ] && /usr/clearos/apps/syncthing/deploy/install
fi

[ -x /usr/clearos/apps/syncthing/deploy/upgrade ] && /usr/clearos/apps/syncthing/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-syncthing - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-syncthing-core - uninstalling'
    [ -x /usr/clearos/apps/syncthing/deploy/uninstall ] && /usr/clearos/apps/syncthing/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/syncthing/controllers
/usr/clearos/apps/syncthing/htdocs
/usr/clearos/apps/syncthing/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/syncthing/packaging
%exclude /usr/clearos/apps/syncthing/unify.json
%dir /usr/clearos/apps/syncthing
/usr/clearos/apps/syncthing/deploy
/usr/clearos/apps/syncthing/language
/usr/clearos/apps/syncthing/libraries
/etc/cron.d/app-syncthing
/usr/clearos/sandbox/etc/httpd/conf.d/syncthing.conf
%config(noreplace) /etc/clearos/syncthing.conf
/var/clearos/base/daemon/syncthing.php
