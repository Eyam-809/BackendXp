<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Mail\ProductoSubido;
use Illuminate\Support\Facades\Mail;

class ProductController extends Controller
{
    // 🔹 Obtener todos los productos tipo "venta"
    public function index()
    {
        try {
            $products = Product::where('tipo', 'venta')
                ->where('status_id', 2)
                ->get()
                ->each->append('image_url');

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 🔹 Crear un nuevo producto
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'price'           => 'required|numeric',
            'stock'           => 'required|integer',
            'image'           => 'nullable|image|max:2048',
            'id_user'         => 'required|integer|exists:users,id',
            'categoria_id'    => 'nullable|integer|exists:categorias,id',
            'subcategoria_id' => 'nullable|integer|exists:subcategorias,id',
            'tipo'            => 'required|string|in:venta,trueque',
            'video'           => 'nullable|file|mimes:mp4,mov,avi|max:51200', // 50MB máx
            'status_id'       => 'nullable|integer|exists:statuses,id',
        ]);

        $product = new Product();
        $product->fill($request->only([
            'name', 'description', 'price', 'stock',
            'id_user', 'categoria_id', 'subcategoria_id', 'tipo'
        ]));
        $product->status_id = 1;

        // Asignación explícita para asegurar guardado si $fillable no incluye los campos
        if ($request->has('categoria_id')) {
            $product->categoria_id = $request->input('categoria_id');
        }
        if ($request->has('subcategoria_id')) {
            $product->subcategoria_id = $request->input('subcategoria_id');
        }
        if ($request->has('id_user')) {
            $product->id_user = $request->input('id_user');
        }

        // 🔸 Si el tipo es "trueque", el precio se pone a 0
        if ($request->tipo === 'trueque') {
            $product->price = 0;
            $product->status_id = 2;
        }

        // 🔸 Subir video a S3 si se incluye (con fallback a base64 si S3 falla)
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $mimeType = $file->getClientMimeType();

            // S3 deshabilitado temporalmente — se conserva el código comentado para usar luego.
            /*
            try {
                // Intentar subir a S3
                $path = $file->store('videos', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $product->video = Storage::disk('s3')->url($path);
            } catch (\Throwable $e) {
                // Si falla S3, se registra (pero no guardamos aquí)
                \Log::warning('S3 upload failed', ['error' => $e->getMessage()]);
            }
            */

            // Guardar siempre como base64 en la BD (fallback permanente mientras S3 no se use)
            $contents = file_get_contents($file->getRealPath());
            $product->video = 'data:' . $mimeType . ';base64,' . base64_encode($contents);
            \Log::info('Video guardado en base64 (S3 deshabilitado)', ['size' => strlen($product->video)]);
        }

        // 🔸 Guardar imagen en base64 (sin usar disco)
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $mimeType = $file->getClientMimeType();
            $imageContents = file_get_contents($file);
            $product->image = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
        }
        

        $product->save();

        // 🔸 Enviar correo al usuario
        if ($product->user && $product->user->email) {
            Mail::to($product->user->email)->send(new ProductoSubido($product));
        }

        return response()->json([
            'message' => 'Producto creado con éxito',
            'product' => $product,
        ], 201);
    }

    // 🔹 Mostrar producto por ID
    public function show($id)
    {
        $product = Product::findOrFail($id)->append('image_url');
        return response()->json($product);
    }

    // 🔹 Actualizar producto
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric',
            'stock'       => 'sometimes|integer',
            'tipo'        => 'sometimes|string|in:venta,trueque',
            'image'       => 'nullable|image|max:2048',
            'video'       => 'nullable|file|mimes:mp4,mov,avi|max:51200',
        ]);

        // 🔸 Subir nuevo video si se actualiza (con fallback a base64)
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $mimeType = $file->getClientMimeType();

            // Eliminación en S3 deshabilitada (no borrar nada si guardas en base64)
            /*
            if ($product->video && (str_starts_with($product->video, 'http://') || str_starts_with($product->video, 'https://'))) {
                try {
                    $oldPath = parse_url($product->video, PHP_URL_PATH);
                    $oldPath = ltrim($oldPath, '/');
                    Storage::disk('s3')->delete($oldPath);
                } catch (\Throwable $e) {
                    \Log::warning('No se pudo eliminar video anterior en S3', ['error' => $e->getMessage()]);
                }
            }
            */

            // S3 upload deshabilitado — guardar en base64
            /*
            try {
                $path = $file->store('videos', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $product->video = Storage::disk('s3')->url($path);
            } catch (\Throwable $e) {
                \Log::warning('S3 upload failed on update', ['error' => $e->getMessage()]);
            }
            */
            $contents = file_get_contents($file->getRealPath());
            $product->video = 'data:' . $mimeType . ';base64,' . base64_encode($contents);
            \Log::info('Video actualizado y guardado en base64 (S3 deshabilitado)', ['size' => strlen($product->video)]);
        }

        // 🔸 Subir nueva imagen si se envía
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $mimeType = $file->getClientMimeType();
            $imageContents = file_get_contents($file);
            $product->image = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
        }

        $product->fill($request->except(['video', 'image']));
        $product->save();

        return response()->json([
            'message' => 'Producto actualizado correctamente',
            'product' => $product,
        ]);
    }

    // 🔹 Eliminar producto
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Eliminación en S3 deshabilitada — no intentaremos borrar nada remoto.
        /*
        if ($product->video) {
            $oldPath = parse_url($product->video, PHP_URL_PATH);
            Storage::disk('s3')->delete($oldPath);
        }
        */

        $product->delete();

        return response()->json(['message' => 'Producto eliminado correctamente']);
    }

    // 🔹 Obtener productos por usuario
    public function getUserProducts($id)
    {
        $products = Product::where('id_user', $id)
            ->get()
            ->each->append('image_url');

        return response()->json($products);
    }

    // 🔹 Obtener productos por subcategoría
    public function getBySubcategoria($subcategoria_id)
    {
        try {
            $products = Product::where('subcategoria_id', $subcategoria_id)
                ->where('tipo', 'venta')
                ->where('status_id', 2)
                ->get()
                ->each->append('image_url');

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 🔹 Obtener productos tipo trueque
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
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByStatus($status_id)
    {
        try {
            $products = Product::where('status_id', $status_id)
                ->where('tipo', 'venta')
                ->get()
                ->each->append('image_url');

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna el número de productos vendidos (status_id = 4) de un usuario.
     */
    public function countSoldByUser($userId)
    {
        $count = Product::where('id_user', $userId)
            ->where('status_id', 4)
            ->count();

        return response()->json([
            'user_id' => (int) $userId,
            'sold_count' => $count,
        ]);
    }

    /**
     * Obtener todos los productos activos (status_id = 2).
     */
    public function getActiveProducts($userId)
    {
       $count = Product::where('id_user', $userId)
            ->where('status_id', 2)
            ->count();

        return response()->json([
            'user_id' => (int) $userId,
            'sold_count_active' => $count,
        ]);
    }



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////ADMIN FUNCTIONS///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Obtener todos los productos con status_id = 1.
     */
    public function getStatusOneProducts()
    {
        try {
            $products = Product::where('status_id', 1)
                ->get()
                ->each->append('image_url');

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar el status del producto (ej. 2 = aprobado, 3 = rechazado).
     * Body esperado: { "status_id": 2, "reason": "motivo opcional" }
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'required|integer|in:1,2,3,4'
        ]);

        $product = Product::findOrFail($id);
        $product->status_id = $request->input('status_id');

        // Guardar motivo de rechazo si existe y la columna está disponible
        if ($request->filled('reason') && \Schema::hasColumn('products', 'rejection_reason')) {
            $product->rejection_reason = $request->input('reason');
        }

        $product->save();

        return response()->json([
            'message' => 'Status actualizado correctamente',
            'product' => $product
        ]);
    }

    public function getApprovedProducts(Request $request)
    {
        try {
            $user = $request->user();
            $planId = $user->plan_id; // viene del token
            $userId = $user->id;

            $query = Product::where('status_id', 2);

            // Si el plan es menor a 3, solo puede ver sus productos
            if ($planId < 3) {
                $query->where('id_user', $userId);
            }

            $products = $query->get()->each->append('image_url');

            return response()->json($products);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getRejectedProducts(Request $request)
    {
        try {
            $user = $request->user();
            $planId = $user->plan_id;
            $userId = $user->id;

            $query = Product::where('status_id', 3);

            // Plan menor a 3 = solo los suyos
            if ($planId < 3) {
                $query->where('id_user', $userId);
            }

            $products = $query->get()->each->append('image_url');

            return response()->json($products);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
