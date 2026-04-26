<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;400;600&family=Roboto:wght@400;700&display=swap" rel="stylesheet" media="print">
    {{-- <link rel="stylesheet" href="{{ public_path('css/skeleton/skeleton.css') }}"> 
    <link rel="stylesheet" href="{{ public_path('css/skeleton/normalize.css') }}"> --}}
    <title>Document</title>

    <style>

        *{
            margin: 0;
            padding: 0;
        }
        .top-logo-container{
            display: flex;
            justify-content: center;
        }
        .top-logo{
            width: 100%;
        }
        .header-container{
            height: 100px;
            margin: 0 30px 0 30px;
        }
        .caja{
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px black solid;
            text-align: center;
            width: 80px;
            height: 98%;
        }

    </style>

</head>
<body>

    <div class="top-logo-container">
        {{-- <img class="top-logo" src="{{ public_path('img/pdf/header.png') }}"> --}}
    </div>
    <div class="header-container" style="background-color: green">
        <div class="caja">
            5
        </div>
    </div>

</body>
</html>