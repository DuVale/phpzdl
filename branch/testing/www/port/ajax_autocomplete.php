<?
require_once ('classes/Security.inc');
require_once ('classes/Port.inc');
require_once ('ossim_db.inc');


$search = GET('q');
$limit      = GET('limit');
$protocol   = GET('protocol');

ossim_valid($search    , OSS_NOECHARS, OSS_ALPHA, OSS_SCORE, OSS_PUNC, 'illegal:' . _("search"));
ossim_valid($limit     , OSS_DIGIT                                   , 'illegal:' . _("limit"));
ossim_valid($protocol  , OSS_LETTER                                  , 'illegal:' . _("protocol"));

if (ossim_error()) { 
    die();
}

//create filter and order
$where = "where service like '%" . $search . "%' and protocol_name = '" . $protocol . "'";
$order = "order by service limit " .$limit;

// connect to database
$db        = new ossim_db();
$conn      = $db->connect();

// search ports
$ports = Port::get_list($conn, $where, $order);

$db->close($conn);

foreach($ports as $port)
    echo($port->get_service()."\n");

?>