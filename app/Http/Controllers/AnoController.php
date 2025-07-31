<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnoController extends Controller
{
    public function index()
    {
        return DB::table('anos')->get();
    }
}