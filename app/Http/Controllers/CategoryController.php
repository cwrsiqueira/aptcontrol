<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Client;
use App\Clients_category;
use App\Helpers\Helper;
use Illuminate\Validation\Rule;

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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('menu-categorias', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        $categories = Clients_category::orderBy('id')->paginate(10);

        $user_permissions = Helper::get_permissions();

        return view('categories.categories', [
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
        $user_permissions = Helper::get_permissions();
        if (!in_array('categories.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('categories.index')->withErrors($message);
        }

        return view('categories.categories_create', [
            'user' => Auth::user(),
            'user_permissions' => $user_permissions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('categories.create', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        }

        $data = $request->only([
            'name',
        ]);

        Validator::make(
            $data,
            [
                'name' => 'required|unique:clients_categories|max:100',
            ]
        )->validate();

        $prod = new Clients_category();
        $prod->name = $data['name'];
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Cadastro', $prod->id, $prod->name, 'Categorias');

        return redirect()->route("categories.index")->with('success', 'Salvo com sucesso!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Clients_category $category)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('categories.view', $user_permissions) && !Auth::user()->is_admin) {
            $message = ['no-access' => 'Solicite acesso ao administrador!'];
            return redirect()->route('home')->withErrors($message);
        }

        return view('categories.categories_view', [
            'user'             => Auth::user(),
            'category'           => $category,
            'user_permissions' => $user_permissions,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Clients_category $category)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('categories.update', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        }

        return view('categories.categories_edit', [
            'user' => Auth::user(),
            'category' => $category,
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
        $user_permissions = Helper::get_permissions();
        if (!in_array('categories.update', $user_permissions) && !Auth::user()->is_admin) {
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
                'name' => [
                    'required',
                    'max:100',
                    Rule::unique('clients_categories', 'name')->ignore($id), // ignora o próprio registro
                ],
            ]
        )->validate();

        $prod = Clients_category::find($id);
        $prod->name = $data['name'];
        $prod->save();

        Helper::saveLog(Auth::user()->id, 'Alteração', $prod->id, $prod->name, 'Categorias');

        return redirect()->route('categories.index')->with('success', 'Atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Clients_category $category)
    {
        $user_permissions = Helper::get_permissions();
        if (!in_array('categories.delete', $user_permissions) && !Auth::user()->is_admin) {
            $message = [
                'no-access' => 'Solicite acesso ao administrador!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        }

        $id = $category->id;
        $clients = Client::where('id_categoria', $id)->get();

        if (count($clients) > 0) {
            $message = [
                'cannot_exclude' => 'Categoria não pode ser excluída, pois possui clientes vinculados!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        } elseif ($category->name == 'Padrão') {
            $message = [
                'cannot_exclude' => 'A categoria Padrão não pode ser excluída!',
            ];
            return redirect()->route('categories.index')->withErrors($message);
        } else {
            $category = Clients_category::find($id);
            Clients_category::find($id)->delete();
            Helper::saveLog(Auth::user()->id, 'Deleção', $id, $category->name, 'Categorias');
            return redirect()->route('categories.index')->with('success', 'Excluído com sucesso!');
        }
    }
}
