<?php

/**
 * managedinstalls class
 *
 * @package munkireport
 * @author
 **/
class managedinstalls_controller extends Module_controller
{

    protected $module_path;
    protected $view_path;

    /*** Protect methods with auth! ****/
    public function __construct()
    {
        // Store module path
        $this->module_path = dirname(__FILE__);
        $this->view_path = dirname(__FILE__) . '/views/';
    }

      /**
     * Get managedinstalls information for serial_number
     *
     * @param string $serial serial number
     **/
    public function get_data($serial_number = '')
    {
        $out = array();
        if (! $this->authorized()) {
            $out['error'] = 'Not authorized';
        } else {
            $out = Managedinstalls_model::where('managedinstalls.serial_number', $serial_number)
                ->filter()
                ->get();
        }

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }

    // ------------------------------------------------------------------------

    /**
     * Get pending installs
     *
     *
     * @param string $type Type, munki or apple
     **/
    public function get_pending_installs($type = "munki")
    {
        $out = [];
        if (! $this->authorized()) {
            $out['error'] = 'Not authorized';
        } else {
            $out = $this->get_pending_items('pending_install', $type);       
        }

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }

    // ------------------------------------------------------------------------

    /**
     * Get pending removals
     *
     *
     * @param string $type Type, munki or apple
     **/
    public function get_pending_removals($type = "munki")
    {
        $out = [];
        if (! $this->authorized()) {
            $out['error'] = 'Not authorized';
        } else {
            $out = $this->get_pending_items('pending_removal', $type);       
        }

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }

    // ------------------------------------------------------------------------

        /**
     * Get pending items
     *
     *
     * @param string $status Type, pending_removal or pending_install
     * @param string $type Type, munki or apple
     **/
    private function get_pending_items($status, $type)
    {     
        $hoursBack = 24 * 7; // Hours back
        $fromdate = time() - 3600 * $hoursBack;

        $query = Managedinstalls_model::selectRaw('name, version, display_name, COUNT(*) as count')
            ->where('status', $status)
            ->where('reportdata.timestamp', '>', $fromdate)
            ->where('type', $type)
            ->filter()
            ->groupBy('name', 'display_name', 'version')
            ->orderBy('count', 'desc');

        return $query->get()->toArray();        
    }

    // ------------------------------------------------------------------------

    /**
     * Get package statistics
     *
     * Get statistics about a packat
     *
     * @param string name Package name
     * @return {11:return type}
     */
    public function get_pkg_stats($pkg = '')
    {
        $out = array();
        if (! $this->authorized()) {
            $out['error'] = 'Not authorized';
        } else {


            $query = Managedinstalls_model::selectRaw('name, version, display_name, status, COUNT(*) as count')
                ->filter()
                ->groupBy('status', 'name', 'display_name', 'version')
                ->orderBy('version', 'desc');

                if ($pkg) {
                $query = $query->where('name', '=', $pkg);
            }

            // Convert to list
            foreach ($query->get()->toArray() as $rs) {
                $status = $rs['status'] == 'install_succeeded' ? 'installed' : $rs['status'];
                $key = $rs['name'] . $rs['version'];
                if (isset($out[$key])) {
                    if (isset($out[$key][$status])) {
                        // $key exists, add count
                        $out[$key][$status] += $rs['count'];
                    } else {
                        $out[$key][$status] = $rs['count'];
                    }
                } else {
                    $out[$key] = array(
                        'name' => $rs['name'],
                        'version' => $rs['version'],
                        'display_name' => $rs['display_name'],
                        $status => $rs['count'],
                    );
                }
            }
        }

        $obj = new View();
        $obj->view('json', array('msg' => array_values($out)));
    }


    /**
     * Get installs statistics
     *
     * Undocumented function long description
     *
     * @param int $hours number of hours back or 0 for all
     * @return {11:return type}
     */
    public function get_stats($hours = 0)
    {
        $out = array();
        if (! $this->authorized()) {
            $out['error'] = 'Not authorized';
        } else {

            if ($hours > 0) {
                $timestamp = time() - 60 * 60 * $hours;
            } else {
                $timestamp = 0;
            }

            $query = Managedinstalls_model::selectRaw('managedinstalls.status, type, count(distinct reportdata.serial_number) as clients, count(managedinstalls.status) as total_items')
                ->join('machine', 'machine.serial_number', '=', 'managedinstalls.serial_number')    
                ->where('reportdata.timestamp', '>', $timestamp)
                ->whereNotNull('managedinstalls.type')
                ->filter()
                ->groupBy('status', 'type');
            $out = $query->get()->toArray();     

        }

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }

    /**
     * undocumented function summary
     *
     * Undocumented function long description
     *
     * @param type var Description
     * @return {11:return type}
     */
    public function listing($name = '', $version = '')
    {
        if (! $this->authorized()) {
            redirect('auth/login');
        }
        $data['name'] = rawurldecode($name);
        $data['version'] = rawurldecode($version);
        $data['page'] = 'clients';
        $data['scripts'] = array("clients/client_list.js");
        $obj = new View();
        $obj->view('managed_installs_listing', $data, $this->view_path);
    }

    /**
     * View a file
     * TODO: move to parent?
     * @param string $page filename
     */
    public function view($page)
    {
        if (! $this->authorized()) {
            redirect('auth/login');
        }

        $obj = new View();
        $obj->view($page, '', $this->view_path);
    }

    /**
     * Get machines with $status installs
     *   *
     * @param integer $hours Number of hours to get stats from
     **/
    public function get_clients($status = 'pending_install', $hours = 24)
    {
        $out = [];
        if (! $this->authorized()) {
            $out['error'] = 'Not authorized';
        } else {
            $timestamp = time() - 60 * 60 * $hours;
            $query = Managedinstalls_model::selectRaw('computer_name, machine.serial_number, COUNT(*) as count')
                ->join('machine', 'machine.serial_number', '=', 'managedinstalls.serial_number')    
                -> where('status', $status)
                ->filter()
                ->groupBy('machine.serial_number', 'computer_name')
                ->orderBy('count', 'desc');
            $out = $query->get()->toArray();        
        }

        $obj = new View();
        $obj->view('json', array('msg' => $out));
    }
} // END class default_module
