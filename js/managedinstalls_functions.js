// Global object to hold the variables
var managedInstallsVariables = {
    pkgName: '',
    pkgVersion: ''
}

// Init function
var initializeManagedInstalls = function(pkgName, pkgVersion){
    // Save the variables to the global space so the filter can use them
    managedInstallsVariables.pkgName = decodeURIComponent(pkgName);
    managedInstallsVariables.pkgVersion = decodeURIComponent(pkgVersion);
    if(pkgName){
        // Set name on heading
        $('h3>span:first').text(managedInstallsVariables.pkgName);

        if(pkgVersion){
            // Add version to heading
            $('h3>span:first').text(managedInstallsVariables.pkgName + ' ('+managedInstallsVariables.pkgVersion+')');
        }
    }
}

// Filters
var managedInstallsFilter = function(colNumber, d){

    // Add where array to filter the results
    d.where = [];

    if(managedInstallsVariables.pkgName){
        d.where.push({
            table: 'managedinstalls',
            column: 'name',
            value: managedInstallsVariables.pkgName
        });

        if(managedInstallsVariables.pkgVersion){
            d.where.push({
                table: 'managedinstalls',
                column: 'version',
                value: managedInstallsVariables.pkgVersion
            });
        }
    }
}

// Formatters
var managedInstallStatus = function(colNumber, row){
    // Show label depending on status
    var col = $('td:eq('+colNumber+')', row),
        status = col.text();
    if(mr.statusFormat[status]){
        status = '<span class = "label label-'+mr.statusFormat[status].type+'">'+status+'</span>'
    }
    col.html(status)
}