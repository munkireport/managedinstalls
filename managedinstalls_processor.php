<?php

use CFPropertyList\CFPropertyList;
use munkireport\processors\Processor;

class Managedinstalls_processor extends Processor
{
    public function run($plist)
    {
        $this->timestamp = date('Y-m-d H:i:s');

        if (! $plist) {
            throw new Exception(
                "Error Processing Request: No property list found", 1
            );
        }

        $parser = new CFPropertyList();
        $parser->parse($plist, CFPropertyList::FORMAT_XML);
        if( ! $mylist = $parser->toArray()){
            return;
        }

        // Delete old entries
        Managedinstalls_model::where('serial_number', $this->serial_number)->delete();

        // List with fillable entries
        $fillable = [
            'serial_number' => $this->serial_number, 
            'name' => '',
            'display_name' => '',
            'version' => '',
            'size' => 0,
            'installed' => 0,
            'status' => '',
            'type' => '',
        ];

        $new_installs = [];
        $uninstalls = [];
        $save_array = [];

        # Loop through list
        foreach ($mylist as $name => $props) {
            // Get an instance of the fillable array
            $temp = $fillable;

            // Add name to temp
            $temp['name'] = $name;

            // Copy values and correct type
            foreach ($temp as $key => $value) {
                if (array_key_exists($key, $props)) {
                    $temp[$key] = $props[$key];
                    settype($temp[$key], gettype($value));
                }
            }

            // Set version
            if (isset($props['installed_version'])) {
                $temp['version'] = $props['installed_version'];
            } elseif (isset($props['version_to_install'])) {
                $temp['version'] = $props['version_to_install'];
            }

            // Set installed size
            if (isset($props['installed_size'])) {
                $temp['size'] = $props['installed_size'];
            }

            $save_array[] = $temp;

            // Store new installs
            if ($temp['status'] == 'install_succeeded') {
                $new_installs[] = $temp;
            }

            // Store uninstalls
            if ($temp['status'] == 'uninstalled') {
                $uninstalls[] = $temp;
            }
        }

        $model = Managedinstalls_model::insert(
            $save_array
        );

        return;

        $modelData = ['serial_number' => $this->serial_number];
        

        // Translate plist keys to db keys
        $translate = [
            'ManagedInstallVersion' => 'version',
            'ManifestName' => 'manifestname',
            'RunType' => 'runtype',
            'StartTime' => 'starttime',
            'EndTime' => 'endtime',
        ];

        foreach ($translate as $key => $dbkey) {
            if (array_key_exists($key, $mylist)) {
                $modelData[$dbkey] = $mylist[$key];
            }
        }

        // Parse errors and warnings
        $errorsWarnings = ['Errors' => 'error_json', 'Warnings' => 'warning_json'];
        foreach ($errorsWarnings as $key => $json) {
            $dbkey = strtolower($key);
            if (isset($mylist[$key]) && is_array($mylist[$key])) {
                // Store count
                $modelData[$dbkey] = count($mylist[$key]);

                // Store json
                $modelData[$json] = json_encode($mylist[$key]);
            } else {
                // reset
                $modelData[$dbkey] = 0;
                $modelData[$json] = json_encode([]);
            }
        }
        
        $this->_storeEvents($new_installs, $uninstalls);

        return $this;
    }
        
    private function _storeEvents($new_installs, $uninstalls)
    {
        if ($new_installs) {
            $count = count($new_installs);
            $msg = array('count' => $count);
            if ($count == 1) {
                $pkg = array_pop($new_installs);
                $msg['pkg'] = $pkg['display_name'] . ' ' . $pkg['version'];
            }
            $this->store_event(
                'success',
                'munki.package_installed',
                json_encode($msg)
            );
        }
        elseif ($uninstalls) {
            $count = count($uninstalls);
            $msg = array('count' => $count);
            if ($count == 1) {
                $pkg = array_pop($uninstalls);
                $msg['pkg'] = $pkg['display_name'] . ' ' . $pkg['version'];
            }
            $this->store_event(
                'success',
                'munki.package_uninstalled',
                json_encode($msg)
            );
        }
    }
}
