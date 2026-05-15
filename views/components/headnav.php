<?php
$current_user = [
    'name' => 'Gustavo Bonin Gava',
    'role' => 'Veterinário',
    'avatar' => 'https://ui-avatars.com/api/?name=Gustavo+Gava&background=2a2d35&color=fff' 
];
?>
<header class="system-header">
    <div class="header-left">
        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($current_user['avatar']); ?>" alt="User Avatar">
            <div class="user-info">
                <span class="user-role"><?php echo htmlspecialchars($current_user['role']); ?></span>
                <span class="user-name"><?php echo htmlspecialchars($current_user['name']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="header-center">
        <div class="live-datetime">
            <span id="sys-clock">Carregando...</span>
        </div>
    </div>

    <div class="header-right">
        
        <a href="#" class="header-icon" title="Notifications">🔔</a>

        <a href="#" onclick="alert('Não implementado')" class="header-icon" title="System Settings">⚙️</a>

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
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #e8f4f8;
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
        padding-right: 1.5rem;
    }

    .user-profile img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 2px solid #333333;
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
</style>