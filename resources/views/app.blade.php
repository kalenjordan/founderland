<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>App</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">

    <link href="/css/app.css" rel="stylesheet" type="text/css">

    <script src="https://unpkg.com/marked@0.3.6"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-secondary-color-lightest">
    <div id="app" class="wrapper">
        <router-view></router-view>
    </div>

    <script src="/js/app.js"></script>
</body>
</html>