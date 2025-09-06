<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Client;
use App\Clients_category;
use App\User;
use App\Helpers\Helper;

class CategoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:menu-clientes');
    }

    public function get_permissions()
    {
        $id = Auth::user()->id;
        $user_permissions_obj = User::find($id)->permissions;
        $user_permissions = array();
        foreach ($user_permissions_obj as $item) {
            $user_permissions[] = $item->id_permission_item;
        }
        return $user_permissions;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Clients_category::orderBy('id')->paginate(10);

        $user_permissions = $this->get_permissions();

        return view('categories', [
            'user' => Auth::user(),
            'categories' => $categories,
            'user_permissions' => $user_permissions
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
        $user_permissions = $this->get_permissions();
        if (!in_array('21', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
        ]);

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|unique:clients|max:100',
            ]
        )->validate();

        $prod = new Clients_category();
        $prod->name = $data['name'];
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $prod->id, $prod->name, 'Categorias');

        return redirect()->route("categories.index");
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
        $user_permissions = $this->get_permissions();
        if (!in_array('22', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        }

        $categories = Clients_category::orderBy('id')->paginate(10);
        $category = Clients_category::find($id);
        $user_permissions = $this->get_permissions();

        return view('categories', [
            'user' => Auth::user(),
            'category' => $category,
            'categories' => $categories,
            'user_permissions' => $user_permissions
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
        $user_permissions = $this->get_permissions();
        if (!in_array('22', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
        ]);

        $validator = Validator::make(
            $data,
            [
                'name' => 'required|max:100',
            ]
        )->validate();

        $prod = Clients_category::find($id);
        $prod->name = $data['name'];
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Alteração', $prod->id, $prod->name, 'Categorias');

        return redirect()->route('categories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user_permissions = $this->get_permissions();
        if (!in_array('23', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        }

        $categories = Client::where('id_categoria', $id)->get();

        if (count($categories) > 0) {
            $message = [
                'cannot_exclude' => 'Categoria não pode ser excluída, pois possui clientes vinculados!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        } else {
            $category = Clients_category::find($id);
            Clients_category::find($id)->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $id, $category->name, 'Categorias');
            return redirect()->route('categories.index');
        }
    }
}
