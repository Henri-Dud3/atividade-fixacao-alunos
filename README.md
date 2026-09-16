# Nota Clara

Sistema de cadastro de alunos e notas em HTML, CSS, JavaScript, PHP e SQL (SQLite). Sem frameworks ou dependências de frontend.

Repositório: https://github.com/Henri-Dud3/atividade-fixacao-alunos

Sistema online: https://nota-clara-production.up.railway.app

## Executar

Requisito: PHP 8.1+ com a extensão `pdo_sqlite` habilitada.

Na pasta do projeto, execute `php -S localhost:8000 -t public` e abra http://localhost:8000.
No Windows desta máquina, execute `powershell -ExecutionPolicy Bypass -File iniciar.ps1` para usar o PHP portátil preparado em `.runtime`.
Mantenha o terminal aberto enquanto usa o sistema. Encerre com Ctrl+C.

O banco `data/alunos.sqlite` e as contas de demonstração são criados automaticamente no primeiro acesso. As alterações ficam salvas entre reinícios.

## Contas de demonstração

| Perfil | Nome | Usuário | Senha |
| --- | --- | --- | --- |
| Professor | Professor | professor | Professor@123 |
| Aluno | Ana Silva | ana | Ana@12345 |
| Aluno | Bruno Santos | bruno | Bruno@12345 |

O professor cadastra, busca, edita e exclui alunos. No cadastro, define também usuário e senha do aluno. O aluno vê somente a própria nota. A nota aceita valores de 0 a 10, inclusive zero, com até duas casas no formulário.

Há uma identidade por sessão do navegador, compartilhada entre abas. É preciso sair para trocar de conta. Navegadores ou janelas anônimas diferentes possuem sessões independentes.

## Organização

- `public/index.php`: formulários e telas.
- `public/style.css`: interface responsiva.
- `public/app.js`: busca, exibição de senha e confirmação de exclusão.
- `app.php`: sessões, permissões, validação e consultas parametrizadas.
- `database.sql`: estrutura SQL do banco.
- `data/`: banco persistente, fora da pasta pública.
- `output/Entrega_Railway.docx`: documento atualizado com acesso online e credenciais.

Senhas são armazenadas como hashes. Todas as alterações exigem token CSRF; HTML é escapado na saída e permissões são verificadas no PHP.

## Publicar e entregar

Use uma hospedagem com PHP e PDO SQLite, disco persistente e permissão de escrita em `data`. Configure a raiz pública para `public/`; nunca exponha a raiz do projeto, pois ela contém o banco e o documento de senhas. Não funciona em hospedagem exclusivamente estática, como GitHub Pages.

O endereço localhost só funciona na máquina que está executando o sistema. A versão pública está no Railway, no endereço indicado acima e no documento de entrega. O código-fonte está no GitHub.

As credenciais acima são de demonstração para avaliação escolar. Cadastros adicionais têm as credenciais escolhidas pelo professor.

## Verificação

Execute `python tests/test_system.py` com o PHP disponível. O teste usa um banco temporário e verifica login, sessão única, CSRF, validação de notas, cadastro, duplicidade, escape de HTML, edição, persistência, acesso individual, bloqueio de alterações por alunos, exclusão e logout.

O `Dockerfile` e `railway.json` configuram a publicação no Railway. A porta de destino é `80`, e o volume persistente está montado em `/var/www/html/data`. O script `docker-entrypoint.sh` prepara as permissões na inicialização. O deploy foi feito pelo CLI; alterações no GitHub não são publicadas automaticamente. Para atualizar o serviço vinculado, execute `railway up`.
