# PataConnect

Sistema para apoio a gestao de um canil municipal, com foco em atendimento ao municipe, controle de animais, registros operacionais e previsao inteligente de estoque.

## Visao geral

O PataConnect centraliza rotinas importantes do canil em uma interface unica. A proposta e facilitar o registro de ocorrencias, acompanhar animais em diferentes situacoes, organizar dados administrativos e apoiar decisoes de reposicao de insumos com uso de modelos de inteligencia artificial.

## Funcionalidades

- Autenticacao de usuarios do sistema.
- Cadastro e acompanhamento de fichas de atendimento ao municipe.
- Listagem de animais em quarentena e animais adotados.
- Organizacao de registros de controle relacionados aos animais.
- Dashboard com visualizacao do fluxo de animais.
- Area de almoxarifado para acompanhamento de consumo e estoque.
- Modulo analitico para previsao de consumo de racao, vacinas e agua sanitaria.
- Indicacao de itens criticos, prioridade de compra e estimativa de dias restantes de estoque.

## Inteligencia artificial

O projeto possui um modulo de analise preditiva implementado em Python. O modelo utilizado para previsao de consumo de estoque e baseado em regressao Ridge, com variaveis operacionais do canil, como:

- quantidade de caes por porte;
- quantidade de gatos;
- animais em quarentena;
- rotinas de limpeza;
- filhotes e adultos recem-chegados;
- alertas sanitarios;
- atendimentos externos de vacinacao.

A previsao gerada auxilia na tomada de decisao sobre reposicao de itens essenciais.

## Tecnologias

- PHP 8+
- Python 3
- HTML, CSS e JavaScript
- PostgreSQL, via PDO
- Pandas
- NumPy
- Scikit-learn

## Estrutura do projeto

```text
PataConnect/
├── analytics/          # Modelos, dados sinteticos e pipeline de IA
├── system/             # Autenticacao, conexao com banco e servicos internos
├── views/              # Telas PHP da aplicacao
├── composer.json       # Configuracao PHP do projeto
├── requirements.txt    # Dependencias Python
└── main.py             # Entrada de comunicacao com rotinas Python
```

## Como executar

Entre na pasta do projeto e inicie o servidor PHP:

```powershell
php -S 127.0.0.1:8000 -t views
```

Depois acesse:

```text
http://127.0.0.1:8000
```

## Login inicial

Para ambiente local de desenvolvimento:

```text
usuario: admin
senha: pata123
```

## Dependencias Python

Para instalar as dependencias do modulo analitico:

```powershell
pip install -r requirements.txt
```

## Pipeline analitico

O pipeline de previsao de estoque pode ser executado com:

```powershell
python -m analytics.data_pipeline
```

Ele processa os dados de consumo, treina os modelos e gera as informacoes usadas pelo dashboard de estoque inteligente.

## Banco de dados

A estrutura SQL principal fica em:

```text
system/struct.sql
```

O sistema foi pensado para utilizar PostgreSQL, com conexao configurada por variaveis de ambiente.

## Objetivo academico

Este projeto foi desenvolvido como proposta de sistema para apoiar a administracao de um canil municipal, integrando cadastro, atendimento, acompanhamento de animais e previsao de estoque em uma unica aplicacao.
