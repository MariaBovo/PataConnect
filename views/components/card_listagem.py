import streamlit as st
import time

# Configuração da página
st.set_page_config(page_title="Sistema de Relatórios", layout="centered")

# Título do Projeto
st.title("📊 Painel de Controle de Dados")
st.subheader("FATEC Rio Claro - Projeto Acadêmico")

st.write("---")

# --- LÓGICA DE CENTRALIZAÇÃO ---
# Criamos 3 colunas. O botão ficará na coluna do meio (col2).
# Os números [1, 1, 1] garantem que as colunas laterais tenham o mesmo tamanho.
col1, col2, col3 = st.columns([1, 1, 1])

with col2:
    # use_container_width faz o botão ocupar toda a largura da coluna central
    botao_relatorio = st.button("Gerar Relatórios", use_container_width=True)

# --- AÇÃO DO BOTÃO ---
if botao_relatorio:
    with st.status("Processando dados...", expanded=True) as status:
        st.write("Buscando informações no banco de dados...")
        time.sleep(1)
        st.write("Formatando tabelas...")
        time.sleep(1)
        status.update(label="Relatório Concluído!", state="complete", expanded=False)
    
    st.success("O relatório foi gerado com sucesso!")
    
    # Exemplo de conteúdo que apareceria após o clique
    st.info("Aqui você poderia exibir gráficos do Matplotlib ou tabelas do Pandas.")

st.write("---")