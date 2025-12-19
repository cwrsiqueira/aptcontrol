@extends('layouts.template')

@section('title', 'Editar Permissão')

@section('content')
<main role="main" class="col-md-9 ml-sm-auto col-lg pt-3 px-4 mb-5">
    <div class="d-flex align-items-center justify-content-between mb-3 page-header">
        <h2 class="mb-0">Editar Permissão</h2>
        <a href="{{ route('permission-items.index') }}" class="btn btn-sm btn-outline-secondary">Voltar</a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
    </div>
    @endif

    <div class="card card-lift">
        <div class="card-body">
            <form method="POST" action="{{ route('permission-items.update', $item->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Slug</label>

                    <div class="alert alert-warning">
                        <strong>Atenção:</strong> alterar o <code>slug</code> pode quebrar acessos.
                        <br>
                        Se você mudar o slug aqui, precisa <strong>refatorar no código</strong> onde esse slug é usado,
                        por exemplo:
                        <ul class="mb-0">
                            <li>checagens de permissão (ex.: <code>in_array('slug', $user_permissions)</code>)</li>
                            <li>menus/itens protegidos por slug</li>
                            <li>middlewares/helpers/gates que dependem do slug</li>
                        </ul>
                        Além disso, os vínculos em <code>permission_links</code> precisarão acompanhar o novo slug (se
                        você decidir mudar).
                    </div>

                    <input type="text" name="slug" id="slug" class="form-control" maxlength="100"
                        value="{{ old('slug', $item->slug) }}" readonly required>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="confirm_slug_change"
                            id="confirm_slug_change" value="1" {{ old('confirm_slug_change') ? 'checked' : '' }}>
                        <label class="form-check-label" for="confirm_slug_change">
                            Entendi os riscos e que preciso refatorar o código caso altere o slug. Liberar edição do
                            slug.
                        </label>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const checkbox = document.getElementById('confirm_slug_change');
                        const slugInput = document.getElementById('slug');
                        const originalSlug = slugInput.value;

                        function sync() {
                            slugInput.readOnly = !checkbox.checked;
                            if (!checkbox.checked) {
                                // volta pro original se desmarcar
                                slugInput.value = originalSlug;
                            }
                        }

                        checkbox.addEventListener('change', sync);
                        sync();
                    });
                </script>

                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="name" class="form-control" maxlength="150"
                        value="{{ old('name', $item->name) }}" required>
                </div>

                <div class="form-group">
                    <label>Grupo</label>
                    <input type="text" name="group_name" class="form-control" maxlength="100"
                        value="{{ old('group_name', $item->group_name) }}">
                </div>

                <button class="btn btn-success" type="submit">Salvar</button>
            </form>
        </div>
    </div>
</main>
@endsection
