<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ASL Food Delivery API') }}</title>
    <link rel="icon" href="{{ config('app.url') }}/favicon.ico">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    <style>
        :root {
            --asl-primary: #F8B803;
            --asl-accent: #FF750F;
            --asl-dark: #1B1B18;
            --asl-muted: #5B5B57;
            --asl-bg: #FFFDF5;
            --asl-card: #FFFFFF;
            --asl-border: #F0E5BA;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--asl-bg);
            color: var(--asl-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .page-header {
            background: linear-gradient(135deg, var(--asl-primary) 0%, var(--asl-accent) 100%);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            border-bottom: 1px solid rgba(27, 27, 24, 0.15);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            height: 40px;
            width: auto;
            border-radius: 8px;
        }

        .brand span {
            color: var(--asl-dark);
            font-size: 1.35rem;
            font-weight: 700;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav a {
            color: var(--asl-dark);
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .nav a:hover {
            background: rgba(255, 255, 255, 0.5);
            border-color: rgba(27, 27, 24, 0.15);
        }

        .nav a.cta {
            background: var(--asl-dark);
            color: #fff;
            border-color: var(--asl-dark);
        }

        .nav a.cta:hover {
            background: #000;
            border-color: #000;
        }

        .container {
            max-width: 920px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            width: 100%;
            flex: 1;
        }

        .hero {
            text-align: center;
            padding: 2.5rem 1rem;
        }

        .hero h1 {
            margin: 0 0 0.5rem;
            font-size: 2rem;
            font-weight: 700;
            color: var(--asl-dark);
        }

        .hero .tagline {
            margin: 0;
            font-size: 1.075rem;
            color: var(--asl-muted);
        }

        .card {
            background: var(--asl-card);
            border-radius: 12px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--asl-border);
        }

        .card h2 {
            margin: 0 0 0.75rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--asl-dark);
        }

        .card p {
            margin: 0 0 0.75rem;
            color: var(--asl-dark);
            font-size: 1rem;
        }

        .card p:last-child {
            margin-bottom: 0;
        }

        .card ul {
            margin: 0.5rem 0 0;
            padding-left: 1.25rem;
            color: var(--asl-dark);
        }

        .card ul li {
            margin-bottom: 0.35rem;
        }

        .card a {
            color: var(--asl-accent);
            font-weight: 600;
            text-decoration: none;
        }

        .card a:hover {
            text-decoration: underline;
        }

        .badge {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--asl-dark);
            background: rgba(248, 184, 3, 0.18);
            border: 1px solid rgba(27, 27, 24, 0.12);
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            margin-bottom: 0.75rem;
        }

        .footer {
            background: var(--asl-dark);
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            padding: 1.1rem 1.5rem;
            font-size: 0.875rem;
            margin-top: auto;
        }

        .footer p { margin: 0; }

        @media (prefers-color-scheme: dark) {
            :root {
                --asl-bg: #121210;
                --asl-card: #1B1B18;
                --asl-dark: #F8F5E6;
                --asl-muted: #C8C2A5;
                --asl-border: #3A3629;
            }

            .page-header {
                background: linear-gradient(135deg, #9A6F00 0%, #B44F09 100%);
                border-bottom-color: rgba(248, 245, 230, 0.15);
            }

            .brand span,
            .nav a {
                color: #FFF7DD;
            }

            .nav a:hover {
                background: rgba(255, 247, 221, 0.15);
                border-color: rgba(255, 247, 221, 0.2);
            }

            .nav a.cta {
                background: #FFF7DD;
                color: #1B1B18;
                border-color: #FFF7DD;
            }

            .nav a.cta:hover {
                background: #FFFFFF;
                border-color: #FFFFFF;
            }

            .badge {
                color: #FFF7DD;
                background: rgba(248, 184, 3, 0.2);
                border-color: rgba(255, 247, 221, 0.18);
            }

            .card a {
                color: #FFB071;
            }

            .footer {
                background: #0D0D0B;
                color: rgba(255, 247, 221, 0.9);
            }
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="brand">
            @if (file_exists(public_path('site-logo.png')))
                <img src="{{ asset('site-logo.png') }}" alt="{{ config('app.name') }}">
            @endif
            <span>{{ config('app.name', 'ASL Food Delivery API') }}</span>
        </div>

        @if (Route::has('login'))
            <nav class="nav">
                @auth
                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="cta">Register</a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>

    <main class="container">
        <section class="hero">
            <span class="badge">Food Delivery APIs</span>
            <h1>{{ config('app.name', 'ASL Food Delivery API') }}</h1>
            <p class="tagline">Backend services for food ordering, delivery operations, user access, and real-time notifications.</p>
        </section>

        <section class="card">
            <h2>What this API powers</h2>
            <p>
                This application provides backend APIs for a food delivery platform, including secure authentication,
                role-based access control, media uploads, and notification workflows for customer and partner experiences.
            </p>
        </section>

        <section class="card">
            <h2>Key capabilities</h2>
            <ul>
                <li>Authentication with optional OTP two-factor login flow.</li>
                <li>Role and permission management for admin operations.</li>
                <li>Notification streaming, DataTables listing, and unread management.</li>
                <li>Notification preferences (in-app, email, SMS with phone validation).</li>
                <li>Secure media uploads and signed file access links.</li>
            </ul>
        </section>

        <section class="card">
            <h2>Developer access</h2>
            <p>
                Swagger documentation is available at
                <a href="{{ url('/api/documentation') }}">/api/documentation</a>.
            </p>
            <p>
                Most API responses use the envelope:
                <strong>{ success, message, data }</strong> (except DataTables endpoints).
            </p>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'ASL Food Delivery API') }}. All rights reserved.</p>
    </footer>
</body>
</html>
