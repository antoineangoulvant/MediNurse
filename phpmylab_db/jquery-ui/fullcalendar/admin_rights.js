$(document).ready(function(){

	var calendar_old = [];
	calendar_old[0]=$("select[name='idCalendar'] option:selected").text();
	calendar_old[1]=$("select[name='idCalendar'] option:selected").val();

	$("select[name='idCalendar'] option:selected").text();
	$('#calendar_name').val(calendar_old[0]);
		
	$(document).on('change','#calendar_name', function() {
		if($('#calendar_name').val()!=calendar_old[0])
			{
				var r = confirm('Vous avez modifier le nom du calendrier, voulez-vous le sauvegarder?');
				if (r == true) {
					$.ajax({
						url: 'jquery-ui/fullcalendar/update_calendar_name.php',
						type: 'POST',
						async: false,
						data: 'name='+$('#calendar_name').val()+'&idCalendar='+calendar_old[1]				
					});
					location.reload();//.delay(30);
				} 
			}
	});
	
	$(document).on('change',"select[name='idCalendar']", function() {
		
		/*if($('#calendar_name').val()!=calendar_old[0])
		{
			var r = confirm('Vous avez modifier le nom du calendrier, voulez-vous le sauvegarder?');
			if (r == true) {
				$.ajax({
					url: 'jquery-ui/fullcalendar/update_calendar_name.php',
					type: 'POST',
					async: false,
					data: 'name='+$('#calendar_name').val()+'&idCalendar='+calendar_old[1]				
				});
				location.reload().delay(30);
			} 
		}*/
		
		calendar_old[0]=$('#calendar_name').val();
		$('#calendar_name').val($("select[name='idCalendar'] option:selected").text());
		calendar_old[0] = $('#calendar_name').val();
		calendar_old[1] = $("select[name='idCalendar'] option:selected").val();
		
		//location.reload(); //.delay(30);
	});
	
	$(document).on('click', "#add_user_right", function(){
		
		var user=$("select[name='search_by_user']").val();
		//alert(user);
		$.ajax({
			url: 'jquery-ui/fullcalendar/add_user.php',
			type: 'POST',
			async: false,
			data: 'user='+user+'&idCalendar='+$("select[name='idCalendar']").val()
						
		});
		location.reload(); //.delay(30);
	});

	$(document).on('click', "#add_group_right", function(){
		
		var group=$("select[name='search_by_group']").val();
		$.ajax({
			url: 'jquery-ui/fullcalendar/add_group.php',
			type: 'POST',
			async: false,
			data: 'group='+group+'&idCalendar='+$("select[name='idCalendar']").val()

		});
		location.reload(); //.delay(30);
	});




});
