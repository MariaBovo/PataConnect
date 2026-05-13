import streamlit as st
from datetime import datetime


# ── Dados em sessão (substitua por DB real depois) ─────────────────────────
def _init_state():
    if "pet_registros" not in st.session_state:
        st.session_state["pet_registros"] = []


def _get_pending():
    return [r for r in st.session_state["pet_registros"] if r["status"] == "Pendente"]


def _get_all():
    return st.session_state["pet_registros"]


def _add_registro(data: dict):
    data["id"] = len(st.session_state["pet_registros"]) + 1
    data["status"] = "Pendente"
    data["criado_em"] = datetime.now().strftime("%d/%m/%Y %H:%M")
    st.session_state["pet_registros"].append(data)


# ── CSS da página ──────────────────────────────────────────────────────────
_CSS = """
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

/* Escopo: tudo prefixado com .crp- para não vazar */
.crp-page {
    font-family: 'DM Sans', 'Segoe UI', sans-serif;
    max-width: 860px;
    margin: 0 auto;
    padding: 0 8px;
}

.crp-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
    padding-bottom: 18px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.crp-header-icon {
    font-size: 2.2rem;
    background: linear-gradient(135deg, #e85d26, #ff9a6c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.crp-header-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #f5f5f5;
    margin: 0;
    letter-spacing: -0.02em;
}

.crp-header-sub {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.45);
    margin: 0;
}

/* Tabs estilo pill */
.crp-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 28px;
    background: rgba(255,255,255,0.04);
    border-radius: 12px;
    padding: 5px;
}

/* Formulário */
.crp-form-section {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 20px;
}

.crp-section-label {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #ff7a3c;
    margin-bottom: 16px;
}

/* Cards de registro pendente */
.crp-pending-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-left: 3px solid #ff7a3c;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.crp-pending-name {
    font-weight: 600;
    font-size: 1rem;
    color: #f0f0f0;
}

.crp-pending-meta {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.4);
    margin-top: 2px;
}

.crp-badge-pendente {
    background: rgba(255, 180, 0, 0.12);
    border: 1px solid rgba(255, 180, 0, 0.35);
    color: #ffb400;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.07em;
    padding: 3px 10px;
    border-radius: 100px;
    text-transform: uppercase;
    white-space: nowrap;
}

.crp-badge-aprovado {
    background: rgba(0, 200, 120, 0.12);
    border: 1px solid rgba(0, 200, 120, 0.3);
    color: #00c87a;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 100px;
    text-transform: uppercase;
    white-space: nowrap;
}

.crp-empty {
    text-align: center;
    padding: 48px 0;
    color: rgba(255,255,255,0.25);
    font-size: 0.9rem;
}

.crp-empty-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
    display: block;
}
</style>
"""


# ── Página principal ────────────────────────────────────────────────────────
def render_crud_pet_page():
    _init_state()
    st.markdown(_CSS, unsafe_allow_html=True)

    # Header + botão voltar
    col_back, col_void = st.columns([1, 5])
    with col_back:
        if st.button("← Voltar", key="btn_voltar_crp"):
            st.session_state["page"] = "dashboard"
            st.rerun()

    st.markdown("""
    <div class="crp-page">
        <div class="crp-header">
            <span class="crp-header-icon">🐾</span>
            <div>
                <p class="crp-header-title">Registro de Animal</p>
                <p class="crp-header-sub">Cadastre novos animais e acompanhe pendências</p>
            </div>
        </div>
    </div>
    """, unsafe_allow_html=True)

    # Tabs
    tab_form, tab_pending, tab_all = st.tabs([
        "📋  Novo Registro",
        f"⏳  Pendentes ({len(_get_pending())})",
        f"📁  Todos os Registros ({len(_get_all())})"
    ])

    # ── TAB 1: Formulário ──────────────────────────────────────────────────
    with tab_form:
        st.markdown("<div style='height:12px'></div>", unsafe_allow_html=True)

        with st.form("form_registro_animal", clear_on_submit=True):

            st.markdown('<p class="crp-section-label">🐶 Dados do Animal</p>', unsafe_allow_html=True)

            col1, col2 = st.columns(2)
            with col1:
                nome = st.text_input("Nome do Animal *", placeholder="Ex: Rex")
                especie = st.selectbox("Espécie *", [
                    "Selecione...", "Cachorro", "Gato", "Pássaro",
                    "Coelho", "Hamster", "Réptil", "Outro"
                ])
                sexo = st.radio("Sexo", ["Macho", "Fêmea", "Indefinido"], horizontal=True)

            with col2:
                raca = st.text_input("Raça", placeholder="Ex: Labrador")
                idade = st.number_input("Idade estimada (anos)", min_value=0.0,
                                        max_value=50.0, step=0.5, value=1.0)
                porte = st.selectbox("Porte", ["Pequeno", "Médio", "Grande"])

            cor_pelagem = st.text_input("Cor / Pelagem", placeholder="Ex: Preto e branco")
            observacoes = st.text_area("Observações / Condição de saúde",
                                       placeholder="Descreva qualquer detalhe relevante...",
                                       height=100)

            st.divider()
            st.markdown('<p class="crp-section-label">👤 Dados do Responsável</p>', unsafe_allow_html=True)

            col3, col4 = st.columns(2)
            with col3:
                tutor_nome = st.text_input("Nome do Tutor *", placeholder="Ex: Maria Souza")
                tutor_cpf = st.text_input("CPF", placeholder="000.000.000-00")
            with col4:
                tutor_tel = st.text_input("Telefone *", placeholder="(11) 99999-9999")
                tutor_email = st.text_input("E-mail", placeholder="exemplo@email.com")

            st.divider()

            col_sub, col_void = st.columns([2, 3])
            with col_sub:
                submitted = st.form_submit_button(
                    "✔  Enviar Registro",
                    use_container_width=True,
                    type="primary"
                )

        if submitted:
            # Validação básica
            erros = []
            if not nome.strip():
                erros.append("Nome do animal é obrigatório.")
            if especie == "Selecione...":
                erros.append("Selecione a espécie.")
            if not tutor_nome.strip():
                erros.append("Nome do tutor é obrigatório.")
            if not tutor_tel.strip():
                erros.append("Telefone é obrigatório.")

            if erros:
                for e in erros:
                    st.error(e)
            else:
                _add_registro({
                    "nome": nome,
                    "especie": especie,
                    "raca": raca or "—",
                    "sexo": sexo,
                    "idade": f"{idade} anos",
                    "porte": porte,
                    "cor": cor_pelagem or "—",
                    "obs": observacoes or "—",
                    "tutor": tutor_nome,
                    "cpf": tutor_cpf or "—",
                    "tel": tutor_tel,
                    "email": tutor_email or "—",
                })
                st.success(f"✅ Animal **{nome}** registrado com sucesso! Aguardando aprovação.")
                st.balloons()

    # ── TAB 2: Pendentes ───────────────────────────────────────────────────
    with tab_pending:
        st.markdown("<div style='height:12px'></div>", unsafe_allow_html=True)
        pendentes = _get_pending()

        if not pendentes:
            st.markdown("""
            <div class="crp-empty">
                <span class="crp-empty-icon">✅</span>
                Nenhum registro pendente no momento.
            </div>
            """, unsafe_allow_html=True)
        else:
            for reg in reversed(pendentes):
                col_info, col_action = st.columns([5, 1])
                with col_info:
                    st.markdown(f"""
                    <div class="crp-pending-card">
                        <div>
                            <div class="crp-pending-name">🐾 {reg['nome']} &nbsp;·&nbsp;
                                <span style="font-weight:400;color:rgba(255,255,255,0.55)">
                                    {reg['especie']} · {reg['raca']}
                                </span>
                            </div>
                            <div class="crp-pending-meta">
                                👤 {reg['tutor']} &nbsp;|&nbsp; 📞 {reg['tel']}
                                &nbsp;|&nbsp; 🕐 {reg['criado_em']}
                            </div>
                        </div>
                        <span class="crp-badge-pendente">Pendente</span>
                    </div>
                    """, unsafe_allow_html=True)
                with col_action:
                    if st.button("Aprovar", key=f"aprovar_{reg['id']}"):
                        reg["status"] = "Aprovado"
                        st.rerun()

    # ── TAB 3: Todos ───────────────────────────────────────────────────────
    with tab_all:
        st.markdown("<div style='height:12px'></div>", unsafe_allow_html=True)
        todos = _get_all()

        if not todos:
            st.markdown("""
            <div class="crp-empty">
                <span class="crp-empty-icon">📁</span>
                Nenhum registro encontrado.<br>Use a aba <b>Novo Registro</b> para começar.
            </div>
            """, unsafe_allow_html=True)
        else:
            # Filtro rápido
            col_f1, col_f2, _ = st.columns([2, 2, 2])
            with col_f1:
                filtro_status = st.selectbox(
                    "Filtrar por status",
                    ["Todos", "Pendente", "Aprovado"],
                    key="filtro_status_crp"
                )
            with col_f2:
                filtro_especie = st.selectbox(
                    "Filtrar por espécie",
                    ["Todas"] + list({r["especie"] for r in todos}),
                    key="filtro_esp_crp"
                )

            registros_filtrados = [
                r for r in todos
                if (filtro_status == "Todos" or r["status"] == filtro_status)
                and (filtro_especie == "Todas" or r["especie"] == filtro_especie)
            ]

            if not registros_filtrados:
                st.info("Nenhum resultado para os filtros selecionados.")
            else:
                for reg in reversed(registros_filtrados):
                    badge = (
                        '<span class="crp-badge-aprovado">Aprovado</span>'
                        if reg["status"] == "Aprovado"
                        else '<span class="crp-badge-pendente">Pendente</span>'
                    )
                    with st.expander(f"🐾  {reg['nome']}  ·  {reg['especie']}  ·  {reg['criado_em']}"):
                        c1, c2 = st.columns(2)
                        with c1:
                            st.markdown(f"**Raça:** {reg['raca']}")
                            st.markdown(f"**Sexo:** {reg['sexo']}")
                            st.markdown(f"**Idade:** {reg['idade']}")
                            st.markdown(f"**Porte:** {reg['porte']}")
                            st.markdown(f"**Cor/Pelagem:** {reg['cor']}")
                        with c2:
                            st.markdown(f"**Tutor:** {reg['tutor']}")
                            st.markdown(f"**CPF:** {reg['cpf']}")
                            st.markdown(f"**Telefone:** {reg['tel']}")
                            st.markdown(f"**E-mail:** {reg['email']}")
                        st.markdown(f"**Observações:** {reg['obs']}")
                        st.markdown(f"**Status:** {badge}", unsafe_allow_html=True)
