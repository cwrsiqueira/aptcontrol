@extends('layouts.estilos')

@section('title', 'C/C Produto')

@section('content')
    <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
        <h2>Conta Corrente Produto</h2>
        <div class="row">
            <div class="card col-md m-3">
                <div class="card-header"><span>{{$product->name}}</span></div>
                <div class="card-body">
                    Total a entregar: {{number_format($quant_total ?? 0, 0, '', '.') ?? 0}} <br>
                    Entregas a partir de: {{date('d/m/Y', strtotime($delivery_in))}} <br>
                </div>
            </div>
            <form method="get">
                <div class="card m-3">
                    <div class="card-header">
                        Filtros
                    </div>

                    <div class="card-body">
                        @foreach ($quant_por_categoria as $item)
                            <input class="mr-1" type="checkbox" name="por_categoria[]"  value="{{$item['id']}}" @if(!empty($_GET['por_categoria']) && in_array($item['id'], $_GET['por_categoria'])) checked @endif>{{$item['name']}} = {{number_format($item['saldo'], 0, '', '.')}} <br>
                        @endforeach
                        <hr>
                        <input type="submit" value="Filtrar" id="search">
                        <a href="{{route('cc_product', ['id' => $product->id])}}" id="clean_search">Limpar Filtro</a>
                    </div>
                </div>
            </form>

            <div class="col-md m-3">
                {{-- <div class="card-tools">
                    <button class="btn btn-sm btn-secondary" onclick="window.location.href = '../../products'" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" onclick="this.remove();document.getElementById('btn_voltar').remove();window.print();window.location.href = '../../products';">Imprimir</button>
                </div> --}}
                <div class="card-tools">
                    <button class="btn btn-sm btn-secondary" onclick="javascript:history.go(-1);" id="btn_voltar">Voltar</button>
                    <button class="btn btn-sm btn-secondary" id="btn_imprimir">Imprimir</button>
                    <a class="btn btn-sm btn-secondary" id="btn_sair" href="{{route('products.index')}}">Sair</a>
                    <a class="btn btn-sm btn-secondary" id="btn_recalc" href="{{route('day_delivery_recalc', ['id' => $product->id])}}">Recalcular datas de entrega</a>
                </div>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Categoria</th>
                    <th>Pedido</th>
                    <th>Saldo</th>
                    <th>Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $item)
                    <tr class="linha" data-id="{{$item->id}}">
                        <td>{{date('d/m/Y', strtotime($item->order_date))}}</td>
                        <td>{{$item->client_name}}</td>
                        <td>{{$item->category_name}}</td>
                        <td>{{$item->order_id}}</td>
                        <td>{{number_format($item->saldo, 0, '', '.')}}</td>
                        <td>{{date('d/m/Y', strtotime($item->delivery_date))}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{-- {{$data->links()}} --}}
    </main>
@endsection

@section('css')
    <style>
        tbody tr:hover {
            background-color:rgb(227, 236, 233);
            cursor: pointer;
        }
    </style>
@endsection

@section('js')
    <script>
        $('.linha').click(function(){
            let attr = $(this).attr('style');
            if (typeof attr !== typeof undefined && attr !== false) {
                $(this).removeAttr('style');
            } else {
                $(this).attr('style', 'background-color:aquamarine;');
            }
        });
        $('#btn_imprimir').click(function(){
            $(this).hide();
            $('#btn_voltar').hide();
            $('#btn_sair').hide();
            $('#btn_recalc').hide(); 
            $('#search').hide();
            $('#clean_search').hide(); 
            window.print();
            javascript:history.go(0);
        })
    </script>
@endsection

