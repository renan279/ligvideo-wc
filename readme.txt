# LigVideo para WooCommerce

**Versão:** 1.3.8
**Autor:** Renan Macarroni

## Descrição

O plugin **LigVideo para WooCommerce** integra sua loja com a plataforma **LigVideo (AlouShop)**, permitindo que produtos sejam adicionados ao carrinho durante uma **videochamada**. Suporta tanto **produtos simples** quanto **variações via SKU**, além de oferecer um **botão flutuante opcional** no frontend da loja.

## Funcionalidades

- Adição de produtos ao carrinho via chamada da API externa.
- Suporte a produtos simples e produtos com variações.
- Geração de URLs seguras com criptografia (Chave Pública).
- Exibição de botão flutuante de ativação configurável.
- Interface de configuração simples no painel do WordPress.
- Integração com a API REST do WordPress para comunicação com a LigVideo.

---

## Instalação

1. Faça o upload da pasta `ligvideo-wc` para o diretório `/wp-content/plugins/`.
2. Ative o plugin no menu **Plugins** do WordPress.
3. Acesse o menu **Configurações > LigVideo** para configurar as chaves e opções.

---

## Configuração

No painel **Configurações > LigVideo**, preencha os seguintes campos:

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
