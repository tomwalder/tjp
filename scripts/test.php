<?php
/**
 * Test script
 *
 * @author Tom Walder <tom@docnet.nu>
 */
date_default_timezone_set('Europe/London');
require_once('../vendor/autoload.php');
require_once('../config.php');

$obj_client = new \TJP\Client(LDBWS_ACCESS_TOKEN);

$str_from = isset($argv[1]) ? $argv[1] : 'WML';
$str_to = isset($argv[2]) ? $argv[2] : 'MAN';
$int_offset = isset($argv[3]) ? $argv[3] : 0;

echo PHP_EOL, "[{$str_from}-{$str_to}:{$int_offset}]", PHP_EOL;

cliShow($obj_client->fromTo($str_from, $str_to, $int_offset), $obj_client, $str_from, $str_to);
// cliShow($obj_client->toFrom($str_from, $str_to, $int_offset), $obj_client);
// cliShow($obj_client->fromTo('WML', 'MAN'), $obj_client);

// Output
function cliShow($obj_response, $obj_client, $str_from, $str_to)
{
    echo '====', PHP_EOL;
    // print_r($obj_response);
    // return;
    $obj_board_results = $obj_response->GetStationBoardResult;
    foreach ($obj_board_results->trainServices->service as $obj_service) {

        echo implode(', ', [
            $obj_service->std,
            $obj_service->etd,
            $obj_service->origin->location->crs,
            $obj_service->destination->location->crs,
            $obj_service->operator
        ]), PHP_EOL;

        print_r($obj_service->serviceID);

        $obj_detail_result = $obj_client->serviceDetailsExtra($obj_service->serviceID, $str_from, $str_to);

        print_r($obj_detail_result);

        // exit after first record
        exit();
    }
}
