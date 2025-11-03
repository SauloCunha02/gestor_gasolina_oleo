# 🏍️ Sistema de Controle de Gasolina e Manutenção

<div align="center">

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

**Sistema completo para gerenciamento de abastecimentos, trocas de óleo e custos de veículos**

[📸 Screenshots](#-screenshots) • [🚀 Instalação](#-instalação) • [📖 Documentação](#-funcionalidades) • [🤝 Contribuir](#-como-contribuir)

</div>

---

## 📋 Sobre o Projeto

Sistema web desenvolvido para controle pessoal de gastos com combustível e manutenção de veículos. Permite registrar abastecimentos, trocas de óleo, visualizar estatísticas detalhadas, gerar relatórios e acompanhar a evolução dos gastos ao longo do tempo.

### ✨ Destaques

- 📊 **Dashboard interativo** com gráficos e estatísticas em tempo real
- ⛽ **Controle de abastecimentos** com cálculo automático de consumo
- 🛢️ **Gestão de trocas de óleo** com alertas inteligentes
- 📈 **Relatórios personalizáveis** com filtros por período
- 📱 **100% Responsivo** otimizado para celulares (especialmente Galaxy A56)
- 🔒 **Sistema de login seguro** com criptografia SHA-256
- 🎨 **Interface moderna** com animações suaves

---

## 🚀 Instalação

### Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor Apache (XAMPP, WAMP, LAMP, etc.)
- Navegador moderno (Chrome, Firefox, Safari, Edge)

### Passo a Passo

1. **Clone o repositório**

bash

git clone https://github.com/seu-usuario/sistema-gasolina.git

cd sistema-gasolina

2. Configure o banco de dados
   
# Crie um banco de dados no MySQL

CREATE DATABASE gasolina_db;

# Importe o arquivo SQL
mysql -u root -p gasolina_db < database.sql

Ou use o phpMyAdmin:

Acesse http://localhost/phpmyadmin

Crie o banco gasolina_db

Importe o arquivo database.sql

3. Configure a conexão
   
   Edite o arquivo config/database.php:
   
$host = 'localhost';
$dbname = 'gasolina_db';
$username = 'root';
$password = ''; // Sua senha do MySQL

   
6. Acesse o sistema

http://localhost/gasolina/login.php

Credenciais padrão:

Usuário: saulo
Senha: senha123


![Tela inicial do app]([url_ou_caminho_da_imagem](https://github.com/SauloCunha02/gestor_gasolina_oleo/blob/main/assets/img1.jpeg))


