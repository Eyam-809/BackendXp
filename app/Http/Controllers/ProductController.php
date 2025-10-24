<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Mail\ProductoSubido;
use Illuminate\Support\Facades\Mail;

class ProductController extends Controller
{
    public function index() 
    {
        try {
            // Solo productos de tipo venta
            $products = Product::where('tipo', 'venta')
            ->get()
            ->each->append('image_url');
            
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
            'categoria_id' => 'nullable|integer|exists:categorias,id', // Validar categoría si se proporciona
            'subcategoria_id' => 'nullable|integer|exists:subcategorias,id',// Validar subcategoría si se proporciona
            'tipo' => 'required|string|in:venta,trueque',
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:51200',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->id_user = $request->id_user;
        $product->categoria_id = $request->categoria_id;
        $product->subcategoria_id = $request->subcategoria_id;
        $product->tipo = $request->tipo;
        
        if ($request->hasFile('video')) {
        $path = $request->file('video')->store('videos', 's3'); // Carpeta 'videos' en S3
        Storage::disk('s3')->setVisibility($path, 'public'); // Hacerlo público
        $product->video = Storage::disk('s3')->url($path); // Guardar la URL en DB
         }

        // Si el tipo es 'trueque', el precio se establece en 0
    $product->price = $request->tipo === 'trueque' ? 0 : $request->price;

        // Manejar la imagen si se sube una
        if ($request->hasFile('image')) {
            $file = $request->file('image');
        
            // Leer el contenido del archivo
            $imageContents = file_get_contents($file);
        
            // Obtener la extensión/mime type
            $mimeType = $file->getClientMimeType(); // ejemplo: image/jpeg
        
            // Codificar a base64
            $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
        
            // Guardar en la base de datos
            $product->image = $base64Image;
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

        $product = Product::findOrFail($id)->append('image_url');
        return response()->json($product);
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
        $products = Product::where('id_user', $id)->get()->each->append('image_url');
        return response()->json($products);
    }

   public function getBySubcategoria($subcategoria_id)
{
    try {
        $products = Product::where('subcategoria_id', $subcategoria_id) // Solo ventas
            ->where('tipo', 'venta')
            ->get()
            ->each->append('image_url');

        return response()->json($products);
    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function getTrueques()
    {
        try {
            $products = Product::where('tipo', 'trueque')
            ->with('user:id,name,email')
            ->get()
            ->each->append('image_url');

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
