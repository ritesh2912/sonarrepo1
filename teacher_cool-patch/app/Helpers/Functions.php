<?php

/**
 * Success response method
 *
 * @param $result
 * @param $message
 * @return \Illuminate\Http\JsonResponse
 */
function sendResponse($result, $message='Success')
{
    $response = [
        'success' => true,
        'data'    => $result,
        'message' => $message,
    ];

    return response()->json($response, 200);
}

/**
 * Return error response
 *
 * @param       $error
 * @param array $errorMessages
 * @param int   $code
 * @return \Illuminate\Http\JsonResponse
 */
function sendError($error, $errorMessages = [], $code = 404)
{
    $response = [
        'success' => false,
        'error' => $error,
    ];

    !empty($errorMessages) ? $response['data'] = $errorMessages : null;

    return response()->json($response, $code);
}

function getString($n)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }
    
    return $randomString;
}

function convertFromUTCtime($time, $timeZone){
    // timezone by php friendly values
    $fromTz = date_default_timezone_get();
    $date = new DateTime($time, new DateTimeZone($fromTz));
    $date->setTimezone(new DateTimeZone($timeZone));
    $time= $date->format('Y-m-d H:i:s');
    return $time;
}

function convertToUTCtime($time, $fromTimeZone){
    // timezone by php friendly values
    $to_tz = date_default_timezone_get();
    $date = new DateTime($time, new DateTimeZone($fromTimeZone));
    $date->setTimezone(new DateTimeZone($to_tz));
    $time= $date->format('Y-m-d H:i:s');
    return $time;
}
