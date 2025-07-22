<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;

class planesController extends Controller
{
    public function index() 
    {
       // $planes = Plan::all();
       // return response()->json($planes);
       return Plan::all();
      
    }
}
