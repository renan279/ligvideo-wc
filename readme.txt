=== LigVideo Integration ===
Contributors: renanmacarroni
Tags: woocommerce, video, ligvideo, aloushop, carrinho
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.4.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integra LigVideo ao WooCommerce para adicionar produtos ao carrinho via videochamada, com suporte a produtos simples e variações.

== Description ==

Integra LigVideo (AlouShop) ao WooCommerce para adicionar produtos ao carrinho via videochamada, suportando simples e variações por ID, e botão flutuante opcional.

== Installation ==

1. Faça upload do plugin para a pasta `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Vá para Configurações > LigVideo Integration para configurar suas chaves de API

== Frequently Asked Questions ==

= Como configurar o plugin? =

Acesse Configurações > LigVideo Integration no painel administrativo do WordPress e insira suas chaves de API fornecidas pelo LigVideo.

= O plugin suporta produtos variáveis? =

Sim, o plugin suporta tanto produtos simples quanto produtos variáveis do WooCommerce.

== Changelog ==

= 1.4.8 =
* Correções de segurança e melhorias de performance

== Upgrade Notice ==

= 1.4.8 =
Atualização recomendada para todos os usuários.

## Descrição

O plugin **LigVideo Integration** integra sua loja com a plataforma **LigVideo (AlouShop)**, permitindo que produtos sejam adicionados ao carrinho durante uma **videochamada**. Suporta tanto **produtos simples** quanto **variações via SKU**, além de oferecer um **botão flutuante opcional** no frontend da loja.

## Funcionalidades

- Adição de produtos ao carrinho via chamada da API externa.
- Suporte a produtos simples e produtos com variações.
- Geração de URLs seguras com criptografia (Chave Pública).
- Exibição de botão flutuante de ativação configurável.
- Interface de configuração simples no painel do WordPress.
- Integração com a API REST do WordPress para comunicação com a LigVideo.

---

## Instalação

1. Faça o upload da pasta `ligvideo-integration` para o diretório `/wp-content/plugins/`.
2. Ative o plugin no menu **Plugins** do WordPress.
3. Acesse o menu **Configurações > LigVideo Integration** para configurar as chaves e opções.

---

## Configuração

No painel **Configurações > LigVideo Integration**, preencha os seguintes campos:

- **ID da Loja**: Identificador fornecido pela LigVideo.
- **Chave Pública (Base64)**: Utilizada para criptografar os dados enviados.
- **Chave Privada (Base64)**: Não utilizada diretamente no plugin, mas pode ser armazenada para referência.
- **Mostrar botão flutuante**: Habilita um botão fixo no canto da tela.

---

## Endpoints REST

### Produtos
`GET /wp-json/ligvideo/v1/produtos`

Parâmetros disponíveis:
- `id` (opcional): IDs separados por vírgula.
- `codigo_barras` (opcional): SKU do produto.
- `nome` (opcional): Texto de busca.

Retorna uma lista de produtos criptografada com a chave pública.

### Retorno de Carrinho
`GET /wp-json/ligvideo/v1/retorno?prod=[JSON]`

Adiciona os produtos informados diretamente ao carrinho.
O parâmetro `prod` deve conter um JSON com a seguinte estrutura:

```json
[
  {
    "id": 123,
    "qnt": 2
  },
  {
    "id": 456,
    "qnt": 1
  }
]
