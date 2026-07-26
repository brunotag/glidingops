<!DOCTYPE HTML>
<html>
  <meta name="viewport" content="width=device-width">
  <meta name="viewport" content="initial-scale=1.0">

  <head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- styles --}}
    @stack('styles')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
    {{-- scripts --}}
    @stack('scripts')
  </head>

  <body>
@if (App::environment('development'))
    <div style='width: 100%;background-color: red;color: white;font-weight: bolder;font-size: 20px;text-align: center;'>
      DEVELOPMENT MODE
    </div>
@endif
  <a href='/home'>HOME</a>
  @yield('content')

  </body>
</html>