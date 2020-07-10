@extends('layouts.template')

@section('title', 'Integrações')

@section('content')
    <main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4">
        <h2>Integrações</h2>
        
    </main>
@endsection

@section('js')

<script>

    function select(obj) {
        var id = $(obj).attr('data-id');
        var name = $(obj).html();

        $('.searchresults').hide();
        $('#busca').val(name);
        $('input[name=busca_id]').val(id);
    }

    $(function(){
        $('#conta_corrente').change(function(){
            
            if ($(this).val() === 'order') {
                $('#busca').attr('placeholder', 'Digite o número');
            } else {
                $('#busca').attr('placeholder', 'Digite o nome');
            }
        })

        $('#busca').keyup(function(){
            let search = '';
            switch ($('#conta_corrente').val()) {
                case 'client':
                    search = 'Client';
                    break;
                case 'product':
                    search = 'Product';
                    break;
                case 'order':
                    search = 'Order';
                    break;
            }
            let q = $(this).val();
            
            if (q.length <= 0) {
                $('.searchresults').hide();
            }

            if (q.length > 0) {

                $.ajax({
                    url:"{{ route('search') }}",
                    type:"get",
                    data: {q:q,search:search},
                    dataType:"json",
                    befoneSend:function() {},
                    success:function(json) {
                        
                        if( $('.searchresults').length == 0 ) {
                            $('#busca').after('<div class="searchresults"></div>');
                        }
                        var res_width = $('#busca').css('width');
                        $('.searchresults').css('width', res_width);

                        var html = '';

                        for(var i in json) {
                            html += '<div class="si"><a href="javascript:;" onclick="select(this)" data-id="'+json[i].id+'">'+json[i].name+'</a></div>';
                        }

                        $('.searchresults').html(html);
                        $('.searchresults').show();
                    }
                });

            }

        });
    })
</script>
    
@endsection