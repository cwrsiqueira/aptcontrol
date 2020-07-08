<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Client;


class ClientController extends Controller
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clients = Client::paginate(5);
        if (!empty($_GET['q'])) {
            $q = $_GET['q'];
            $clients = Client::where('name', 'LIKE', '%'.$q.'%')->paginate(5);
        }
        
        return view('clients', [
            'user' => Auth::user(),
            'clients' => $clients
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'name',
            'contact',
            'address',
        ]);

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|unique:clients|max:100',
                'contact' => 'max:50|nullable',
                'address' => 'nullable',
            ]
        )->validate();

        $prod = new Client();
        $prod->name = $data['name'];
        $prod->contact = $data['contact'];
        $prod->full_address = $data['address'];
        $prod->save();

        return redirect()->route('clients.index');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $clients = Client::paginate(5);
        $client = Client::find($id);

        return view('clients',[
            'user' => Auth::user(),
            'client' => $client,
            'clients' => $clients,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->only([
            'name',
            'contact',
            'address',
        ]);

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|max:100',
                'contact' => 'max:50|nullable',
                'address' => 'nullable',
            ]
        )->validate();

        $prod = Client::find($id);
        $prod->name = $data['name'];
        $prod->contact = $data['contact'];
        $prod->full_address = $data['address'];
        $prod->save();

        return redirect()->route('clients.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
