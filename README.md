# Abacate Pay PIX para WooCommerce - AlfaStageLabs

![WooCommerce](https://img.shields.io/badge/WooCommerce-96588a?style=for-the-badge&logo=woocommerce&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Version](https://img.shields.io/badge/Version-5.1-green?style=for-the-badge)

A solução definitiva e transparente para receber pagamentos via PIX no seu WooCommerce através do **Abacate Pay**. Mantenha seu cliente na loja, ofereça uma experiência fluida e automatize sua gestão de pedidos.

## 🚀 Novidades da Versão 5.1

*   **⚡ Confirmação em Tempo Real:** A página de "Pedido Recebido" monitora o pagamento automaticamente. Assim que o cliente paga, a tela atualiza para "Pagamento Recebido" sem necessidade de recarregar manualmente.
*   **📧 PIX no E-mail:** O QR Code e o código "Copia e Cola" são incluídos automaticamente no e-mail de "Aguardando Pagamento" enviado ao cliente.
*   **⏰ Cancelamento Automático:** Rotina interna (Cron) que verifica pedidos expirados e altera o status para "Cancelado" automaticamente, liberando seu estoque.

## ✨ Funcionalidades Principais

*   **Checkout Transparente:** Geração de PIX sem redirecionar o usuário para sites externos.
*   **Cronômetro Regressivo:** Exibe visualmente quanto tempo o cliente ainda tem para realizar o pagamento.
*   **Compatibilidade com Blocos:** Suporte total ao novo Checkout de Blocos (Gutenberg) e ao Checkout Clássico.
*   **ID de Transação Visível:** O ID do Abacate Pay é salvo no pedido e exibido no painel administrativo para fácil conciliação.
*   **Logs Detalhados:** Sistema de debug completo para monitorar requisições e respostas da API em *WooCommerce > Status > Logs*.

## 📦 Instalação

1.  Faça o download do plugin ou clone este repositório:
    ```bash
    git clone https://github.com/AlfaStage/wc-abacatepay.git
    ```
2.  Mova a pasta para `wp-content/plugins/`.
3.  No seu painel WordPress, vá em **Plugins** e ative o **Abacate Pay PIX - AlfaStageLabs**.
4.  **Importante:** Se você estiver atualizando de uma versão anterior, desative e ative o plugin para garantir que o agendador de cancelamento automático seja registrado corretamente.

## ⚙️ Configuração

### 1. No Abacate Pay
*   Acesse o dashboard do [Abacate Pay](https://abacatepay.com).
*   Gere uma **API Key** com permissões de **Billing** (Cobrança) ou acesso total.
*   Na aba **Webhooks**, você precisará da URL gerada pelo plugin (veja abaixo).

### 2. No WooCommerce
*   Vá em **WooCommerce > Configurações > Pagamentos > Abacate Pay - PIX**.
*   **API Token:** Insira a chave gerada no passo anterior.
*   **Tempo de Expiração:** Defina em minutos (ex: 15).
*   **Senha do Webhook:** Crie uma senha segura. Ela será usada para validar as notificações que seu site recebe.
*   **URL do Webhook:** Copie a URL limpa que aparece no campo e cole-a no painel do Abacate Pay.

## 🛠 Resolução de Problemas (FAQ)

**O status do pedido não muda para "Pago" sozinho?**
Verifique se você colou a URL do Webhook corretamente no painel do Abacate Pay e se o evento `billing.paid` está selecionado lá.

**Erro "Insufficient permissions"?**
Sua chave de API não tem permissão para criar cobranças. Gere uma nova chave no Abacate Pay e certifique-se de marcar as permissões de escrita/cobrança.

**Onde vejo os erros de pagamento?**
Acesse **WooCommerce > Status > Logs** e selecione o log `abacatepay` no menu suspenso. Lá você verá exatamente o que foi enviado e o que a API respondeu.

## 📄 Licença

Desenvolvido por [AlfaStageLabs](https://github.com/AlfaStage).
Uso livre para lojas WooCommerce.

---
*Este plugin não possui vínculo oficial com a marca Abacate Pay, sendo uma integração de comunidade baseada em sua API pública.*
