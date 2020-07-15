<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (Auth::user()->confirmed_user !== 0) {
            return view('dashboard', [
                'user' => Auth::user(),
            ]);
        } else {
            return view('home', [
                'user' => Auth::user(),
            ]);
        } 
    }
}
