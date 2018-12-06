# VMware Stats

This plugin Gathers information from a VMware system and displays it for each host.

## Install

  * Download the archive and place it in your $ONABASE/www/local/plugins directory, the directory must be named `vmware_stats`
  * Make the plugin directory owned by your webserver user I.E.: `chown -R www-data /opt/ona/www/local/plugins/vmware_stats`
  * `cp /opt/ona/www/local/plugins/vmware_stats/vmware_stats.conf.php.example /opt/ona/etc/vmware_stats.conf.php`
  * Modify the `vmware_stats.conf.php` file and provide the DNS name, user and password for each server.  Ideally this is a read only user.

## Usage

Any host that is defined with a manufacturer name of `VMware` will attempt to display VMware status information.

When you navigate to a specific host within ONA it will use the primary DNS name of that host to search within vcenter.  The expectation is that either your host within VMware is named with the same FQDN as it is in ONA or VMTools is installed and will provide the host name.  If either of these two things do not provide a match to the host name then it will likely fail to display any data.

When data is found it will show the status of VMTools and the VMs power state.  It will also show items such as Memory, CPU, Disk, and Network.  This data is pulled real time and is meant to help show more detail about the host from the VMware perspective.
