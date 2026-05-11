<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <style>
            body {
                font-family: system-ui, -apple-system, sans-serif;
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #0c0c0e;
                color: #e7e7ea;
            }
            main {
                text-align: center;
                padding: 2rem;
            }
            h1 {
                font-size: 1.75rem;
                font-weight: 600;
                margin: 0 0 0.5rem;
            }
            p {
                color: #9b9ba3;
                margin: 0 0 1.5rem;
                font-size: 0.95rem;
            }
            a {
                color: #f59e0b;
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <main>
            <h1>{{ config('app.name') }}</h1>
            <p>Бэкенд и админ-панель.</p>
            <a href="{{ url('/admin') }}">Админ-панель</a>
        </main>
    </body>
</html>
