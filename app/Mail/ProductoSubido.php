<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;

class ProductoSubido extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product) {}

    public function build()
    {
        return $this->subject('Has subido un nuevo producto')
                    ->markdown('emails.producto.subido');
    }
}
