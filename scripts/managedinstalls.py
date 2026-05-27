#!/usr/local/munkireport/munkireport-python3
"""
Filter the result of /Library/Managed Installs/ManagedInstallReport.plist
to only the parts that represent the installed items
"""

import plistlib
import sys
import os
import CoreFoundation
import time
import string
import datetime

# This prints out the cache file to Terminal
DEBUG = False

# Path to the default munki install dir
default_install_dir = '/Library/Managed Installs'

# Checks munki preferences to see where the install directory is set to
managed_install_dir = CoreFoundation.CFPreferencesCopyAppValue("ManagedInstallDir", "ManagedInstalls")

# Checks munki preferences to see where the ManagedSoftwareUpdate.log (and Install.log) is set
log_file = CoreFoundation.CFPreferencesCopyAppValue( "LogFile", "ManagedInstalls")

# set the paths based on munki's configuration.
if managed_install_dir:
    MANAGED_INSTALL_REPORT = os.path.join(managed_install_dir, 'ManagedInstallReport.plist')
    MANAGED_INSTALL_LOG = os.path.join(managed_install_dir, 'Logs/Install.log')

else:
    MANAGED_INSTALL_REPORT = os.path.join(default_install_dir, 'ManagedInstallReport.plist')
    MANAGED_INSTALL_LOG = os.path.join(default_install_dir, 'Logs/Install.log')

if log_file:
    MANAGED_INSTALL_LOG = log_file.replace( "ManagedSoftwareUpdate.log", "Install.log")

# Don't skip manual check
if len(sys.argv) > 1:
    if sys.argv[1] == 'debug':
        print('**** DEBUGGING ENABLED ****')
        DEBUG = True

def add_items(item_list, install_list, status, item_type, log=""):
    """Add item to list and set status"""
    for item in item_list:
        # Check if applesus item
        if item.get('productKey'):
            name = item['productKey']
        else:
            name = item['name']

        if log != "":
            install_list[name] = filter_item_log(item, name, log)
        else:
            install_list[name] = filter_item(item)

        install_list[name]['status'] = status
        install_list[name]['type'] = item_type


def add_removeditems(item_list, install_list, log):
    """Add removed item to list and set status"""

    if log != "":
        for item in item_list:
            install_list[item] = {'name': item, 'status': 'removed',
                                  'installed': False, 'display_name': item,
                                  'type': 'munki'}

            # Try to get the removal time from The Log™
            log_search = " Removal of "+item+": SUCCESSFUL"
            matched_line = [i for i in log if log_search in i]

            if matched_line != []:
                timestamp = string_to_time(matched_line[0].split(" Removal of ")[0])
                install_list[item].update({"timestamp": timestamp})

    else:
        for item in item_list:
            install_list[item] = {'name': item, 'status': 'removed',
                                  'installed': False, 'display_name': item,
                                  'type': 'munki'}


def remove_result(item_list, install_list):
    """Update list according to result"""
    for item in item_list:
        if 'time' in item:
            install_list[item['name']]['timestamp'] = str(int(item['time'].replace(tzinfo=datetime.timezone.utc).timestamp()))

        if item['status'] == 0:
            install_list[item['name']]['installed'] = False
            install_list[item['name']]['status'] = 'uninstalled'
        else:
            install_list[item['name']]['status'] = 'uninstall_failed'

        # Sometimes an item is only in RemovalResults, 
        # so we have to add extra info:

        # Add munki
        install_list[item['name']]['type'] = 'munki'

        # Fix display name
        if not install_list[item['name']].get('display_name'):
            install_list[item['name']]['display_name'] = item['display_name']


def install_result(item_list, install_list):
    """Update list according to result"""
    for item in item_list:
        if 'time' in item:
            install_list[item['name']]['timestamp'] = str(int(item['time'].replace(tzinfo=datetime.timezone.utc).timestamp()))

        # Check if applesus item
        if item.get('productKey'):
            name = item['productKey']
        else:
            name = item['name']

        if item['status'] == 0:
            install_list[name]['installed'] = True
            install_list[name]['status'] = 'install_succeeded'
        else:
            install_list[name]['status'] = 'install_failed'


def filter_item(item):
    """Only return specified keys"""
    keys = ["display_name", "installed_version", "installed_size",
            "version_to_install", "installed", "note"]

    out = {}
    for key in keys:
        try:
            out[key] = item[key]
        # pylint: disable=pointless-except
        except KeyError:  # not all objects have all these attributes
            pass

    return out


def filter_item_log(item, name, log):
    """Only return specified keys"""
    keys = ["display_name", "installed_version", "installed_size",
            "version_to_install", "installed", "note"]

    out = {}
    for key in keys:
        try:
            out[key] = item[key]
        # pylint: disable=pointless-except
        except KeyError:  # not all objects have all these attributes
            pass

    # Search for display name by default, if that fails search for by name
    if "display_name" in out and out["display_name"] != "":
        search_name = out["display_name"]
    else:
        search_name = name

    # Try to get the install time from The Log™
    if "installed_version" in out:
        log_search = " Install of "+search_name+"-"+out["installed_version"]+": SUCCESSFUL"
        matched_line = [i for i in log if log_search in i]

        if matched_line != []:
            timestamp = string_to_time(matched_line[0].split(" Install of ")[0])
            out.update({"timestamp": timestamp})

    return out


def string_to_time(date_time):
    # Returns UTC timestamp from local time zone string

    if (date_time == "0" or date_time == 0):
        return ""
    else:
        try:
            # Munki 7 and newer log
            return str(int(time.mktime(time.strptime(str(date_time), '%Y-%m-%d %H:%M:%S.%f%z')))) # 2025-07-20 07:22:52.533-05:00
        except Exception:
            try:
                # Munki 6 and older log
                return str(int(time.mktime(time.strptime(str(date_time), '%b %d %Y %H:%M:%S %z')))) # Jul 06 2025 06:19:55 -0500
            except Exception:
                return date_time

def main():
    """Main"""

    # Check if MANAGED_INSTALL_REPORT exists
    if not os.path.exists(MANAGED_INSTALL_REPORT):
        print('%s is missing.' % MANAGED_INSTALL_REPORT)
        install_report = {}
    else:
        try:
            with open(MANAGED_INSTALL_REPORT, 'rb') as fp:
                install_report = plistlib.load(fp)
        except Exception as message:
            raise Exception("Error creating plist from output: %s" % message)

    # Check if MANAGED_INSTALL_LOG exists
    if not os.path.exists(MANAGED_INSTALL_LOG):
        print('%s is missing.' % MANAGED_INSTALL_LOG)
        install_log = ""
    else:
        file = open(MANAGED_INSTALL_LOG, "r")
        log_content = file.read()
        file.close()

        install_log = []
        for line in log_content.split("\n"):
            install_log.insert(0, line)

    # pylint: disable=E1103
    install_list = {}
    if install_report.get('ManagedInstalls'):
        # Log search
        add_items(install_report['ManagedInstalls'], install_list, 'installed', 'munki', install_log)
    if install_report.get('AppleUpdates'):
        add_items(install_report['AppleUpdates'], install_list, 'pending_install', 'applesus')
    if install_report.get('ProblemInstalls'):
        add_items(install_report['ProblemInstalls'], install_list, 'install_failed', 'munki')
    if install_report.get('ItemsToRemove'):
        add_items(install_report['ItemsToRemove'], install_list, 'pending_removal', 'munki')
    if install_report.get('RemovedItems'):
        # Log search
        add_removeditems(install_report['RemovedItems'], install_list, install_log)
    if install_report.get('ItemsToInstall'):
        add_items(install_report['ItemsToInstall'], install_list, 'pending_install', 'munki')

    # Update install_list with results
    if install_report.get('RemovalResults'):
        remove_result(install_report['RemovalResults'], install_list)
    if install_report.get('InstallResults'):
        install_result(install_report['InstallResults'], install_list)
    # pylint: enable=E1103

    if DEBUG:
        print(install_list)

    # Write report to cache
    cachedir = '%s/cache' % os.path.dirname(os.path.realpath(__file__))
    with open("%s/managedinstalls.plist" % cachedir, 'wb') as fp:
        plistlib.dump(install_list, fp)

if __name__ == "__main__":
    main()
