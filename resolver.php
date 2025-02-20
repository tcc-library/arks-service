<html>
<head>
  <style>
    .loader{
      position: fixed;
      left: 0px;
      top: 0px;
      width: 100%;
      height: 100%;
      z-index: -1;
      background: url('/front/images/loading.gif')
      50% 50% no-repeat rgb(249,249,249);
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
<!--<div class="loader"></div>-->
</body>
</html>

<?php
require_once 'config/MysqlArkConf.php';

use Noid\Config\MysqlArkConf;

if (strpos($_SERVER['REQUEST_URI'], "/ark:/") === 0 || strpos($_SERVER['REQUEST_URI'], "/ark:") === 0) {
  // processing the Ark URL
  $params = str_replace("ark:/", "", $_GET['q']);
  $parts = array_filter(explode("/", $params));

  // get Ark ID
  $arkid = $parts[0] . '/'. $parts[1];
  if (strpos($arkid, "ark:") === 0) {
    $arkid = str_replace("ark:", "", $arkid);
  }
  
  // get all database Ark related
  $arkdbs = showArkDatabases();
  // only proceed if already have ark_core db
  if (is_array($arkdbs) && count($arkdbs) > 0) {
    $url = "/404.php";

    // loop through database and find matching one with prefix
    foreach ($arkdbs as $db) {
      // if ark ID found, look for URL fields first.

      // if there is Qualifier, look up for the qualifier, ignore the ark id
      if (count($parts) > 2) {
        // establish qualifier from Ark URL
        $total = count($parts);
        $qualifier = "";
        for ($i = 2; $i < $total; $i++) {
          $qualifier .= $parts[$i];
          if ($i != $total - 1) {
            $qualifier .= "/";
          }
        }

        // looking up
        $result = lookup($db, $arkid, strtoupper($qualifier));
        if (!empty($result)) {
          $url = $result;
          break;
        }
      }

      $result = lookup($db, $arkid, "URL");
      if (!empty($result)) {
        // found URL field bound associated with the ark id
        $url = $result;

        // TODO: add a counter here
          increase_reidrection($db, $arkid);
        break;
      }
    }

    // exclusive for UTSC, may removed
    if ($url === "/404.php") {
      // not found URL, get PID and established the URL
      foreach ($arkdbs as $db) {
        // if ark ID found, look for URL fields first.
        $pid = lookup($db, $arkid, "PID");
        if (!empty($pid)) {
          // found URL field bound associated with the ark id
          $dns = getNAA($db);
          $url = "https://$dns/islandora/object/" . $pid;

          // TODO: add a counter here
          increase_reidrection($db, $arkid);
          break;
        }
      }
    }
    // New: Add a check for ? or ?? and the end of Ark URL
    if ( substr_compare($_SERVER['REQUEST_URI'], "?", -1) === 0 ) {
      // if the Ark URLs ends with '?'
      $medata = getMetdata($db, $arkid);
      print($medata);
    }
    else if ( substr_compare($_SERVER['REQUEST_URI'], "??", -2) === 0 ) {
      $medata = getMetdata($db, $arkid);
      print($medata);
    }
    else {
      header("Location: $url");
    }
  }
} else {
  print "invalid argument";
}

/**
 *  Counting redirection
 */
function increase_reidrection($db, $ark_id) {
  
// TODO UPDATE REDIRECTION COUNT HERE
  $link = mysqli_connect(MysqlArkConf::$mysql_host, MysqlArkConf::$mysql_user, MysqlArkConf::$mysql_passwd, MysqlArkConf::$mysql_dbname);

  if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
  }
  
  // get existed redirection count.
  $count = lookup($db, $ark_id, "REDIRECT");
  if ($count == false) { 
    $count = 1;
    // do insert
    $query = "INSERT INTO `$db` (_key, _value) VALUES('$ark_id REDIRECT', $count)";
  }
  else {
    $where = 'WHERE _key regexp "(^|[[:space:]])'.$ark_id.'([[:space:]])REDIRECT$"';
    $count++;
    // do update
    $query = "UPDATE `$db` SET _value = $count ". $where;
  }

  $count++;
  if (mysqli_query($link, $query)) {
    #print_log("New record created successfully");
  }
  else {
    #print_log("New record created failed"); 
  }
  mysqli_close($link);
}


/**
 * Get full metadata
 */
function getMetdata($db, $ark_id)
{
  $link = mysqli_connect(MysqlArkConf::$mysql_host, MysqlArkConf::$mysql_user, MysqlArkConf::$mysql_passwd, MysqlArkConf::$mysql_dbname);

  if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
  }

  $where = 'WHERE _key LIKE "' . $ark_id .'%"';
  if ($query = mysqli_query($link, "SELECT *  FROM `$db` ". $where)) {

    if (!mysqli_query($link, "SET @a:='this will not work'")) {
      printf("Error: %s\n", mysqli_error($query));
    }
    $results = $query->fetch_all();

    if (count($results) > 0) {
      $medata = "<pre>";
      foreach($results as $pair) {
        $field = trim(str_replace($ark_id, " ", $pair[0])) ;
        if (!in_array($field, [':/c', ":/h", "REDIRECT", ""])) {
          $medata .= $field. ": " . $pair[1] . "\n";
        }
      }
      $medata .= "</pre>";
      return $medata;
    }

    $query->close();
  }
  mysqli_close($link);
  return false;
}


/**
 * Get Org registered info
 */
function lookup($db, $ark_id, $field = "")
{
  $link = mysqli_connect(MysqlArkConf::$mysql_host, MysqlArkConf::$mysql_user, MysqlArkConf::$mysql_passwd, MysqlArkConf::$mysql_dbname);

  if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
  }
  $where = 'where _key regexp "(^|[[:space:]])'.$ark_id.'([[:space:]])'.$field.'$"';

  if ($query = mysqli_query($link, "SELECT  _value FROM `$db` ". $where)) {

    if (!mysqli_query($link, "SET @a:='this will not work'")) {
      printf("Error: %s\n", mysqli_error($query));
    }
    $results = $query->fetch_all();

    if (count($results) > 0) {
      return $results[0][0];
    }

    $query->close();
  }
  mysqli_close($link);
  return false;
}

function getNAA($db) {
  $link = mysqli_connect(MysqlArkConf::$mysql_host, MysqlArkConf::$mysql_user, MysqlArkConf::$mysql_passwd, MysqlArkConf::$mysql_dbname);

  if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
  }
  $where = "where _key = ':/naa'";


  if ($query = mysqli_query($link, "SELECT * FROM `$db` ". $where)) {

    if (!mysqli_query($link, "SET @a:='this will not work'")) {
      printf("Error: %s\n", mysqli_error($query));
    }
    $results = $query->fetch_all();
    if (count($results) > 0) {
      return $results[0][1];
    }

    $query->close();
  }
  mysqli_close($link);
  return false;
}

/**
 * list all Arrk database
 * @param string $name
 */
function showArkDatabases()
{
  $link = mysqli_connect(MysqlArkConf::$mysql_host, MysqlArkConf::$mysql_user, MysqlArkConf::$mysql_passwd, MysqlArkConf::$mysql_dbname);

  if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
  }

  if ($query = mysqli_query($link, "SHOW TABLES")) {
    if (!mysqli_query($link, "SET @a:='this will not work'")) {
      printf("Error: %s\n", mysqli_error($query));
    }
    $results = $query->fetch_all();
    $arkdbs = [];
    foreach ($results as $db) {
      // It starts with 'http'
      if (!in_array($db[0], ['system', 'user'])) {
        array_push($arkdbs, $db[0]);
      }
    }
    $query->close();
  }
  mysqli_close($link);
  return $arkdbs;
}
