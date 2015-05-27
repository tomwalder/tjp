<?php
/**
 * Retrieve train data
 *
 * @author Tom Walder <tom@docnet.nu>
 */
date_default_timezone_set('Europe/London');
require_once('../vendor/autoload.php');
require_once('../config.php');

// Empty response
$obj_response = (object)[
    'success' => FALSE,
    'services' => []
];

// Get the CRS inputs from GET or POST
function getCrs($str_handle, $str_default)
{
    if(isset($_GET[$str_handle])) {
        $str_val = trim($_GET[$str_handle]);
        if(3 == strlen($str_val)) {
            return strtoupper($str_val);
        }
    }
    if(isset($_POST[$str_handle])) {
        $str_val = trim($_POST[$str_handle]);
        if(3 == strlen($str_val)) {
            return strtoupper($str_val);
        }
    }
    return $str_default;
}

// Get from & to
$str_from = getCrs('from', 'WML');
$str_to = getCrs('to', 'MAN');

try {

    $obj_client = new \TJP\Client(LDBWS_ACCESS_TOKEN);
    $obj_travel_result = $obj_client->fromTo($str_from, $str_to);

    $obj_response->from = $str_from;
    $obj_response->to = $str_to;

    // Show them
    $obj_board_results = $obj_travel_result->GetStationBoardResult;
    foreach ($obj_board_results->trainServices->service as $obj_service) {

        $obj_detail = $obj_client->serviceDetailsExtra($obj_service->serviceID, $str_from, $str_to);
        $obj_detail_result = $obj_detail->raw;

        $obj_response->services[] = [
            'std' => $obj_service->std,
            'etd' => $obj_service->etd,
            'ontime' => ('On time' === $obj_service->etd),
            'operator' => operatorMap($obj_service->operator),
            'origin_crs' => $obj_service->origin->location->crs,
            'dest_crs' => $obj_service->destination->location->crs,
            'platform' => isset($obj_detail_result->platform) ? $obj_detail_result->platform : NULL,
            'stops' => $obj_detail->stops,
            'duration' => $obj_detail->duration_mins
        ];
    }
    $obj_response->success = TRUE;
} catch (\Exception $obj_ex) {
    syslog(LOG_ERR, $obj_ex->getMessage());
    $obj_response->error = $obj_ex->getMessage();
    http_response_code(500);
}

function operatorMap($str) {
    $arr_map = [
        'Arriva Trains Wales' => 'Arriva Wales'
    ];
    return isset($arr_map[$str]) ? $arr_map[$str] : $str;
}

header('Content-type: application/json');
echo json_encode($obj_response);