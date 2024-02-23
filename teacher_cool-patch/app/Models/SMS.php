<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Twilio\Rest\Client;

class SMS extends Model
{
    use HasFactory;
    public static function sendSMS($data){
       
        $account_sid = env('TWILIO_APP_ID');
        $auth_token = env('TWILIO_APP_KEY');
        
        $twilio_number = env('TWILIO_APP_NUMBER');

        $client = new Client($account_sid, $auth_token);
        $client->messages->create(
            // Where to send a text message (your cell phone?)
            // $data['phone_number'],
            '+91 95015 60691',
            array(
                'from' => $twilio_number,
                'body' => $data['body']
            )
        );
    }

}
