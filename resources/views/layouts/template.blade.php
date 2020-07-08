
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="{{asset('favicon.ico')}}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title')</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/4.0/examples/dashboard/">

    <!-- Bootstrap core CSS -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="{{asset('css/dashboard.css')}}" rel="stylesheet">

    {{-- FONT AWESOME --}}
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">

    <!-- MY Custom styles -->
    <link href="{{asset('css/style.css')}}" rel="stylesheet">

    @yield('css')
  </head>

  <body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
      <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="#">{{ config('app.name', 'Laravel') }}</a>
      <span style="color:#fff;">Sessão aberta por: {{$user->id ?? ''}} - {{$user->name ?? ''}}</span>
      <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
          <a class="nav-link" href="{{route('logout')}}">{{ __('Logout') }}</a>
        </li>
      </ul>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
          <div class="sidebar-sticky">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a class="nav-link @if(Request::is('home')) active @endif" href="{{route('home')}}">
                  <span data-feather="home"></span>
                  Painel <span class="sr-only">(current)</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link @if(Request::is(['products', 'products/*'])) active @endif" href="{{route('products.index')}}">
                  <span data-feather="shopping-cart"></span>
                  Produtos
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link @if(Request::is('clients')) active @endif" href="{{route('clients.index')}}">
                  <span data-feather="users"></span>
                  Clientes
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link @if(Request::is(['orders', 'orders/*'])) active @endif" href="{{route('orders.index')}}">
                  <span data-feather="file"></span>
                  Pedidos
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link @if(Request::is('reports')) active @endif" href="{{route('reports.index')}}">
                  <span data-feather="bar-chart-2"></span>
                  Relatórios
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link @if(Request::is('integrations')) active @endif" href="{{route('integrations.index')}}">
                  <span data-feather="layers"></span>
                  Integrações
                </a>
              </li>
            </ul>

            
          </div>
        </nav>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
          
            @yield('content')

        </main>
      </div>
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script>window.jQuery || document.write('<script src="{{asset("js/jquery-slim.min.js")}}"><\/script>')</script>
    <script src="{{asset('js/popper.min.js')}}"></script>
    <script src="{{asset('js/bootstrap.min.js')}}"></script>

    <!-- Icons -->
    <script src="https://unpkg.com/feather-icons/dist/feather.min.js"></script>
    <script>
      feather.replace()
    </script>

    <script src="{{asset('js/jquery.mask.min.js')}}"></script>

    @yield('js')

  </body>
</html>