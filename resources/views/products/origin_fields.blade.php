@foreach (\App\Helpers\Helper::listOriginQuery() as $name => $value)
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
@endforeach
