<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Coming Soon' }}</title>
    <style>
        :root {
            --bg: #0b1020;
            --panel: rgba(14, 22, 42, 0.86);
            --line: rgba(255, 255, 255, 0.1);
            --text: #f5f7fb;
            --muted: #afbad3;
            --brand: #f4b400;
            --brand-soft: #ffe39a;
            --glow: rgba(244, 180, 0, 0.24);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            color: var(--text);
            font-family: "Manrope", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(244, 180, 0, 0.22), transparent 26%),
                radial-gradient(circle at bottom right, rgba(91, 153, 255, 0.18), transparent 28%),
                linear-gradient(160deg, #070b16 0%, #0d1324 52%, #05070f 100%);
        }

        .shell {
            width: min(980px, 100%);
            display: grid;
            gap: 24px;
        }

        .hero {
            overflow: hidden;
            position: relative;
            border: 1px solid var(--line);
            border-radius: 32px;
            background: linear-gradient(180deg, rgba(14, 22, 42, 0.96), rgba(9, 14, 28, 0.96));
            box-shadow: 0 24px 90px rgba(0, 0, 0, 0.45);
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(8px);
        }

        .hero::before {
            width: 240px;
            height: 240px;
            top: -100px;
            right: -40px;
            background: var(--glow);
        }

        .hero::after {
            width: 180px;
            height: 180px;
            bottom: -70px;
            left: -40px;
            background: rgba(91, 153, 255, 0.18);
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
            gap: 22px;
            padding: 34px;
        }

        .kicker,
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid rgba(244, 180, 0, 0.16);
            background: rgba(244, 180, 0, 0.1);
            color: var(--brand-soft);
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            padding: 10px 14px;
            text-transform: uppercase;
        }

        h1 {
            margin: 18px 0 14px;
            font-size: clamp(2.6rem, 6vw, 4.9rem);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.75;
            font-size: 1.04rem;
        }

        .copy {
            display: grid;
            gap: 18px;
            align-content: center;
        }

        .panel {
            display: grid;
            gap: 16px;
            align-content: start;
            padding: 24px;
            border-radius: 24px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(10px);
        }

        .badge {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.08);
            color: var(--text);
        }

        .list {
            display: grid;
            gap: 12px;
        }

        .item {
            padding: 14px 15px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: var(--muted);
        }

        .item strong {
            display: block;
            margin-bottom: 4px;
            color: var(--text);
            font-size: 0.98rem;
        }

        .footer-note {
            text-align: center;
            color: var(--muted);
            font-size: 0.95rem;
        }

        code {
            padding: 3px 7px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
        }

        @media (max-width: 860px) {
            .hero-grid {
                grid-template-columns: 1fr;
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="hero">
            <div class="hero-grid">
                <div class="copy">
                    <span class="kicker">Coming Soon</span>
                    <div>
                        <h1>GharKaam is getting ready for launch.</h1>
                        <p>
                            Hum final polish, listings, aur user experience ko production-ready bana rahe hain.
                            Thori dair mein yahan full marketplace live hogi.
                        </p>
                    </div>
                    <span class="badge">Launch mode is currently active</span>
                </div>

                <aside class="panel">
                    <span class="badge">What is being prepared</span>
                    <div class="list">
                        <div class="item">
                            <strong>Verified service listings</strong>
                            Cleaner categories, better discovery, aur sharper presentation.
                        </div>
                        <div class="item">
                            <strong>Booking experience</strong>
                            Customer journey ko launch ke liye refine kiya ja raha hai.
                        </div>
                        <div class="item">
                            <strong>Final production checks</strong>
                            Performance, deployment, aur content finishing touches.
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <div class="footer-note">
            Jab site ready ho jaye to <code>COMING_SOON=false</code> kar dein aur website normal mode mein aa jayegi.
        </div>
    </main>
</body>
</html>
