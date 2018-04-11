//var idCalendar=$('#calendar_id').val();
var curSource="jquery-ui/fullcalendar/events.php?idCalendar="+idCalendar;
var options;
	
$(document).ready(function(){
	
	
	options = {      /* 	********	config du calendar		********	*/
		lang: 'fr',
		height: 600,
		defaultView:'month', // par défaut on affiche un agenda semaine
		header:{ // Mise en forme de l en tête
			left:'prev,today,next', // à gauche: les boutons de navigation
			center:'title', // au milieu: le titre
			right:'month,basicWeek,agendaDay' // à droite: les boutons de type de vue
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
	   editable:false, 		// droit de modifier les évenements déjà créés 
	   selectable:false,		   //permet de créer des évènements directement sur la grille
	//   eventLimit:true,     //permet d'afficher "more" s'il y a trop d'events sur un jour
	   selectHelper:true,
	   minTime: "07:00:00",
	   maxTime: "20:00:00",
	   weekends: false,  //show_weekends,
	   textColor: 'black',
	   allDaySlot:false,
	   height: 660,

	   // Fonction permettant l'instanciation des évènements 
	 eventRender: function(event, element) {
			var start_hour=event.start.format('HH:MM').toString();
			start_hour=start_hour.split(":");
			start_hour=start_hour[0];
			if(start_hour=="00"){
				element.find('.fc-time').hide(); 	// cacher l'heure de début dans la vue d'un évènement 
			}
			else if( event.title=='Jour f\u00e9ri\u00e9'){
			     element.find('.fc-time').hide(); 	// cacher l'heure de début  
			     element.find('.fc-title').append("<br/>" + event.description);    
			}/*else{
				 element.find('.fc-title').append("<br/>Intervenant:  " + event.intervenant); // ajouter dans la vue de l'event
				 element.find('.fc-title').append("<br/>Lieu:  " +event.lieu); 
			}*/
        }
	 
     
 }         //fin du tableau options


	var calendar = $('#calendar').fullCalendar(options);

	 
});



