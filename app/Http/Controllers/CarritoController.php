<?php
namespace App\Http\Controllers;
use App\Models\carrito;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarritoController extends Controller
{
    public function agregarAlCarrito(Request $request)
    {
        try {
            \Log::info('Datos recibidos:', $request->all()); // Registrar datos en logs
            
            // Validar que los parámetros sean correctos
            $request->validate([
                'producto_id' => 'required|exists:products,id',  // Validar que el producto exista
                'cantidad' => 'required|integer|min:1',  // La cantidad debe ser al menos 1
            ]);

            // Verificar si el producto existe
            $producto = Product::find($request->producto_id);
            if (!$producto) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            // Puedes utilizar cualquier identificador para el usuario (por ejemplo, un 'user_id' enviado en la solicitud)
            // Si no usas un 'user_id', puedes hacer que el carrito sea para cualquier visitante
            // Sin autenticación, el carrito no estará relacionado a un usuario
            $user_id = $request->user_id ?? 'guest'; // Si no se envía un user_id, usaremos 'guest'

            // Crear o actualizar el carrito para este usuario, con la cantidad de producto
            $carrito = Carrito::updateOrCreate(
                [
                    'usuario_id' => $user_id,  // Usamos el user_id (puede ser 'guest' si no se envía)
                    'producto_id' => $request->producto_id,
                ],
                ['cantidad' => $request->cantidad]  // Usamos la cantidad recibida
            );

            return response()->json(['mensaje' => 'Producto agregado al carrito', 'carrito' => $carrito]);
        } catch (\Exception $e) {
            \Log::error('Error al agregar al carrito: ', ['error' => $e->getMessage()]); // Registrar error en log
            return response()->json(['error' => 'Ocurrió un error en el servidor: ' . $e->getMessage()], 500);
        }
    }

    // Ver el carrito (sin autenticación, pero con 'user_id')
    public function verCarrito(Request $request)
{
    $user_id = $request->query('user_id', 'guest'); // Obtener el user_id de la URL

    // Obtener los productos del carrito con la relación 'producto'
    $carrito = Carrito::where('usuario_id', $user_id)
                      ->with('producto') // Cargar los datos completos del producto
                      ->get();

    if ($carrito->isEmpty()) {
        return response()->json(['message' => 'Carrito vacío'], 200);
    }

    return response()->json($carrito);
}






public function eliminarDelCarrito($id)
{
    // Buscar el carrito en la base de datos utilizando solo el id del carrito
    $carrito = Carrito::find($id);  // Usamos `find` para buscar el carrito solo por su id

    if (!$carrito) {
        return response()->json(['error' => 'Producto no encontrado en el carrito'], 404);
    }

    // Eliminar el carrito completamente basado solo en el id
    $carrito->delete();

    return response()->json(['message' => 'Producto eliminado del carrito']);
}










}
