<!DOCTYPE html>
<html>
<?php
require_once('../components/card.php');
require_once('../components/head.php');
?>
<body>
    <?php require('../components/headnav.php');?>
    <div class="registration-wrapper">
        <div class="form-card">
            <div class="header-actions">
                <a href="/dispatch"><button class="btn-action btn-secondary">Voltar</button></a>
            </div>
            <div class="form-header">
                <h2>Cadastro de Novo Resgate</h2>
                <p>Registre os dados do chamador e informações do incidente para o despacho da equipe de resgate.</p>
            </div>
            <form action="#" method="POST">
                
                <h3 class="form-section-title">Informações do Chamador</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="caller_name">Nome Completo</label>
                        <input type="text" id="caller_name" name="caller_name" class="form-control" placeholder="ex.: Maria Santos" required>
                    </div>
                    <div class="form-group">
                        <label for="caller_phone">Telefone</label>
                        <input type="tel" id="caller_phone" name="caller_phone" class="form-control" placeholder="(00) 00000-0000" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="incident_location">Local do Incidente / Endereço</label>
                    <input type="text" id="incident_location" name="incident_location" class="form-control" placeholder="Nome da rua, número, bairro e pontos de referência" required>
                </div>

                <h3 class="form-section-title">Detalhes do Incidente</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="call_reason">Motivo da Chamada</label>
                        <select id="call_reason" name="call_reason" class="form-control" required>
                            <option value="" disabled selected>Selecione o motivo…</option>
                            <option value="stray">Animal de Rua (Agressivo)</option>
                            <option value="injured">Resgate de Animal Ferido/Doente</option>
                            <option value="abuse">Denúncia de Maus‑tratos/Negligência</option>
                            <option value="surrender">Entrega de Proprietário</option>
                            <option value="other">Outra Consulta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="animal_type">Tipo de Animal</label>
                        <select id="animal_type" name="animal_type" class="form-control" required>
                            <option value="" disabled selected>Selecione a espécie…</option>
                            <option value="dog">Canino</option>
                            <option value="cat">Felino</option>
                            <option value="equine">Equino</option>
                            <option value="wildlife">Selvagem</option>
                            <option value="multiple">Múltiplos Animais</option>
                            <option value="uni">Universitário</option>
                            <option value="unknown">Desconhecido</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label>Nível de Prioridade</label>
                    <div class="urgency-group">
                        <label class="urgency-label">
                            <input type="radio" name="priority" value="low" checked> Baixa
                        </label>
                        <label class="urgency-label">
                            <input type="radio" name="priority" value="medium"> Média
                        </label>
                        <label class="urgency-label" style="color: #fa5252; font-weight: bold;">
                            <input type="radio" name="priority" value="high"> Alta (Emergência/Fudeu)
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Descrição & Observações Adicionais</label>
                    <textarea id="notes" name="notes" class="form-control" placeholder="Descreva a cor, tamanho, condição do animal e quaisquer instruções específicas para a equipe de resgate…"></textarea>
                </div>

                <button type="submit" class="btn-submit">Registrar Chamada de Despacho</button>
            </form>
        </div>
    </div>
</body>
<style>
    .registration-wrapper {
        max-width: 800px;
        margin: 2rem auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #ffffff;
    }

    .form-card {
        background-color: #1a1c23;
        border: 1px solid #333333;
        border-radius: 0.5rem;
        padding: 2.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .form-header {
        margin-bottom: 2rem;
        border-bottom: 1px solid #333333;
        padding-bottom: 1rem;
    }

    .form-header h2 {
        margin: 0 0 0.5rem 0;
        color: #e8f4f8;
        font-size: 1.5rem;
    }

    .form-header p {
        margin: 0;
        color: #868e96;
        font-size: 0.9rem;
    }

    .form-section-title {
        color: #e8f4f8;
        font-size: 1.1rem;
        margin: 1.5rem 0 1rem 0;
        font-weight: 600;
    }

    .form-row {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #adb5bd;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        padding: 0.75rem 1rem;
        border-radius: 0.25rem;
        border: 1px solid #333333;
        background-color: #12141a;
        color: #ffffff;
        font-size: 1rem;
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: #6c757d;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    .urgency-group {
        display: flex;
        gap: 1.5rem;
        margin-top: 0.5rem;
    }

    .urgency-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        color: #e8f4f8;
        cursor: pointer;
    }

    .urgency-label input[type="radio"] {
        accent-color: #e8f4f8;
        width: 1.2rem;
        height: 1.2rem;
        cursor: pointer;
    }

    .btn-submit {
        background-color: #2b8a3e;
        color: white;
        border: none;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: 700;
        border-radius: 0.25rem;
        cursor: pointer;
        width: 100%;
        margin-top: 2rem;
        transition: background-color 0.2s;
    }

    .btn-submit:hover {
        background-color: #237032;
    }

    .header-actions {
        text-align:end;
        gap: 1rem;
    }

    .btn-action {
        padding: 0.4rem 0.8rem;
        border-radius: 0.25rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .btn-secondary {
        background-color: transparent;
        color: #e8f4f8;
        border: 1px solid #6c757d;
    }

    .btn-secondary:hover {
        background-color: #333333;
        color: white;
        border-color: #adb5bd;
    }
</style>
</html>