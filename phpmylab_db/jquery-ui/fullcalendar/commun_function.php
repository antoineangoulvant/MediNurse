<?php


  if (!function_exists('json_encode'))
  {
    function json_encode($a=false)
    {
      if (is_null($a)) return 'null';
      if ($a === false) return 'false';
      if ($a === true) return 'true';
      if (is_scalar($a))
      {
        if (is_float($a))
        {
          // Always use "." for floats.
          return floatval(str_replace(",", ".", strval($a)));
        }
   
        if (is_string($a))
        {
          static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
          return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
        }
        else
          return $a;
      }
      $isList = true;
      for ($i = 0, reset($a); $i < count($a); $i++, next($a))
      {
        if (key($a) !== $i)
        {
          $isList = false;
          break;
        }
      }
      $result = array();
      if ($isList)
      {
        foreach ($a as $v) $result[] = json_encode($v);
        return '[' . join(',', $result) . ']';
      }
      else
      {
        foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
        return '{' . join(',', $result) . '}';
      }
    }
  }

  function db_connect() {
      global $mysql_location,$mysql_user,$mysql_password,$mysql_base;
      static $connection;

      if(!isset($connection)) {
          $connection = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
      }

      if($connection === false) {
          return mysqli_connect_error(); 
      }
      return $connection;
  }
   
  function db_error() {
      $connection = db_connect();
      return mysqli_error($connection);
  }

  function canUserReadTemplateWeek($user, $idTemplateWeek) {
    $connection = db_connect();
    
    //TODO
    $idCalendar=false;
    $SelectIdCalendar = "SELECT id_calendar 
      FROM T_SEMAINE_TYPE 
      WHERE  id_semaine_type = ?";
  
    $stmt = mysqli_stmt_init($connection);

    if (mysqli_stmt_prepare($stmt, $SelectIdCalendar)) {
        mysqli_stmt_bind_param($stmt, 'i', $idTemplateWeek);

        if ( !mysqli_stmt_execute($stmt) ) {
          echo "Echec lors de l'exécution : (" . $stmt->errno . ") " . $stmt->error;
          return false;
        }

        mysqli_stmt_bind_result($stmt, $idCalendar);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // check user can read this calendar
    if($idCalendar){
      if(canUserWriteCalendar($user, $idCalendar)){
        return true;
      }
    }
    return false;
  }
    
  function canUserReadCalendar($user, $calendar) {
    $connection = db_connect();

    $requete = "SELECT utilisateur, id_calendar_view 
      FROM T_CALENDAR_AUTORISATIONS  
      WHERE  utilisateur = ?
      AND id_calendar_view = ? ";
  
    $prepa = mysqli_stmt_init($connection);
    mysqli_stmt_prepare($prepa, $requete);
    mysqli_stmt_bind_param($prepa, 'si', $user, $calendar);

    if( mysqli_stmt_execute($prepa) ) {
      mysqli_stmt_store_result($prepa);
      if (mysqli_stmt_num_rows($prepa) > 0) {
        mysqli_stmt_close($prepa);
        return true;
      }
    }
    mysqli_stmt_close($prepa);
    return false;
  }
  
  function canUserUpdateCalendar($user_id, $idEvent) {
    $connection = db_connect();

    $requete = "SELECT id_event, id_calendar
      FROM T_EVENT  
      WHERE id_event = ? ";
  
    $prepa = mysqli_stmt_init($connection);
    mysqli_stmt_prepare($prepa, $requete);
    mysqli_stmt_bind_param($prepa, 'i', $idEvent);
    
    if( mysqli_stmt_execute($prepa) ) {
      mysqli_stmt_store_result($prepa);
      if (mysqli_stmt_num_rows($prepa) > 0) {
        $id_calendar=false;
        mysqli_stmt_bind_result($prepa, $idEvent, $id_calendar);
        mysqli_stmt_fetch($prepa);
        
        if($id_calendar) {
          if(canUserWriteCalendar($user_id, $id_calendar)) {
            mysqli_stmt_close($prepa);
            return true;
          }
        }
      }
    }
    mysqli_stmt_close($prepa);
    return false;
  }
  
  function canUserWriteCalendar($user, $calendar) {
    $connection = db_connect();

    $requete = "SELECT utilisateur, id_calendar_view, can_modify_calendar
      FROM T_CALENDAR_AUTORISATIONS  
      WHERE  utilisateur = ?
      AND id_calendar_view = ? ";
  
    $prepa = mysqli_stmt_init($connection);
    mysqli_stmt_prepare($prepa, $requete);
    mysqli_stmt_bind_param($prepa, 'si', $user, $calendar);

    if( mysqli_stmt_execute($prepa) ) {
      mysqli_stmt_store_result($prepa);
      if (mysqli_stmt_num_rows($prepa) > 0) {
        
        $canModifyCalendar=false;
        mysqli_stmt_bind_result($prepa, $user, $calendar, $canModifyCalendar);
        mysqli_stmt_fetch($prepa);
        
        if($canModifyCalendar) {
          mysqli_stmt_close($prepa);
          return true;
        }
      }
    }
    mysqli_stmt_close($prepa);
    return false;
  }

  function updateWeekModel($weekId, $weekModelName){
    $connection = db_connect();

    $requete = "UPDATE T_SEMAINE_TYPE SET nom_semaine_type=?
                WHERE id_semaine_type=?";

    $prepa = mysqli_stmt_init($connection);
    mysqli_stmt_prepare($prepa, $requete);
    mysqli_stmt_bind_param($prepa, 'si', $weekModelName, $weekId);

    if( mysqli_stmt_execute($prepa) ) {
      mysqli_stmt_close($prepa);
      return true;
    }
    mysqli_stmt_close($prepa);
    return false;
  }

  function createWeekModel($calendar, $weekModelName) {
    $connection = db_connect();

    $requete = "INSERT INTO T_SEMAINE_TYPE (id_calendar, nom_semaine_type)
      VALUES(?,?)";

    $prepa = mysqli_stmt_init($connection);
    mysqli_stmt_prepare($prepa, $requete);
    mysqli_stmt_bind_param($prepa, 'is', $calendar, $weekModelName);

    if( mysqli_stmt_execute($prepa) ) {
      mysqli_stmt_close($prepa);
      return true;
    }
    mysqli_stmt_close($prepa);
    return false;
  }

  function deleteWeekModel($weekId) {
    $connection = db_connect();

    $requete = "DELETE FROM T_SEMAINE_TYPE
      WHERE id_semaine_type=?";

    $prepa = mysqli_stmt_init($connection);
    mysqli_stmt_prepare($prepa, $requete);
    mysqli_stmt_bind_param($prepa, 'i', $weekId);
    
    if( mysqli_stmt_execute($prepa) ) {
      mysqli_stmt_close($prepa);
      return true;
    }
    mysqli_stmt_close($prepa);
    return false;
  }
?>
