<?php
require_once __DIR__ . '/../system/auth.php';

$method = pata_request_method();

$error = null;
$notice = isset($_GET['logged_out']) ? 'Sessao encerrada com seguranca.' : null;
$redirect = pata_sanitize_redirect($_GET['redirect'] ?? '/');

if ($method === 'DELETE') {
    if (!pata_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Sessao expirada. Tente novamente.';
    } else {
        pata_logout();
        header('Location: /login?logged_out=1');
        exit;
    }
}

if ($method === 'GET' && pata_is_authenticated()) {
    header('Location: ' . $redirect);
    exit;
}

if ($method === 'POST') {
    $redirect = pata_sanitize_redirect($_POST['redirect'] ?? '/');

    if (!pata_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Sessao expirada. Tente entrar novamente.';
    } else {
        $result = pata_login($_POST['username'] ?? '', $_POST['password'] ?? '');

        if ($result['ok']) {
            header('Location: ' . $redirect);
            exit;
        }

        $error = $result['message'];
    }
} elseif (!in_array($method, ['GET', 'DELETE'], true)) {
    http_response_code(405);
    $error = 'Metodo HTTP nao permitido para esta tela.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pata - Entrar</title>
    <style>
        :root {
            --ink: #17201d;
            --muted: #60736b;
            --line: #dbe4df;
            --paper: #fffdfa;
            --soft: #f3f7f3;
            --teal: #2f8f7a;
            --teal-dark: #1f5f53;
            --coral: #df715c;
            --green: #7cc7aa;
            --shadow: 0 24px 70px rgba(23, 32, 29, 0.18);
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--paper);
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(390px, 470px);
        }

        .auth-visual {
            position: relative;
            display: flex;
            align-items: flex-end;
            min-height: 100vh;
            padding: clamp(2rem, 5vw, 4.5rem);
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(17, 30, 26, 0.80) 0%, rgba(17, 30, 26, 0.54) 44%, rgba(17, 30, 26, 0.10) 100%),
                url("/assets/auth-hero.png") center / cover no-repeat;
        }

        .brand-block {
            position: relative;
            z-index: 1;
            width: min(620px, 100%);
            color: #ffffff;
        }

        .brand-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            margin-bottom: 1.25rem;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #dff8ed;
        }

        .brand-mark {
            display: inline-grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: var(--green);
            color: #10231d;
            font-size: 1.35rem;
            font-weight: 900;
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.18);
        }

        .brand-block h1 {
            max-width: 680px;
            margin: 0;
            font-size: clamp(3.6rem, 9vw, 7.4rem);
            line-height: 0.86;
            font-weight: 900;
        }

        .brand-block p {
            max-width: 560px;
            margin: 1.4rem 0 0;
            color: rgba(255, 255, 255, 0.86);
            font-size: clamp(1rem, 1.5vw, 1.2rem);
            line-height: 1.6;
        }

        .auth-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: clamp(1.5rem, 4vw, 3rem);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.86), rgba(243, 247, 243, 0.98)),
                var(--soft);
            border-left: 1px solid rgba(47, 143, 122, 0.14);
        }

        .login-card {
            width: 100%;
            max-width: 390px;
        }

        .login-card header {
            margin-bottom: 1.6rem;
        }

        .eyebrow {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--teal);
            font-size: 0.78rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .login-card h2 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.1;
        }

        .login-card p {
            margin: 0.7rem 0 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .alert {
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            font-size: 0.92rem;
            line-height: 1.4;
        }

        .alert-error {
            color: #7c2531;
            background: #fff0f0;
            border: 1px solid #f0b9bd;
        }

        .alert-success {
            color: #175f46;
            background: #edf9f2;
            border: 1px solid #a7dec0;
        }

        .auth-form {
            display: grid;
            gap: 1rem;
            padding: 1.35rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: var(--shadow);
        }

        .form-group {
            display: grid;
            gap: 0.45rem;
        }

        label {
            color: #33443e;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .control {
            width: 100%;
            min-height: 48px;
            padding: 0.78rem 0.9rem;
            color: var(--ink);
            border: 1px solid #cfdcd5;
            border-radius: 8px;
            background: #fbfefa;
            font: inherit;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .control:focus {
            outline: none;
            border-color: var(--teal);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(47, 143, 122, 0.14);
        }

        .submit-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            margin-top: 0.35rem;
            border: 0;
            border-radius: 8px;
            background: var(--teal);
            color: #ffffff;
            font: inherit;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(47, 143, 122, 0.28);
            transition: background-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
        }

        .submit-button:hover {
            background: var(--teal-dark);
            transform: translateY(-1px);
            box-shadow: 0 18px 36px rgba(47, 143, 122, 0.34);
        }

        .support-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            color: var(--muted);
            font-size: 0.86rem;
        }

        .support-row strong {
            color: var(--coral);
        }

        @media (max-width: 900px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-visual {
                min-height: 42vh;
                padding: 2rem;
                align-items: flex-end;
            }

            .brand-block h1 {
                font-size: clamp(3.5rem, 18vw, 5.5rem);
            }

            .auth-panel {
                min-height: auto;
                border-left: 0;
                padding: 2rem 1.2rem 2.4rem;
            }
        }

        @media (max-width: 520px) {
            .auth-visual {
                min-height: 34vh;
            }

            .brand-block p {
                display: none;
            }

            .login-card h2 {
                font-size: 1.65rem;
            }

            .auth-form {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="auth-visual" aria-label="Pata">
            <div class="brand-block">
                <div class="brand-chip">
                    <span class="brand-mark">P</span>
                    <span>Canil Municipal</span>
                </div>
                <h1>Pata</h1>
                <p>Gestao municipal com cuidado, rastreabilidade e resposta rapida para a equipe.</p>
            </div>
        </section>

        <aside class="auth-panel">
            <div class="login-card">
                <header>
                    <span class="eyebrow">Acesso restrito</span>
                    <h2>Entrar no Pata</h2>
                    <p>Acesse com as credenciais liberadas para a equipe autorizada.</p>
                </header>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif ($notice): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
                <?php endif; ?>

                <form class="auth-form" method="POST" action="/login" autocomplete="on">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                    <div class="form-group">
                        <label for="username">Usuario</label>
                        <input class="control" id="username" name="username" type="text" autocomplete="username" placeholder="ex.: gustavo" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Senha</label>
                        <input class="control" id="password" name="password" type="password" autocomplete="current-password" placeholder="Digite sua senha" required>
                    </div>

                    <button class="submit-button" type="submit">Entrar</button>

                    <div class="support-row">
                        <span>Projeto <strong>Pata</strong></span>
                        <span>Sessao protegida</span>
                    </div>
                </form>
            </div>
        </aside>
    </main>
</body>
</html>
