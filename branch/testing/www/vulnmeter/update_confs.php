<?php
require_once ('classes/OMP.inc');
    $omp = new OMP();
$omp->update_preferences_sqlite("11735473-90f8-4645-9146-9d706d27a7ee","PLUGINS_PREFS","SSH Authorization[entry]:SSH login name:","joseang");
/*
$pref_name  = "plugins_timeoutsssss";
$pref_value = "111";
$uuid       = "11735473-90f8-4645-9146-9d706d27a7ee";
$category   = "PLUGINS_PREFS";

$sqlite = new SQLITE("/tmp/tasks.db");

$sqlite->connect_db();

// get "config id" from configs table

$result    = $sqlite->execute_db("SELECT id FROM configs WHERE uuid='".$uuid."'");
$row       = $sqlite->fetch_row_db($result);
$config_id = $row["id"];

// get preference id (id field)from config to modify the preference

$result = $sqlite->execute_db("SELECT id FROM config_preferences WHERE config ='".$config_id."' AND name='".$pref_name."'");
$row = $sqlite->fetch_row_db($result);
$preference_id = $row["id"];

// update if preference exists
if($preference_id != "") {
    $sqlite->execute_db("UPDATE config_preferences SET value = '".$pref_value."' where config = '".$config_id."' AND name = '".$pref_name."'");
}
else { // else insert
    $result = $sqlite->execute_db("SELECT MAX(id)+1 as next_id FROM config_preferences");
    $row = $sqlite->fetch_row_db($result);
    $next_id = $row["next_id"];
    
    $sqlite->execute_db("INSERT INTO config_preferences (id, config, type, name, value) VALUES ('".$next_id."', '".$config_id."', '".$category."', '".$pref_name."', '".$pref_value."')");
}
    */
?>