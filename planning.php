<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 14/05/2018
 * Time: 23:08
 */

    session_start();

    // liste des événements
    $json = array();
    // requête qui récupère les événements
    $requete = "SELECT * FROM evenement WHERE id_utilisateur = ".$_SESSION['id']." ORDER BY id";

    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    // exécution de la requête
    $resultat = $bdd->query($requete) or die(print_r($bdd->errorInfo()));

    // envoi du résultat au success
    $events = json_encode($resultat->fetchAll(PDO::FETCH_ASSOC));
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8' />
    <!-- Bootstrap core CSS-->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <link href='fullcalendar/fullcalendar.min.css' rel='stylesheet' />
    <link href='fullcalendar/fullcalendar.print.min.css' rel='stylesheet' media='print' />
    <script src='fullcalendar/lib/moment.min.js'></script>
    <script src='fullcalendar/lib/jquery.min.js'></script>
    <script src='fullcalendar/fullcalendar.min.js'></script>
    <script>

        $(document).ready(function() {

            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay,listWeek'
                },
                defaultDate: '2018-05-14',
                editable: false,
                navLinks: false, // can click day/week names to navigate views
                eventLimit: true, // allow "more" link when too many events
                events: <?php echo $events; ?>,
                selectable: false,
                selectHelper: false,
                /*select: function(start, end, allDay) {
                    var title = prompt('Event Title:');
                    if (title) {
                        start = $.fullCalendar.formatDate(start, "yyyy-MM-dd HH:mm:ss");
                        end = $.fullCalendar.formatDate(end, "yyyy-MM-dd HH:mm:ss");
                        $.ajax({
                            url: 'http://localhost/medinurse/add_events.php',
                            data: 'title='+ title+'&start='+ start +'&end='+ end ,
                            type: "POST",
                            success: function(json) {
                                alert('OK');
                            }
                        });
                        calendar.fullCalendar('renderEvent',
                            {
                                title: title,
                                start: start,
                                end: end,
                                allDay: allDay
                            },
                            true // make the event "stick"
                        );
                    }
                    calendar.fullCalendar('unselect');
                },
                eventDrop: function(event, delta) {
                    start = $.fullCalendar.formatDate(event.start, "yyyy-MM-dd HH:mm:ss");
                    end = $.fullCalendar.formatDate(event.end, "yyyy-MM-dd HH:mm:ss");
                    $.ajax({
                        url: 'http://localhost/fullcalendar/update_events.php',
                        data: 'title='+ event.title+'&start='+ start +'&end='+ end +'&id='+ event.id ,
                        type: "POST",
                        success: function(json) {
                            alert("OK");
                        }
                    });
                },
                eventResize: function(event) {
                    start = $.fullCalendar.formatDate(event.start, "yyyy-MM-dd HH:mm:ss");
                    end = $.fullCalendar.formatDate(event.end, "yyyy-MM-dd HH:mm:ss");
                    $.ajax({
                        url: 'http://localhost/fullcalendar/update_events.php',
                        data: 'title=' + event.title + '&start=' + start + '&end=' + end + '&id=' + event.id,
                        type: "POST",
                        success: function (json) {
                            alert("OK");
                        }
                    });
                },*/
                loading: function(bool) {
                    $('#loading').toggle(bool);
                }
            });

        });

    </script>
    <style>

        body {
            margin: 0;
            padding: 0;
            font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
            font-size: 14px;
        }

        #script-warning {
            display: none;
            background: #eee;
            border-bottom: 1px solid #ddd;
            padding: 0 10px;
            line-height: 40px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            color: red;
        }

        #loading {
            display: none;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        #calendar {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 10px;
        }

        .center-block{
            margin-left: auto;
            margin-right: auto;
            display: block;
            width: 200px;
            margin-bottom: 50px;
        }

    </style>
</head>
    <body>
        <div id='script-warning'>
            <code>php/get-events.php</code> must be running.
        </div>

        <div id='loading'>loading...</div>

        <div id='calendar'></div>

        <a href="index.php" class="btn btn-primary btn-info center-block">Retour à Medinurse</a>
    </body>
</html>
