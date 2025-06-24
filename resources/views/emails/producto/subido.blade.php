@component('mail::message')
# ¡Producto Subido!

Hola {{ $product->user->name }},

Has subido el producto: **{{ $product->name }}**.

Descripción:
> {{ $product->description }}

Por favor, manda el producto a la siguiente dirección para su revisión:

📍 **Calle Falsa 123, Ciudad de Mexico, MX**

Gracias por usar **XPMarket**.

@endcomponent
