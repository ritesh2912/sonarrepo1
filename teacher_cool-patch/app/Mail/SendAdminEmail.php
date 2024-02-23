<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class SendAdminEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    // public function envelope()
    // {
    //     return new Envelope(
    //         subject: this->data['subject'],
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  *
    //  * @return \Illuminate\Mail\Mailables\Content
    //  */
    // public function content()
    // {
    //     return new Content(
    //         view: 'emails.welcome',
    //     );
    // }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address    = env('MAIL_FROM_ADDRESS');
        $subject    = $this->data['subject'];
        $name       = env('MAIL_FROM_NAME');
        $body       = $this->data['body'];
        $filename   = isset($this->data['filename']) ? $this->data['filename'] : null;
        $reqData   = isset($this->data['reqData']) ? $this->data['reqData'] : null;
        $is_copyright   = isset($this->data['is_copyright'])? $this->data['is_copyright'] : false;
        $sent_url = '';

        $url =  !empty($this->data['url']) ? $this->data['url'] : (!empty($this->data['profile_url']) ? $this->data['profile_url'] : 'null');    
        
        if(isset($this->data['filename'])){
            return $this->from($address, $name)
                        ->to($this->data['to'])
                        ->cc($this->data['cc'])
                        ->attach(storage_path('app/public/'.$filename))
                        ->view('emails.adminemail')
                        ->replyTo($address, $name)
                        ->subject($subject)
                        ->with(['body'=>$body, 'url'=>$sent_url]);
        }elseif($is_copyright){
            return $this->from($address, $name)
                        ->to($this->data['to'])
                        ->view('emails.copyright-takedown-email')
                        ->replyTo($address, $name)
                        ->subject($subject)
                        ->with(['reqData'=>$reqData]);
        }else{
            return $this->from($address, $name)
                        ->to($this->data['to'])
                        ->cc($this->data['cc'])
                        ->view('emails.adminemail')
                        ->replyTo($address, $name)
                        ->subject($subject)
                        ->with(['body'=>$body, 'url'=>$sent_url]);
        }
        
    }


}
