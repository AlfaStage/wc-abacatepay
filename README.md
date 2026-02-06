# Abacate Pay PIX para WooCommerce

Integração oficial desenvolvida por [AlfaStageLabs](https://github.com/AlfaStage/wc-abacatepay) para receber pagamentos via PIX instantâneo utilizando a API do Abacate Pay.

## 🚀 Funcionalidades

*   **Checkout Transparente:** O cliente não sai da sua loja para pagar.
*   **QRCode Dinâmico:** Geração instantânea do QR Code na página de "Pedido Recebido".
*   **Pix Copia e Cola:** Botão fácil para copiar o código.
*   **Cronômetro:** Exibição do tempo restante para pagamento.
*   **Aprovação Automática:** Webhooks configurados para mudar o status do pedido para "Processando" assim que o pagamento é recebido.
*   **Compatibilidade Total:** Funciona com Checkout Clássico e WooCommerce Blocks (Gutenberg).
*   **Privacidade:** Envio simplificado de dados (apenas valor e ID do pedido) para evitar erros de validação de cadastro.

## 📦 Instalação

1.  Baixe o plugin ou clone este repositório na pasta `wp-content/plugins/wc-abacatepay`.
2.  Acesse o painel do WordPress.
3.  Vá em **Plugins > Plugins Instalados**.
4.  Ative o **Abacate Pay PIX - AlfaStageLabs**.

## ⚙️ Configuração (Passo a Passo)

### 1. Obter Credenciais
1.  Acesse sua conta no [Abacate Pay](https://abacatepay.com).
2.  Vá em **API Keys**.
3.  Crie uma nova chave e certifique-se de marcar as permissões de **Billing** ou **Acesso Total**.

### 2. Configurar no WooCommerce
1.  No WordPress, vá em **WooCommerce > Configurações > Pagamentos**.
2.  Clique em **Abacate Pay - PIX**.
3.  Cole sua **API Token**.
4.  Defina o **Tempo de Expiração** (em minutos) para o QR Code.
5.  Defina uma senha para o **Webhook Secret** (ou use a gerada automaticamente).
6.  **Salve as alterações**.

### 3. Configurar Webhook (Crucial)
1.  Após salvar, copie a **URL do Webhook** que aparecerá no campo cinza na página de configuração do plugin.
2.  Volte ao painel do Abacate Pay.
3.  Vá na seção **Webhooks** e clique em criar novo.
4.  Cole a URL copiada.
5.  Selecione os eventos de pagamento (`billing.paid` ou similar).

## 🛠 Troubleshooting (Resolução de Problemas)

**Erro: "Insufficient permissions"**
*   Sua chave de API foi criada sem permissão de criar cobranças. Crie uma nova chave no painel do Abacate Pay e marque todas as permissões.

**O QR Code não aparece**
*   Verifique se o Webhook Secret está igual no plugin e na URL colada no Abacate Pay.
*   Limpe o cache do seu site.

**Logs de Erro**
*   Para debugar, vá em **WooCommerce > Status > Logs** e procure por `abacatepay` no menu suspenso. O plugin registra todas as requisições e respostas.

## 📄 Licença

Desenvolvido por **AlfaStageLabs**.
