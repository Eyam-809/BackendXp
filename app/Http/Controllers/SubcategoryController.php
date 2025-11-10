<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subcategoria;


class SubcategoryController extends Controller
{
     public function byCategory($categoria_id)
    {
        // OJO: usamos 'categoria_id' porque así se llama la columna en la BD
        $subcategorias = Subcategoria::where('categoria_id', $categoria_id)->get();

        return response()->json($subcategorias);
    }



    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

  
}
