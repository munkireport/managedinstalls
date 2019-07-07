<?php 

$this->view('listings/default',
[
  "i18n_title" => 'managedinstalls.report',
  "js_link" => "module/managedinstalls/js/managedinstalls_functions",
  "js_init" => "initializeManagedInstalls('$name', '$version')",
	"table" => [
		[
			"column" => "machine.computer_name",
			"i18n_header" => "listing.computername",
			"formatter" => "clientDetail",
			"tab_link" => "munki",
		],
		[
			"column" => "reportdata.serial_number",
      "i18n_header" => "displays_info.machineserial",
      "filter" => "managedInstallsFilter",
		],
		["column" => "managedinstalls.name", "i18n_header" => "name",],
    ["column" => "managedinstalls.display_name", "i18n_header" => "displayname",],
    ["column" => "managedinstalls.version", "i18n_header" => "version",],
    [
      "column" => "managedinstalls.status",
      "i18n_header" => "status",
      "formatter" => "managedInstallStatus"
    ],
    [
      "column" => "reportdata.timestamp",
      "i18n_header" => "listing.checkin",
      "formatter" => "timestampToMoment",
    ],
    ["column" => "managedinstalls.type", "i18n_header" => "type",],
    [
      "column" => "managedinstalls.size",
      "i18n_header" => "size",
      "formatter" => "fileSize"
    ],
	]
]);
