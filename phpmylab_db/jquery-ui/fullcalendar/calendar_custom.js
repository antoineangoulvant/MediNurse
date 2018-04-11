var idCalendar=$('#calendar_id').val();
var curSource="jquery-ui/fullcalendar/events.php?idCalendar="+idCalendar;
var canModify='';
var options;

$(document).ready(function(){
	var c_name= $('#calendar_id option:selected').text();
	var titre_h2 =  document.getElementById('calendar_name');
	titre_h2.textContent= c_name;
	canModify=getPermissions();
	//alert(canModify);
	
	options = {      /* 	********	config du calendar		********	*/
		lang: 'fr',
		theme: true,
		defaultView:'agendaWeek', // par défaut on affiche un agenda semaine
		header:{ // Mise en forme de l en tête
			left:'prev,today,next', // à gauche: les boutons de navigation
			center:'title', // au milieu: le titre
			right:'month,agendaWeek,agendaDay' // à droite: les boutons de type de vue
	   },
	   titleFormat:{
			 month: "MMMM YYYY",
            week: "MMM D // W",
            day: "D MMM YYYY"
	   },
	   eventSources:[
		curSource,
	   {
		  url: "./jquery-ui/fullcalendar/jours_feries_events.php",
		  selectable:false,
		  editable:false
		  
	   }],
	   editable:canModify, 		// droit de modifier les évenements déjà créés                      ////// le bug d'affichage de la modif des events est ici 
	   selectable:true,		   //permet de créer des évènements directement sur la grille
	//   eventLimit:true,     //permet d'afficher "more" s'il y a trop d'events sur un jour
	   selectHelper:true,
	   minTime: "07:00:00",
	   maxTime: "20:00:00",
	   weekends: false,  //show_weekends,
	   weekNumbers: true,
	   allDayDefault: false,
	   textColor: 'black',
	   allDaySlot:false,
	   height: 660,

	   
	   //FONCTIONS
	   //création évènement via clic sur la grille
	   select:function (start, end, allDay,calEvent) { 
			if( canModify==false){
					calendar.fullCalendar('unselect');
					return;
			}
			$( "#datepicker_start").datepicker({dateFormat: "yy-mm-dd"});
			$( "#datepicker_end").datepicker({dateFormat: "yy-mm-dd"});
			
			$( "#datepicker_start").datepicker("setDate", new Date(start));
			$( "#datepicker_end").datepicker("setDate", new Date(end));	 
			$( "#start_hour").val(moment(start).format("HH:mm"));
			$( "#end_hour").val(moment(end).format("HH:mm"));
			$("#event_title").val("");
			$( "#eventContent" ).dialog(
			{
					resizable: true,
					height:600,
					width:600,
					modal: true,
					buttons: {
							"Sauvegarder": function() {
								var title =$("#event_title").val();
								var start2=$( "#datepicker_start").val()+" "+moment().format($( "#start_hour").val(),"HH:mm:ss");
								var end2 =$( "#datepicker_end").val()+" "+moment().format($( "#end_hour").val(),"HH:mm:ss");
								var color=$("select[name='colorpicker']").val();
								var desc=$("#description").val();
								idCalendar=$('#calendar_id').val();
								var lieu = $('#lieu').val();
								var intervenant=$('#intervenant').val();
								if(start2>end2){
									alert("Date invalide, saisissez une date de fin post\351rieure \340 la date de d\351but");
								
								}else{
									 $.ajax({
										 url: './jquery-ui/fullcalendar/add_events.php',
										 data: 'title='+ title+'&start='+ start2 +'&end='+ end2 +'&backgroundColor='+color +'&description='+ desc+'&idCalendar='+idCalendar+'&lieu='+lieu+'&intervenant='+intervenant,
										 type: "POST",
										 success: function(json) {
											
												calendar.fullCalendar('renderEvent',
												   {
														title:title,
														start:start2,
														end:end2,
														editable:true
														},
														true      //make the event stick
													);
												calendar.fullCalendar('removeEvents');          // supprimer tous les events  
												calendar.fullCalendar('refetchEvents');			// pour les recharger										
											}
										});														
									$( this ).dialog( "close" );
								}
							 },
							 "Annuler": function() {
								$( this ).dialog( "close" );
							 }
						 }
					 
					});
	   },			 
	 
	// Fonction appelée lors d'un clic sur un évènement
	eventClick: function(calEvent, jsEvent, view) {
		if(calEvent.title=='Jour f\u00e9ri\u00e9' || canModify==false){
			// rien ----> jour férié ou lecture seul
			return;
		}
		$("#datepicker_start").datepicker({dateFormat: "yy-mm-dd"});
		$("#datepicker_end").datepicker({dateFormat: "yy-mm-dd"});
		
		// Pré-remplir les champs
		$("#event_title").val(calEvent.title);
		$("#datepicker_start").datepicker("setDate", new Date(calEvent.start));
		$("#datepicker_end").datepicker("setDate", new Date(calEvent.end));	 
		$("#start_hour").val(moment(calEvent.start).format("HH:mm"));
		$("#end_hour").val(moment(calEvent.end).format("HH:mm"));
		$("select[name='colorpicker']").val(calEvent.backgroundColor);
		$("#description").val(calEvent.description);
		$('#lieu').val(calEvent.lieu);
		$('#intervenant').val(calEvent.intervenant);

		$("#eventContent" ).dialog({
			resizable: true,
			height:600,
			width:600,
			modal: true,
			buttons: {
						"Sauvegarder": function() {
							var title =$("#event_title").val();
							var start =$( "#datepicker_start").val()+" "+moment().format($( "#start_hour").val(),"HH:mm:ss");
							var end =$( "#datepicker_end").val()+" "+moment().format($( "#end_hour").val(),"HH:mm:ss");
							var color=$("select[name='colorpicker']").val();
							var desc=$("#description").val();
							var lieu = $('#lieu').val();
							var intervenant=$('#intervenant').val();
							if(start>end){
								alert("Date invalide, saisissez une date de fin post\351rieure \340 la date de d\351but");
							}else{
								 $.ajax({
									 url: './jquery-ui/fullcalendar/update_events.php',
									 data: 'title='+ title+'&start='+ start +'&end='+ end +'&id='+ calEvent.id +'&backgroundColor='+color +'&description='+ desc+'&lieu='+lieu+'&intervenant='+intervenant,
									 type: "POST",
									 success: function(json) {
											calendar.fullCalendar('removeEvents');          // supprimer tous les events  
											calendar.fullCalendar('refetchEvents');			// pour les recharger
										}
									});														
								$( this ).dialog( "close" );
							}
						 },
						 "Annuler": function() {
							$( this ).dialog( "close" );
						 },
						 "Supprimer": function() {
							 $.ajax({
								 url: './jquery-ui/fullcalendar/delete_event.php',
								 data: '&idEvent='+ calEvent.id,
								 type: "POST",
								 success: function(json) {
										calendar.fullCalendar('removeEvents');          // supprimer tous les events  
										calendar.fullCalendar('refetchEvents');			// pour les recharger
									}
							 });
							$( this ).dialog( "close" );
						 }
					 }
				 
		});				
	},
		
	// Fonction permettant l'instanciation des évènements 
	 eventRender: function(event, element) {
			if(canModify==false || event.title=='Jour f\u00e9ri\u00e9'){
				event.editable=false;
			}else{
				event.editable=true;
			}
			//event.title=encodeURI(event.title);
			var start_hour=event.start.format('HH:MM').toString();
			start_hour=start_hour.split(":");
			start_hour=start_hour[0];
			if(start_hour=="00"){
				element.find('.fc-time').hide(); 	// cacher l'heure de début dans la vue d'un évènement 
			}
			else if( event.title=='Jour f\u00e9ri\u00e9'){
			     element.find('.fc-time').hide(); 	// cacher l'heure de début  
			     element.find('.fc-title').append("<br/>" + event.description);    
			}else{
				 element.find('.fc-title').append("<br/>Intervenant:  " + event.intervenant); // ajouter dans la vue de l'event
				 element.find('.fc-title').append("<br/>Lieu:  " +event.lieu); 
			}
        }, 
        
      // Fonction permettant le drag & drop
	  eventDrop: function(event, delta) {
		 start =  moment(event.start).format("YYYY-MM-DD HH:mm:ss");
		 end =  moment(event.end).format("YYYY-MM-DD HH:mm:ss");
		 $.ajax({
			 url: './jquery-ui/fullcalendar/update_events.php',
			 data: 'title='+ event.title+'&start='+ start +'&end='+ end +'&id='+ event.id+ '&backgroundColor=' +event.backgroundColor +'&description='+ event.description+'&lieu='+event.lieu+'&intervenant='+event.intervenant,
			 type: "POST",
			 success: function(json) {
					calendar.fullCalendar('removeEvents');          // supprimer tous les events  
					calendar.fullCalendar('refetchEvents');			// pour les recharger depuis la database
				}
			});
				
		},
	 
	   // fonction permettant l'édition de la durée d'un event 
	   eventResize: function(event) {
		 start =  moment(event.start).format("YYYY-MM-DD HH:mm:ss");
		 end =  moment(event.end).format("YYYY-MM-DD HH:mm:ss");
		 $.ajax({
			 url: './jquery-ui/fullcalendar/update_events.php',
			 data: 'title='+ event.title+'&start='+ start +'&end='+ end +'&id='+event.id  +'&backgroundColor='+ event.backgroundColor +'&description='+ event.description+'&lieu='+event.lieu+'&intervenant='+event.intervenant,
			 type: "POST",
			 success: function(json) {
					calendar.fullCalendar('removeEvents');          // supprimer tous les events  
					calendar.fullCalendar('refetchEvents');			// pour les recharger depuis la database
				}
		 });
			
		},
		
		/*eventMouseover : function( event, jsEvent, view ) { 
			//	var layer =	"<div id='events' >scsdc</div>";
			//	$('#calendar').append(layer);
			//$('.fc-event-inner', this).append('<div id=\"'+event.id+'\" class=\"hover-end\">yo</div>');
		},
		/*eventMouseout : function( event, jsEvent, view ) { 
				//var layer =	"<div id='events' >scsdc</div>";
				//$('#calendar').append(layer);
		},*/
		
		// Lors d'un changement de vue (semaine suivante, précendente, mois, jour...)
		viewRender : function(view, element){
			MAJ_names();  // met à  jour les champs 'Etudiants et 'Tuteur' 
		}		
	// });
 }         //fin du tableau options

//alert(options['editable']);
	
	 
	
// Fonction appelée lors d'un changement de calendrier (via le select)
$('#calendar_id').change(function() {	

	var c_name= $('#calendar_id option:selected').text();			// MAJ du titre 
	var titre_h2 =  document.getElementById('calendar_name');
	titre_h2.textContent= c_name;
	$('#calendar').fullCalendar('destroy');
	
	if(c_name=='Calendrier T\351l\351phone GTC'){
		options.weekends=true;
		options.header.right='month,agendaWeek';
		options.defaultView='month';
		//$('#edit_calendar').hide();
    $('#stage_div').hide();
		$("label[for='tuteur']").hide();
		$("label[for='etudiants']").hide();
		$('#apply_mois_type').show();
	}
	else if(c_name=='Calendrier de gestion des stagiaires'){
		options.weekends=false;
		$('#stage_div').show();
		$("label[for='tuteur']").show();
		$("label[for='etudiants']").show();
		options.header.right='month,agendaWeek,agendaDay';
		options.defaultView='agendaWeek';
		$('#apply_mois_type').hide();
		
	}else{
		$('#stage_div').hide();
		$("label[for='tuteur']").hide();
		$("label[for='etudiants']").hide();
		options.header.right='month,agendaWeek,agendaDay';
		options.defaultView='agendaWeek';
		$('#apply_mois_type').hide();
	}	
	
	idCalendar=$('#calendar_id').val();
	getPermissions();					// affiche ou non la partie droite d'édition du calendrier
	options.eventSources[0]="jquery-ui/fullcalendar/events.php?idCalendar="+idCalendar;
	var calendar = $('#calendar').fullCalendar(options);
	MAJ_names();						// MAJ des inputs	
	$.ajax({
		url: "calendrier.php",
		type: "POST",
		data: "idCalendar="+$('#calendar_id').val()
	});
});	 
		 
		 
// Appelée lors du clic sur le bouton "Appliquer semaine type"
$(document).on('click', "#apply_semaine_type", function(){
	var curWeek_start = $('#calendar').fullCalendar('getView').start.format('YYYY-MM-DD');   // date de début de la semaine vu sur la grille
	$.ajax({
	   url: "jquery-ui/fullcalendar/add_semaine_type.php",
	   type: 'POST',
	   data: 'idCalendar='+$('#calendar_id').val()+'&idSemaine='+$('#semaine_id').val()+'&date_view='+curWeek_start+'&nom_etud='+$('#nom_etud').val()+'&nom_tuteur='+$('#nom_tuteur').val(),

	   success: function(){
			calendar.fullCalendar('removeEvents');          
			calendar.fullCalendar('refetchEvents');
			if(c_name!='Calendrier de gestion des stagiaires')
			{
				$('#stage_div').hide();
				$("label[for='tuteur']").hide();
				$("label[for='etudiants']").hide();
			}else{
				$('#stage_div').show();
				$("label[for='tuteur']").show();
				$("label[for='etudiants']").show();
				MAJ_names();
			}
		 },
		 error: function (request, status, error) {
			alert("ko");
		 }
		
	});
	
});	

// Appelée lors du clic sur le bouton "Etablir le calendrier sur 2 mois"
$(document).on('click', "#apply_mois_type", function(){
	var curWeek_start = $('#calendar').fullCalendar('getView').start.format('YYYY-MM-DD');   // date de début de la semaine vu sur la grille
	$.ajax({
	   url: "jquery-ui/fullcalendar/add_mois_type.php",
	   type: 'POST',
	   data: 'idCalendar='+$('#calendar_id').val()+'&idSemaine='+$('#semaine_id').val()+'&date_view='+curWeek_start,

	   success: function(){
			calendar.fullCalendar('removeEvents');          
			calendar.fullCalendar('refetchEvents');
			/*if(c_name!='Calendrier de gestion des stagiaires')
			{
				$('#stage_div').hide();
				$("label[for='tuteur']").hide();
				$("label[for='etudiants']").hide();
			}else{
				$('#stage_div').show();
				$("label[for='tuteur']").show();
				$("label[for='etudiants']").show();
				MAJ_names();
			}*/
		 },
		 error: function (request, status, error) {
			alert("ko");
		 }
		
	});
	
});	
		
		
$(document).on('click', "#edit_names", function(){
			$("#nom_tuteur").prop('disabled', false);	
			$("#nom_etud").prop('disabled', false);
			$("#edit_names").hide();
		//	$("#save_names").show();      // n marche pas
			document.getElementById('save_names').style.visibility='visible';
			document.getElementById('show_names').style.visibility='visible';
});
		
$(document).on('click', "#save_names", function(){
	  var curWeek_start = $('#calendar').fullCalendar('getView').start.format('YYYY-MM-DD HH:mm:ss');   // date de début de la semaine vu sur la grille
	  var curWeek_end = $('#calendar').fullCalendar('getView').end.format('YYYY-MM-DD HH:mm:ss');
	  document.getElementById('save_names').style.visibility='visible';
	  $.ajax({
		   url: "./jquery-ui/fullcalendar/set_etudiants_tuteur.php",
		   type: 'POST',
		   data: 'idCalendar='+$('#calendar_id').val()+'&curWeek_start='+curWeek_start+'&curWeek_end='+curWeek_end+'&nom_etud='+$('#nom_etud').val()+'&nom_tuteur='+$('#nom_tuteur').val(),

		   success: function(){
				$("#edit_names").show();
				MAJ_names();
			 },
			 error: function (request, status, error) {
				alert("ko");
			 }
			
		});
});	
		

	
// Fonction de mise à jour des inputs 'Eudiants' et 'Tuteur'
function MAJ_names(){
		var curWeek_start = $('#calendar').fullCalendar('getView').start.format('YYYY-MM-DD HH:mm:ss');
		var curWeek_end = $('#calendar').fullCalendar('getView').end.format('YYYY-MM-DD HH:mm:ss');

		$.ajax({
				url: './jquery-ui/fullcalendar/get_etudiants_tuteur.php',     
				data: 'idCalendar='+ $('#calendar_id').val()+'&curWeek_start='+curWeek_start+'&curWeek_end='+curWeek_end,		
				type: "POST",
				success: function(data) {
					if(data.length==0){
						$("#nom_tuteur").val("");
						$("#nom_etud").val("");
						
						$("#nom_tuteur").prop('disabled', false);	
						$("#nom_etud").prop('disabled', false);	
						
						$("label[for='tuteur']").hide();
						$("label[for='etudiants']").hide();
						document.getElementById('show_names').style.visibility='hidden';
						document.getElementById('edit_names').style.visibility='hidden';				
						document.getElementById('save_names').style.visibility='hidden';				
					}else{
						$("#nom_tuteur").val(data['tuteur']);
						$("#nom_etud").val(data['etudiants']);
						$("#nom_tuteur").attr('disabled', true);	// si les noms ont déjà été fixés les inputs	
						$("#nom_etud").attr('disabled', true);		// sont en readonly
						

						$("label[for='tuteur']").text("Tuteur : "+data['tuteur']);
						$("label[for='etudiants']").text("Etudiants : "+data['etudiants']);
						$("label[for='tuteur']").show();
						$("label[for='etudiants']").show();
						document.getElementById('show_names').style.visibility='visible';
	
						if(document.getElementById('edit_calendar').style.visibility=='hidden'){
							document.getElementById('edit_names').style.visibility='hidden';
						}else{
							document.getElementById('edit_names').style.visibility='visible';
							document.getElementById('save_names').style.visibility='hidden';
						}
					}
				 }
		});
		getPermissions();					// affiche ou non la partie droite d'édition du calendrier

}  ///////// fin fonction



	if(c_name=='Calendrier T\351l\351phone GTC'){
		options.weekends=true;
		options.header.right='month,agendaWeek';
		options.defaultView='month';
		options.eventClick=function(calEvent, jsEvent, view) {
										if(calEvent.title=='Jour f\u00e9ri\u00e9' || canModify==false){
											// rien ----> jour férié ou lecture seul
											return;
										}
										$("#datepicker_start2").datepicker({dateFormat: "yy-mm-dd"});
										$("#datepicker_end2").datepicker({dateFormat: "yy-mm-dd"});
										
										// Pré-remplir les champs
										//$("select[name='membre_nom'] option:selected").text(calEvent.title);
										//$("#membre_nom option:contains(" + calEvent.title + ")").attr('selected', 'selected');
										$("#membre_nom option").filter(function () { return $(this).html() == calEvent.title; }).prop('selected', true);
										//alert(calEvent.title);
										
										$("#datepicker_start2").datepicker("setDate", new Date(calEvent.start));
										$("#datepicker_end2").datepicker("setDate", new Date(calEvent.end));	 
										
										$("select[name='colorpicker2']").val(calEvent.backgroundColor);
										$("#eventContent_GTC" ).dialog({
											resizable: true,
											height:600,
											width:600,
											modal: true,
											buttons: {
														"Sauvegarder": function() {
															var title =$("select[name='membre_nom'] option:selected").text();
															var start =$("#datepicker_start2").val()+" "+moment(calEvent.start).format("HH:mm:ss");
															var end =$("#datepicker_end2").val()+" "+moment(calEvent.end).format("HH:mm:ss");
															var color=$("select[name='colorpicker2']").val();
													
															if(start>end){
																alert("Date invalide, saisissez une date de fin post\351rieure \340 la date de d\351but");
															}else{
																 $.ajax({
																	 url: './jquery-ui/fullcalendar/update_events.php',
																	 data: 'title='+ title+'&start='+ start +'&end='+ end +'&id='+ calEvent.id +'&backgroundColor='+color ,
																	 type: "POST",
																	 success: function(json) {
																			calendar.fullCalendar('removeEvents');          // supprimer tous les events  
																			calendar.fullCalendar('refetchEvents');			// pour les recharger
																		}
																	});														
																$( this ).dialog( "close" );
															}
														 },
														 "Annuler": function() {
															$( this ).dialog( "close" );
														 },
														 "Supprimer": function() {
															 $.ajax({
																 url: './jquery-ui/fullcalendar/delete_event.php',
																 data: '&idEvent='+ calEvent.id,
																 type: "POST",
																 success: function(json) {
																		calendar.fullCalendar('removeEvents');          // supprimer tous les events  
																		calendar.fullCalendar('refetchEvents');			// pour les recharger
																	}
															 });
															$( this ).dialog( "close" );
														 }
													 }
												 
										});				
								};
		$('#edit_calendar').hide();
		$("label[for='tuteur']").hide();
		$("label[for='etudiants']").hide();
		$('#stage_div').hide();
		$('#apply_mois_type').show();
	}
	else if(c_name=='Calendrier de gestion des stagiaires'){
		options.weekends=false;
		$('#stage_div').show();
		$("label[for='tuteur']").show();
		$("label[for='etudiants']").show();		
		$('#edit_calendar').show();
		$('#apply_mois_type').hide();
	}
	else{
		options.weekends=true;
		$('#stage_div').hide();
		$("label[for='tuteur']").hide();
		$("label[for='etudiants']").hide();
		$('#apply_mois_type').hide();
	}
	getPermissions();	
	var calendar = $('#calendar').fullCalendar(options);

	

	
});	//fin de document ready function


//	Détermine si l'utilisateur a le droit de modifier le calendrier vu
function getPermissions()
{
			
			$.ajax({
					url: './jquery-ui/fullcalendar/get_autorisations.php',
					data: 'user='+user+'&idCalendar='+$('#calendar_id').val(),
					type:"POST",
					dataType : "json",
					success  : function(data) {  
								//   alert(data[0].canModify);  
								  // var user = data[0].user;
								  
								 if(data.length != 0){
									// alert(data);
								   canModify = data[0].canModify;  
								   if(canModify==false){
										$('#edit_calendar').hide();
										//canModify = 0;
									}else{
										$('#edit_calendar').show();
										//canModify = 1;
									}
									return canModify;
								}
					}  
				});
				
				
}



