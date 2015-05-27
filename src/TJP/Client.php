<?php
/**
 * LDBWS Client
 *
 * @author Tom Walder <tom@docnet.nu>
 */
namespace TJP;

class Client
{

    /**
     * @var null|\SoapClient
     */
    private $obj_soap_client = NULL;

    /**
     * @param $str_token
     */
    public function __construct($str_token)
    {
        $this->obj_soap_client = new \SoapClient("https://lite.realtime.nationalrail.co.uk/OpenLDBWS/wsdl.aspx?ver=2014-02-20", [
            "trace" => FALSE,
            "compression" => SOAP_COMPRESSION_ACCEPT|SOAP_COMPRESSION_GZIP
        ]);

        // Auth header
        $this->obj_soap_client->__setSoapHeaders(
            new \SoapHeader('http://thalesgroup.com/RTTI/2010-11-01/ldb/commontypes', 'AccessToken', new \SoapVar([
                'ns2:TokenValue' => $str_token
            ], SOAP_ENC_OBJECT), FALSE)
        );
    }

    /**
     * @param $str_from
     * @param null $str_to
     * @param int $int_offset
     * @return mixed
     */
    public function toFrom($str_from, $str_to = NULL, $int_offset = 0)
    {
        $arr_params = [
            'crs' => $str_to,
            'numRows' => 20,
        ];
        if(NULL !== $str_to) {
            $arr_params['filterCrs'] = $str_from;
            $arr_params['filterType'] = 'from';
            $arr_params['timeOffset'] = $int_offset; // between -120 and 2880 exclusive, default 0
            // $arr_params['timeWindow'] = 120; // between -120 and 120 exclusive, default 120
        }

        // Make request
        $obj_response = $this->obj_soap_client->GetArrivalBoard($arr_params);

        return $obj_response;
    }


    public function fromTo($str_from, $str_to = NULL, $int_offset = 0)
    {
        $arr_params = [
            'crs' => $str_from,
            'numRows' => 20,
        ];
        if(NULL !== $str_to) {
            $arr_params['filterCrs'] = $str_to;
            $arr_params['filterType'] = 'to';
            $arr_params['timeOffset'] = $int_offset; // between -120 and 2880 exclusive, default 0
            // $arr_params['timeWindow'] = 120; // between -120 and 120 exclusive, default 120
        }

        // Make request
        $obj_response = $this->obj_soap_client->GetDepartureBoard($arr_params);

        return $obj_response;
    }

    public function serviceDetails($str_service_id)
    {
        $obj_response = $this->obj_soap_client->GetServiceDetails([
            'serviceID' => $str_service_id
        ]);
        return $obj_response;
    }

    public function serviceDetailsExtra($str_service_id, $str_from = NULL, $str_to = NULL)
    {
        $obj_response = $this->serviceDetails($str_service_id);
        $obj_detail_result = $obj_response->GetServiceDetailsResult;

        $str_depart_hhmm = NULL;
        if(isset($obj_detail_result->atd) && preg_match('#\d\d:\d\d#', $obj_detail_result->atd)) {
            $str_depart_hhmm = $obj_detail_result->atd;
        } elseif (isset($obj_detail_result->etd) && preg_match('#\d\d:\d\d#', $obj_detail_result->etd)) {
            $str_depart_hhmm = $obj_detail_result->etd;
        } elseif (isset($obj_detail_result->std) && preg_match('#\d\d:\d\d#', $obj_detail_result->std)) {
            $str_depart_hhmm = $obj_detail_result->std;
        }
        if(NULL === $str_depart_hhmm) {
            throw new \Exception("Could not determine departure time");
        }
        $str_depart = date("Y-m-d ") . $str_depart_hhmm . ":00";
        $obj_depart = new \DateTime($str_depart);

        // Stops & last stop
        $int_stops = 0;
        $str_last_stop_hhmm = '';
        if(isset($obj_detail_result->subsequentCallingPoints) && isset($obj_detail_result->subsequentCallingPoints->callingPointList) && isset($obj_detail_result->subsequentCallingPoints->callingPointList->callingPoint)) {
            foreach ($obj_detail_result->subsequentCallingPoints->callingPointList->callingPoint as $obj_stop) {
                $int_stops++;
                $str_last_stop_hhmm = isset($obj_stop->at) ? $obj_stop->at : $obj_stop->st; // scheduled time
                if($str_to == $obj_stop->crs) {
                    break;
                }
            }
        }
        $str_last_stop = date("Y-m-d ") . $str_last_stop_hhmm . ":00";
        $obj_last_stop = new \DateTime($str_last_stop);

        // Duration
        $obj_diff = $obj_depart->diff($obj_last_stop);

        return (object)[
            'raw' => $obj_detail_result,
            'stops' => $int_stops,
            'depart' => $str_depart,
            'last_stop' => $str_last_stop,
            'duration_mins' => $obj_diff->i
        ];

    }

}