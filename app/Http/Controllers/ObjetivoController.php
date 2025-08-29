<?php

namespace App\Http\Controllers;

use App\Models\Objetivo;
use Illuminate\Http\Request;

class ObjetivoController extends Controller
{
    public function destroy($id)
    {
        $objetivo = Objetivo::find($id);
        if (!$objetivo) {
            return response()->json(['message' => 'Objetivo não encontrado.'], 404);
        }
        $objetivo->delete();
        return response()->json(['success' => true]);
    }
}