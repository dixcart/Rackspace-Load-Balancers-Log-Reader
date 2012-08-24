#Rackspace Cloud Load Balancer log reader

A PHP application to download, extract and insert into a MySQL database, the logs
from Rackspace Cloud Load Balancers.  Eventually to include some basic stats and
an interface, but currently just extracts the raw data and can be run on a schedule.

##Usage

Import `schema.sql` into a MySQL database, edit `/includes/settings.inc.php` to add
your database and Cloud Files settings.  Run `scan-log-folder.php` in a command line
to grab all the logs and start processing them.

This will insert all logs as lines in the `rawlogs` table for you to do your own
analysis on.