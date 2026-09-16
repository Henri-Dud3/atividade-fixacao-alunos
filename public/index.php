<?php require __DIR__ . '/../app.php'; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota Clara | Cadastro de alunos</title>
    <link rel="stylesheet" href="style.css">
    <script src="app.js" defer></script>
</head>
<body>
<header class="topbar">
    <a class="brand" href="index.php"><span class="logo">N</span> Nota Clara</a>
    <?php if ($user): ?>
        <div class="account"><span><?= e($user['nome']) ?> <small><?= e(ucfirst($user['perfil'])) ?></small></span>
        <form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><button class="secondary" name="action" value="logout">Sair</button></form></div>
    <?php else: ?><span class="muted">Portal acadêmico</span><?php endif; ?>
</header>
<main>
<?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success" role="status"><?= e($success) ?></div><?php endif; ?>
<?php if (!$user): ?>
    <div class="login-layout">
        <section class="intro"><span class="eyebrow">APRENDER. ACOMPANHAR. EVOLUIR.</span><h1>Seu desempenho,<br>com clareza.</h1><p>Um espaço simples para professores registrarem notas e alunos acompanharem seus resultados.</p><div class="intro-note"><span class="dot"></span> Cada perfil tem seu próprio acesso.</div></section>
        <section class="card login-card"><span class="eyebrow">BEM-VINDO</span><h2>Acesse sua conta</h2><p class="muted">Entre com o usuário e a senha fornecidos.</p>
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <label>Usuário<input name="login" autocomplete="username" required maxlength="40" placeholder="Seu usuário"></label>
            <label>Senha<input id="senha" name="senha" type="password" autocomplete="current-password" required maxlength="72" placeholder="Sua senha"></label>
            <label class="check"><input type="checkbox" id="show-password"> Mostrar senha</label>
            <button name="action" value="login">Entrar na conta <span aria-hidden="true">→</span></button>
        </form><p class="footnote">Para trocar de perfil, saia da conta atual.</p></section>
    </div>
<?php elseif ($user['perfil'] === 'professor'): ?>
    <div class="page-heading"><div><span class="eyebrow">ÁREA DO PROFESSOR</span><h1>Alunos e notas</h1><p class="muted">Organize a turma e mantenha os resultados atualizados.</p></div><span class="badge"><?= count($alunos) ?> aluno(s)</span></div>
    <div class="dashboard">
    <section class="card"><h2><?= $edit ? 'Editar aluno' : 'Cadastrar aluno' ?></h2><p class="muted">Notas de 0 a 10.</p>
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
            <label>Nome completo<input name="nome" required minlength="2" maxlength="100" value="<?= e($edit['nome'] ?? field('nome')) ?>" placeholder="Ex.: Maria Oliveira"></label>
            <label>Nota final<input name="nota" type="number" min="0" max="10" step="0.01" required value="<?= e($edit['nota'] ?? field('nota')) ?>" placeholder="Ex.: 8,5"></label>
            <?php if (!$edit): ?>
            <label>Usuário do aluno<input name="login" required pattern="[a-zA-Z0-9._\-]{3,40}" maxlength="40" autocomplete="off" value="<?= e(field('login')) ?>" placeholder="Ex.: maria.oliveira"></label>
            <label>Senha inicial<input name="senha" type="password" minlength="8" maxlength="72" required autocomplete="new-password" placeholder="Pelo menos 8 caracteres"></label>
            <p class="footnote">Anote o usuário e a senha para entregar ao aluno.</p>
            <?php endif; ?>
            <button name="action" value="save"><?= $edit ? 'Salvar alterações' : 'Cadastrar aluno' ?></button>
            <?php if ($edit): ?><a class="cancel" href="index.php">Cancelar edição</a><?php endif; ?>
        </form>
    </section>
    <section class="card roster"><div class="list-heading"><h2>Minha turma</h2><label class="search"><span class="sr-only">Buscar aluno</span><input id="search" type="search" placeholder="Buscar aluno..."></label></div>
        <div class="table-wrap"><table><thead><tr><th>Aluno</th><th>Nota final</th><th>Ações</th></tr></thead><tbody>
        <?php foreach ($alunos as $aluno): ?>
            <tr data-student="<?= e($aluno['nome'] . ' ' . $aluno['login']) ?>"><td><strong><?= e($aluno['nome']) ?></strong><small>@<?= e($aluno['login']) ?></small></td><td><span class="grade"><?= number_format((float) $aluno['nota'], 2, ',', '.') ?></span></td><td><div class="actions"><a href="?editar=<?= $aluno['id'] ?>" aria-label="Editar <?= e($aluno['nome']) ?>">Editar</a><form method="post" data-confirm="Excluir este aluno e seu acesso?">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= $aluno['id'] ?>"><button class="delete" name="action" value="delete" aria-label="Excluir <?= e($aluno['nome']) ?>">Excluir</button>
            </form></div></td></tr>
        <?php endforeach; ?>
        </tbody></table></div><p id="empty" class="muted empty" <?= $alunos ? 'hidden' : '' ?>>Nenhum aluno encontrado.</p>
    </section></div>
<?php else: ?>
    <div class="page-heading"><div><span class="eyebrow">ÁREA DO ALUNO</span><h1>Olá, <?= e($boletim['nome']) ?>.</h1><p class="muted">Acompanhe aqui sua nota final.</p></div></div>
    <section class="card result"><span class="eyebrow">SEU RESULTADO</span><h2>Nota final</h2><div class="big-grade"><?= number_format((float) $boletim['nota'], 2, ',', '.') ?><span>/ 10</span></div><p class="muted">Nota registrada pelo professor.</p><div class="result-note">Dúvidas sobre o resultado? Converse com seu professor.</div></section>
<?php endif; ?>
</main>
<footer>Nota Clara <span>•</span> Sistema de cadastro de alunos e notas</footer>
</body>
</html>
