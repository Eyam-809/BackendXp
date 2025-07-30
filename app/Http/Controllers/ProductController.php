<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Mail\ProductoSubido;
use Illuminate\Support\Facades\Mail;

class ProductController extends Controller
{
    public function index() {
    try {
        $products = Product::all();
        return response()->json($products);
    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}
  



    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric',
        'stock' => 'required|integer',
        'image' => 'nullable|image', // Cambiado para validar que sea una imagen
        'id_user' => 'required|integer|exists:users,id',
    ]);

    $product = new Product();
    $product->name = $request->name;
    $product->description = $request->description;
    $product->price = $request->price;
    $product->stock = $request->stock;
    $product->id_user = $request->id_user;

    // Manejar la imagen si se sube una
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('products', 'public');
        //$imagePath = $request->file('image')->public('products', 'storage');
        $product->image = $imagePath;  // Guardar la ruta de la imagen
    }

    $product->save();

     Mail::to($product->user->email)->send(new ProductoSubido($product));

    return response()->json([
        'message' => 'Producto creado con éxito',
        'product' => $product
    ], 201);
}



    public function show($id)
    {
        return response()->json(Product::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        // Procesar la imagen si se envía una nueva
        if ($request->has('image')) {
            $imageData = $request->input('image');
            $image = str_replace('data:image/jpeg;base64,', '', $imageData);
            $image = str_replace(' ', '+', $image);
            $imageName = time() . '.jpg';
            Storage::disk('public')->put($imageName, base64_decode($image));
            $request->merge(['image' => $imageName]);
        }

        $product->update($request->all());
        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image) {
            Storage::disk('public')->delete($product->image); // Eliminar la imagen si existe
        }
        $product->delete();
        return response()->json(['message' => 'Producto eliminado']);
    }

    // Controlador ProductoController.php

    public function getUserProducts($id)
    {
        $products = Product::where('id_user', $id)->get();
        return response()->json($products);
    }

}
