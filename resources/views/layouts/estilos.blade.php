
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
        <div class="d-flex">
          <main role="main" class="col-md ml-sm-auto col-lg pt-3 px-4">
            
              @yield('content')

          </main>
        </div>
        
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