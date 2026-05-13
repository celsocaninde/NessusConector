# 🛡️ Nessus Conector para GLPI

[English](README.md) / [Português do Brasil](readme_pt-br.md)

![Nessus Conector](images/nessus-logo.png)

O **Nessus Conector** é um plugin para GLPI que importa vulnerabilidades de produtos Tenable, vincula achados aos ativos do GLPI, mantém histórico de sincronização e ajuda o time de segurança a criar chamados a partir das vulnerabilidades encontradas.

Este fork adiciona suporte para duas origens:

| Origem | O que importa | Família de API |
| --- | --- | --- |
| 🖥️ Nessus / Tenable VM | scans, hosts e plugins de vulnerabilidade | `/scans` |
| 🌐 Tenable WAS | execuções de scan web e findings | `/was/v2` |

## ✨ Destaques

- 🔐 Configuração da URL da API Tenable, access key e secret key dentro do GLPI.
- 🔎 Busca de scans Nessus / Tenable VM diretamente pelo GLPI.
- 🌐 Busca de configurações e execuções Tenable WAS diretamente pelo GLPI.
- ⚙️ Fila de sincronização pela interface do GLPI.
- 🧩 Associação de alvos importados com ativos do GLPI.
- 📊 Visualização de vulnerabilidades atuais por scan ou consolidada.
- 🕰️ Histórico de sincronização com primeira e última aparição.
- 🎫 Criação de chamados individuais ou agrupados a partir dos achados.
- 👤 Controle de permissões por perfil do GLPI.
- 🧪 Compatível com o comportamento do PHP 8.5 sobre `curl_close()`.

## 🖼️ Imagens

### Telas do Plugin

![Tela do Nessus Conector 1](images/print1.png)

![Tela do Nessus Conector 2](images/print2.png)

### Configuração da API

![Configuração do Nessus Conector](images/print3.png)

## ✅ Requisitos

- GLPI 11.0.x
- Extensão PHP cURL habilitada
- Credenciais de API Tenable
- Perfil de usuário com permissões do plugin habilitadas

Testado com:

```text
PHP 8.5
```

Compatibilidade declarada:

```text
GLPI >= 11.0.0 and < 11.1.0
```

## 🚀 Instalação

Coloque o diretório do plugin em:

```text
plugins/nessusglpi
```

Depois instale e ative pela interface do GLPI, ou use a CLI:

```bash
php bin/console glpi:plugin:install --force --username=glpi --no-interaction nessusglpi
php bin/console glpi:plugin:activate --no-interaction nessusglpi
php bin/console cache:clear
```

Após instalar, abra o perfil do usuário no GLPI e habilite os direitos do **Nessus Conector**.

## 🔑 Configuração

Acesse:

```text
Plugins -> Nessus Conector -> Configuration
```

Preencha:

| Campo | Exemplo |
| --- | --- |
| API URL para Tenable Cloud | `https://cloud.tenable.com` |
| API URL para Nessus local | `https://nessus.exemplo.local:8834` |
| Access key | Sua access key da Tenable |
| Secret key | Sua secret key da Tenable |
| Timeout | `30` |
| Tipos de ativos para associação | Computer, Network equipment, Printer, Phone, Unmanaged |

Use o botão de teste correto:

- **Test Nessus/VM connection** para Nessus / Tenable VM.
- **Test WAS connection** para Tenable Web App Scanning.

## 🖥️ Fluxo Nessus / Tenable VM

1. Acesse:

   ```text
   Plugins -> Nessus Conector -> Scans
   ```

2. Clique em **Browse Nessus / Tenable VM scans**.

3. Escolha um scan e clique em **Use this scan**.

4. O formulário será preenchido com:

   ```text
   Source: Nessus / Tenable VM
   Scan ID: ID numérico do scan
   ```

5. Salve o scan.

6. Mantenha a lista de scans aberta enquanto a fila de sincronização é processada.

Para scans VM, prefira o `id` numérico retornado por `/scans`.

## 🌐 Fluxo Tenable WAS

1. Acesse:

   ```text
   Plugins -> Nessus Conector -> Scans
   ```

2. Clique em **Browse Tenable WAS scans**.

3. Selecione uma configuração WAS.

4. Selecione uma execução de scan.

5. Clique em **Use this scan**.

6. O formulário será preenchido com:

   ```text
   Source: Tenable WAS
   Scan ID: UUID da execução WAS
   ```

7. Salve o scan.

8. Mantenha a lista de scans aberta enquanto a fila de sincronização é processada.

No WAS, use o UUID da execução do scan, não o ID da configuração.

## 🔄 Sincronização

O plugin grava jobs de sincronização em uma fila no banco.

Comportamento atual:

1. Criar ou sincronizar um scan coloca um job na fila.
2. A página de lista de scans detecta jobs pendentes.
3. A página chama um endpoint AJAX para processar o próximo job.
4. Os dados importados são salvos nas tabelas do plugin.

Importante: isso ainda não é um worker permanente em background. Mantenha a tela de scans aberta enquanto houver job pendente, ou implemente depois um worker CLI/cron usando o mesmo serviço.

## 🧩 Associação com Ativos

O plugin tenta associar os alvos importados aos ativos do GLPI usando os tipos de item configurados.

Estratégia atual:

- hostname
- FQDN
- campo `name` do ativo GLPI

No Tenable WAS, a URL da aplicação web é convertida em hostname/domínio antes da associação.

## 🎫 Chamados

Nas telas de vulnerabilidades, é possível criar:

- chamados individuais para uma vulnerabilidade;
- chamados agrupados para a mesma vulnerabilidade em vários ativos;
- chamados de host pendente quando o alvo foi importado, mas não foi associado a um ativo do GLPI.

O conteúdo do chamado inclui detalhes da vulnerabilidade, severidade, alvo, referência do scan e remediação quando disponível.

## 👤 Permissões por Perfil

O plugin cria os seguintes direitos no GLPI:

| Direito | Finalidade |
| --- | --- |
| `plugin_nessusglpi_scan` | Ver/criar/editar scans e processar sincronização |
| `plugin_nessusglpi_config` | Ver/editar configuração da API |
| `plugin_nessusglpi_vulnerability` | Ver vulnerabilidades e hosts importados |
| `plugin_nessusglpi_ticket` | Direitos declarados para vínculos de chamados |

Se as permissões forem alteradas enquanto o usuário estiver logado, faça logout/login para recarregar os direitos da sessão.

## ⚠️ Aviso sobre Dados

Não desinstale o plugin se quiser manter os dados importados.

O script de desinstalação pode remover as tabelas do plugin, incluindo:

- scans cadastrados;
- hosts importados;
- vulnerabilidades importadas;
- histórico de sincronização;
- vínculos do plugin com chamados;
- configuração da API.

Use a desativação do plugin se quiser apenas desligá-lo temporariamente.

## 🧰 Comandos Úteis

```bash
php bin/console glpi:plugin:list
php bin/console glpi:plugin:install --force --username=glpi --no-interaction nessusglpi
php bin/console glpi:plugin:activate --no-interaction nessusglpi
php bin/console cache:clear
```

Validar um arquivo PHP:

```bash
php -l plugins/nessusglpi/src/TenableWasClient.php
```

## 📚 Documentação Extra

Um guia técnico detalhado passo a passo está disponível em:

```text
COMO_FUNCIONA.md
```

## 🤝 Contribuindo

Contribuições são bem-vindas via forks e pull requests.

Fluxo sugerido:

1. Faça um fork do repositório.
2. Crie uma branch de feature.
3. Faça commits focados.
4. Abra um pull request com notas claras de teste.

## 📄 Licença

Veja o arquivo de licença do repositório para mais detalhes.
