<?php
namespace App\Services;
// use Twilio\Rest\Client;

class WhatsappService {
    //send message
    public function sendMessage($data){
            try{
                dd($data);
            $account_sid = env('TWILIO_APP_ID');
            $auth_token = env('TWILIO_APP_KEY');
            
            $twilio_number = "whatsapp:".env('TWILIO_APP_NUMBER');

            $client = new Client($account_sid, $auth_token);
            $client->messages->create(
                // Where to send a text message (your cell phone?)
                // $data['phone_number'],
                'whatsapp:'.$data['number'],
                array(
                    'from' => $twilio_number,
                    'body' => $data['body']
                )
            );
        }catch(\Exception $e){
            dd($e);
        }
        }
}
?>
