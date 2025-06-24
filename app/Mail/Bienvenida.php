<?php 

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Bienvenida extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $user) {}

    public function build()
    {
        return $this->subject('Bienvenido a XPMarket')
                    ->view('emails.bienvenida') // usa la vista Blade
                    ->with(['user' => $this->user]);
    }
}
