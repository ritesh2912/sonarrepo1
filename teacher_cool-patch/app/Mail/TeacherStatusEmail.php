<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class TeacherStatusEmail extends Mailable
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
        $address = env('MAIL_FROM_ADDRESS');
        $subject = $this->data['subject'];
        $name = env('MAIL_FROM_NAME');
        $receiver_name = $this->data['receiver_name'];
        $body = $this->data['body'];
        $login_url = (isset($this->data['login_url']))? $this->data['login_url'] : false;
        $reciver_email = $this->data['to'];

        return $this->view('emails.teacherstatus')
                    ->from($address, $name)
                    ->to($reciver_email, $receiver_name)
                    ->replyTo($address, $name)
                    ->subject($subject)
                    ->with([ 'receiver_name' => $receiver_name , 'body' => $body, 'login_url' => $login_url]);
    }
}
