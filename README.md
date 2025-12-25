# Módulo de Orçamentos (Independente) - Mapos

![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue?style=flat-square&logo=php)
![Mapos Compatible](https://img.shields.io/badge/Mapos-Compatible-green?style=flat-square&logo=codeigniter)
![License](https://img.shields.io/badge/license-MIT-orange?style=flat-square)
![Status](https://img.shields.io/badge/status-active-success?style=flat-square)

Este é um módulo avançado de gestão de orçamentos projetado para funcionar de forma **independente** dentro do ecossistema Mapos.

### 🛡️ Principais Diferenciais
- **Blindado contra Atualizações:** Por residir em uma pasta separada (`/modulos`), você pode atualizar o núcleo do Mapos tranquilamente; este módulo **não será sobrescrito ou afetado**.
- **Design Moderno:** Interface otimizada e responsiva, inspirada nos padrões mais recentes.
- **Motor de Cálculo Avançado:** Realiza cálculos automáticos em tempo real, com suporte nativo a **Taxas/Comissões (%)** por item e geração de PDF profissional.
- **Inteligência Artificial:** Integração nativa com **Google Gemini** para sugestão inteligente de preços de mercado.
- **Integração Total:** Apesar de independente nos arquivos, ele lê e grava diretamente no banco de dados oficial do Mapos (clientes, produtos, serviços), garantindo integridade dos dados.
- **Otimizado para Dispositivos Móveis:** Layout responsivo e tabelas ajustadas para visualização perfeita em qualquer tamanho de tela (celulares e tablets).


### 🖼️ Screenshots (Algumas telas do sistema)

#### Dashboard (Tela Inicial com Resumo)
<img src="Screenshots/01.PNG" width="100%" alt="Dashboard" />
<br><br>

#### Detalhes do Orçamento
<img src="Screenshots/02.PNG" width="100%" alt="Detalhes do Orçamento" />
<br><br>

#### Edição com Inteligência Artificial
<img src="Screenshots/03.PNG" width="100%" alt="Edição com IA" />
<br>

---
<!-- ... (resto do arquivo ) ... -->


---

## 💻 Requisitos do Sistema
- **PHP:** Versão 8.3 ou superior (Requisito do Mapos Core).
- **Mapos:** Compatível com a versão mais recente.

---

## 🚀 Instalação e Acesso

### 1. Instalação
1. Vá até a **raiz** da instalação do seu Mapos (onde ficam as pastas `application`, `assets`, etc).
2. Verifique se existe uma pasta chamada `modulos`. **Se não existir, crie-a.**
3. Copie a pasta `orcamentos` inteira para dentro dessa pasta `modulos`.

O caminho final deve ficar assim:  
`seusistema / modulos / orcamentos`

### 2. Como Acessar (Importante ⚠️)
Este módulo é protegido pelo sistema de segurança do Mapos. **Não é possível acessá-lo sem estar logado.**

1. Faça **login no Mapos principal** normalmente.
2. Acesse a URL de instalação para configurar o banco de dados automaticamente:
   `http://seusistema/modulos/orcamentos/install.php`
3. Siga as instruções na tela para criar as tabelas e verificar as dependências.
4. Após concluir, você será redirecionado para a lista de orçamentos.
5. **Segurança:** Após a instalação, apague o arquivo `install.php` do servidor.

## 🛠️ Instalação Manual (Método Alternativo)

Se você preferir fazer tudo manualmente ou se o instalador automático falhar:

### 🗄️ Banco de Dados (Manual)
1. Localize o arquivo `instalar_tabelas_orcamento.sql` na raiz desta pasta `orcamentos`.
2. Importe este arquivo para o banco de dados do seu Mapos.

## 📦 Dependências (PDF)

Este módulo utiliza a biblioteca **DomPDF** para gerar os arquivos PDF. É necessário instalá-la via Composer.

### Instalação Automática (Recomendada)
Este módulo possui seu próprio gerenciador de dependências para garantir isolamento total.

1. Navegue até a pasta do módulo via terminal:
   `cd seusistema/modulos/orcamentos`
2. Execute o comando para instalar as dependências locais:
```bash
composer install
```
Isso criará a pasta `vendor` **dentro do módulo**, garantindo que ele funcione independentemente das bibliotecas do Mapos principal.

### Instalação Manual (Sem Composer)
Caso não possa usar o Composer, você precisará baixar a biblioteca manualmente:
1. Baixe o release mais recente em [DomPDF Releases](https://github.com/dompdf/dompdf/releases).
2. Extraia o conteúdo e coloque em uma pasta acessível.
3. Você precisará ajustar o `require '../vendor/autoload.php';` no arquivo `gerar_pdf.php` para apontar para o local onde você salvou a biblioteca.

## 🔗 Integração no Menu (Opcional)

Para facilitar o acesso, você pode adicionar um botão no menu lateral do Mapos.
**Nota:** Como o menu faz parte do "core" do Mapos, essa alteração pode ser perdida se você atualizar o sistema.

1. Edite o arquivo: `application/views/tema/menu.php`
2. Procure o local onde quer inserir o botão (ex: abaixo de "Vendas").
3. Cole o seguinte código:

```php
<!-- Botão Módulo Orçamentos -->
<li class="<?= (strpos($_SERVER['REQUEST_URI'], 'modulos/orcamentos') !== false) ? 'active' : '' ?>">
    <a class="tip-bottom" title="Ir para Módulo de Orçamentos" href="<?= base_url() ?>modulos/orcamentos/listar_orcamentos.php">
        <i class='bx bx-file-blank iconX'></i>
        <span class="title">Orçamentos (Módulo)</span>
    </a>
</li>
```

> **⚠️ Atenção:** Como o arquivo `menu.php` pertence ao núcleo do Mapos, ele pode ser sobrescrito em uma atualização do sistema, fazendo o botão sumir. Se isso acontecer, basta refazer este passo.

## ✨ Inteligência Artificial (Configuração)

Após instalar o módulo, você pode ativar os recursos de IA para auxiliar na precificação de orçamentos.

**Funcionalidades:**
- **Sugestão de Preços:** A IA analisa a descrição do item e sugere um preço médio de mercado.
- **Rotação de Chaves de API:** Sistema inteligente que alterna entre múltiplas chaves configuradas para evitar bloqueios por limite de uso.
- **Modelo Otimizado:** Utiliza por padrão o **Gemini 2.5 Flash Lite** e **gemini-2.5-flash**.

### 🔑 Configurando a IA (Passo a Passo)

Para utilizar os recursos de inteligência artificial, você precisará de uma chave de API do Google Gemini. É gratuito (com limites generosos) e fácil de obter.

#### 1. Obtendo a Chave de API
1.  Acesse o [Google AI Studio](https://aistudio.google.com/app/apikey).
2.  Faça login com sua conta Google.
3.  Clique no botão **"Create API key"**.
4.  Copie o código gerado (começa com `AIza...`).

#### 2. Configurando no Sistema
1.  Vá até a pasta do módulo: `modulos/orcamentos/orcamentos/`.
2.  Abra o arquivo `config_ia.php` num editor de texto.
3.  Localize a linha que define as chaves:
    ```php
    define('GEMINI_API_KEYS', [
        'COLE_SUA_CHAVE_AQUI',
    ]);
    ```
4.  Cole a chave que você copiou do Google. Salve o arquivo.

#### 3. Ativando/Desativando a IA
No mesmo arquivo `config_ia.php`, você encontrará a opção:
```php
define('IA_ENABLED', true); // true = Ativado | false = Desativado
```
Se precisar desabilitar os recursos de IA temporariamente, basta mudar para `false`.

## 💾 Backup e Segurança

### Backup do Banco de Dados
Embora o backup geral do Mapos já inclua todas as tabelas (inclusive as deste módulo), o módulo possui uma ferramenta dedicada para gerar backup **apenas das tabelas de orçamento**.
- Recomenda-se realizar este backup antes de atualizações críticas.

### Backup dos Arquivos
Antes de atualizar o Mapos, por segurança, você pode copiar a pasta `modulos/orcamentos` para um local seguro. Assim, se algo der errado, basta copiar a pasta de volta.
Graças à arquitetura modular, **o módulo não deve ser afetado por atualizações do sistema**, mas o seguro morreu de velho! 😉

## ⚙️ Configuração (Importante)

O arquivo principal de configuração é o `config_geral.php`. Ele tenta detectar automaticamente a maioria dos caminhos, mas **atenção especial** deve ser dada à URL raiz do sistema.

### Renomeando a Pasta do Projeto

Se você alterar o nome da pasta principal do projeto (ex: de `mapos` para `os`), você precisa ajustar a constante `MAPOS_URL` no arquivo `config_geral.php`.

**Arquivo:** `config_geral.php`

```php
// ...

// 🛑 AQUI: Se a pasta do seu projeto mudou, altere '/mapos/' para o novo nome (ex: '/os/')
define('MAPOS_URL', $protocol . $host . '/mapos/'); 

// ...
```

### Por que alterar apenas isso?

As outras constantes (`MAPOS_PATH` e `MAPOS_ROOT_PATH`) utilizam caminhos relativos ao sistema de arquivos (`dir/../../`), então elas se "auto-ajustam" independentemente do nome da pasta raiz, contanto que a estrutura interna de diretórios (`modulos/orcamentos`) seja mantida.

A URL pública (`MAPOS_URL`), no entanto, depende de como o servidor web (Apache/Nginx) enxerga sua pasta, por isso precisa ser definida manualmente se fugir do padrão `/mapos/`.

---

## 📜 Licença e Isenção de Responsabilidade

Este módulo é um software de **Código Aberto (Open Source)**, não comercializado.

- **Uso Livre:** Qualquer pessoa pode baixar, usar e modificar.
- **"Do It Yourself" (Faça Você Mesmo):** O módulo é entregue "como está", sem garantias.
- **Sem Suporte (Nem do Mapos Oficial):** Este é um projeto independente. **A equipe oficial do Mapos não oferece suporte a este módulo**, assim como o criador deste módulo também não oferece.
- **Responsabilidade:** A instalação e uso são de inteira responsabilidade do usuário (conta e risco).

Adaptado para trabalhar com PHP 8.3+. Sinta-se à vontade para colaborar!
