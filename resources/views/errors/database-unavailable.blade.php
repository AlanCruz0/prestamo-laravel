<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Servicio no disponible</title>
        <style>
            :root {
                color-scheme: light;
                --bg: #f4efe8;
                --card: rgba(255, 255, 255, 0.92);
                --text: #1f2937;
                --muted: #6b7280;
                --accent: #b45309;
                --accent-dark: #92400e;
                --border: rgba(180, 83, 9, 0.16);
                --shadow: 0 24px 60px rgba(31, 41, 55, 0.14);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 24px;
                font-family: Georgia, "Times New Roman", serif;
                color: var(--text);
                background:
                    radial-gradient(circle at top left, rgba(180, 83, 9, 0.18), transparent 36%),
                    radial-gradient(circle at bottom right, rgba(146, 64, 14, 0.16), transparent 32%),
                    linear-gradient(135deg, #f8f3ec 0%, var(--bg) 100%);
            }

            main {
                width: min(100%, 760px);
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 28px;
                padding: 48px 32px;
                box-shadow: var(--shadow);
                text-align: center;
                backdrop-filter: blur(8px);
            }

            .badge {
                display: inline-block;
                margin-bottom: 18px;
                padding: 8px 14px;
                border-radius: 999px;
                background: rgba(180, 83, 9, 0.12);
                color: var(--accent-dark);
                font-size: 0.9rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            h1 {
                margin: 0 0 16px;
                font-size: clamp(2rem, 4vw, 3.4rem);
                line-height: 1.05;
            }

            p {
                margin: 0 auto;
                max-width: 560px;
                font-size: 1.05rem;
                line-height: 1.7;
                color: var(--muted);
            }

            .actions {
                display: flex;
                justify-content: center;
                gap: 14px;
                flex-wrap: wrap;
                margin-top: 32px;
            }

            a,
            button {
                appearance: none;
                border: none;
                border-radius: 999px;
                padding: 14px 22px;
                font: inherit;
                cursor: pointer;
                text-decoration: none;
                transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
            }

            .primary {
                background: var(--accent);
                color: #fff;
                box-shadow: 0 12px 28px rgba(180, 83, 9, 0.24);
            }

            .secondary {
                background: rgba(255, 255, 255, 0.75);
                color: var(--text);
                border: 1px solid rgba(31, 41, 55, 0.12);
            }

            a:hover,
            button:hover {
                transform: translateY(-1px);
            }

            .details {
                margin-top: 28px;
                font-size: 0.95rem;
                color: var(--muted);
            }

            @media (max-width: 640px) {
                main {
                    padding: 36px 22px;
                }

                .actions {
                    flex-direction: column;
                }

                a,
                button {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <main>
            <div class="badge">Error 503</div>
            <h1>No pudimos conectar con el servicio</h1>
            <p>
                Por favor, contacte al administrador e intente nuevamente en unos minutos.
            </p>

            <div class="actions">
                <a class="primary" href="{{ url('/') }}">Volver al inicio</a>
            </div>

            <p class="details">
                Si el problema persiste, comparta al administrador la hora en la que ocurrio este error.
            </p>
        </main>
    </body>
</html>