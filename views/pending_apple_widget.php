<div class="col-lg-4 col-md-6">
    <div class="card" id="pending-apple-widget">
		<div class="card-header" data-container="body" data-i18n="[title]managedinstalls.widget.pending_apple.tooltip">
			<i class="fa fa-apple"></i>
            <span data-i18n="managedinstalls.widget.pending_apple.title"></span>
            <a href="/module/managedinstalls/listing/#pending_install" class="pull-right text-reset"><i class="fa fa-list"></i></a>
		</div>
		<div class="list-group scroll-box"></div>
	</div>
</div><!-- /col -->

<script>

$(document).on('appUpdate', function(e, lang) {


	$.getJSON( appUrl + '/module/managedinstalls/get_pending_installs/applesus', function( data ) {

        var box = $('#pending-apple-widget div.scroll-box').empty();

		if(data.length){
			$.each(data, function(i,d){
				var badge = '<span class="badge badge-light pull-right">'+d.count+'</span>',
                    url = appUrl+'/module/managedinstalls/listing/'+d.name+'#pending_install',
                    display_name = d.display_name || d.name;

				box.append('<a href="'+url+'" class="list-group-item">'+display_name+' '+d.version+badge+'</a>');
			});
		}
		else{
			box.append('<span class="list-group-item">'+i18n.t('managedinstalls.no_updates_pending')+'</span>');
		}
	});
});
</script>
