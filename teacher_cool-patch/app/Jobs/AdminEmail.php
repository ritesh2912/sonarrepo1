<?php

namespace App\Jobs;

use App\Mail\SendAdminEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\GetQouteEmail;

class AdminEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(isset($this->data['filename'])){
            Mail::to($this->data['to'])->send(new GetQouteEmail($this->data));
        }elseif(isset($this->data['cc'])){
            Mail::to($this->data['to'])->cc($this->data['cc'])->send(new SendAdminEmail($this->data));
        } else{
            Mail::to($this->data['to'])->send(new SendAdminEmail($this->data));
        }
        
    }
}
