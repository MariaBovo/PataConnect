<?php
$current_user = pata_current_user();
if ($current_user === null) {
    $current_user = [
        'full_name' => 'Pata',
        'username' => 'pata',
        'privileged' => false,
    ];
}
$user_role = $current_user['privileged'] ? 'Administrador' : 'Operador';
$user_name = $current_user['full_name'];
$user_initials = pata_user_initials($current_user);
?>
<header class="system-header">
    <div class="header-left">
        <a href="/" class="brand-logo" aria-label="Inicio do Pata">
            <span class="brand-mark">P</span>
            <span>Pata</span>
        </a>
        <div class="user-profile">
            <div class="user-avatar" aria-hidden="true"><?php echo htmlspecialchars($user_initials); ?></div>
            <div class="user-info">
                <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </div>
    </div>
    
    <div class="header-center">
        <div class="live-datetime">
            <span id="sys-clock">Carregando...</span>
        </div>
    </div>

    <div class="header-right">
        
        <a href="#" class="header-icon" title="Notificacoes">🔔</a>

        <a href="#" onclick="alert('Nao implementado')" class="header-icon" title="Configuracoes">⚙️</a>

        <a href="/logout.php" class="logout-link" title="Encerrar sessao">Sair</a>

    </div>
</header>

<script>
    function updateSystemClock() {
        const now = new Date();
        const options = { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        };
        document.getElementById('sys-clock').textContent = now.toLocaleDateString('pt-BR', options);
    }
    updateSystemClock();
    setInterval(updateSystemClock, 1000);
</script>

<style>
    .system-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #1a1c23;
        color: #ffffff;
        padding: 1rem 2rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        border-bottom: 1px solid #333333;
        box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    }
    
    .header-left, .header-center, .header-right {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .brand-logo {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.25rem;
        font-weight: 800;
        color: #f2f5f7;
        text-decoration: none;
        padding-right: 1.5rem;
        border-right: 1px solid #333333;
    }

    .brand-mark {
        display: inline-grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #7cc7aa;
        color: #12141a;
        font-weight: 900;
    }

    .live-datetime {
        color: #adb5bd;
        font-size: 0.85rem;
        font-weight: 500;
        background-color: #12141a;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        border: 1px solid #333333;
    }

    .header-icon {
        color: #adb5bd;
        font-size: 1.2rem;
        text-decoration: none;
        transition: color 0.2s, transform 0.2s;
        display: flex;
        align-items: center;
        cursor: pointer;
    }

    .header-icon:hover {
        color: #ffffff;
        transform: scale(1.1);
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 1px solid rgba(124, 199, 170, 0.45);
        background: #24372f;
        color: #dff8ed;
        font-weight: 800;
        font-size: 0.82rem;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .user-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: #ffffff;
    }

    .user-role {
        font-size: 0.75rem;
        color: #868e96;
    }

    .logout-link {
        color: #f2f5f7;
        border: 1px solid #454a55;
        border-radius: 6px;
        padding: 0.42rem 0.75rem;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 700;
        transition: background-color 0.2s, border-color 0.2s;
    }

    .logout-link:hover {
        background-color: #242730;
        border-color: #7cc7aa;
    }

    @media (max-width: 760px) {
        .system-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 1rem;
        }

        .header-left,
        .header-center,
        .header-right {
            width: 100%;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .brand-logo {
            border-right: 0;
            padding-right: 0;
        }
    }
</style>
