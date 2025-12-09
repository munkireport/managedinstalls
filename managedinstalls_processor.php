<?php
/**
 * Managedinstalls Processor
 * 
 * Processes managed install data from Munki clients.
 * Supports both plist and YAML data formats for future compatibility.
 * 
 * @package munkireport/managedinstalls
 */

use CFPropertyList\CFPropertyList;
use munkireport\processors\Processor;

// Include the DataParser for YAML support
require_once __DIR__ . '/lib/DataParser.php';
use munkireport\managedinstalls\lib\DataParser;

class Managedinstalls_processor extends Processor
{
<<<<<<< HEAD
    private $timestamp;

    public function run($plist)
=======
    public function run($data)
>>>>>>> c05ea67 (Add YAML support alongside XML parsing)
    {
        $this->timestamp = date('Y-m-d H:i:s');

        if (! $data) {
            throw new Exception(
                "Error Processing Request: No data found", 1
            );
        }

        // Use DataParser to handle both plist and YAML formats
        $mylist = DataParser::parse($data);
        if (! $mylist) {
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
            'munki_timestamp' => null,
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
            
            // Set timestamp
            if (isset($props['timestamp'])) {
                $temp['munki_timestamp'] = $props['timestamp'];
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

        $model = Managedinstalls_model::insertChunked(
            $save_array
        );

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
