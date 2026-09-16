<?php
declare(strict_types=1);

// Uma única identidade por sessão; o cookie não pode ser lido pelo JavaScript.
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'none'");

// O banco fica fora da pasta pública. PDO prepara os parâmetros contra SQL injection.
$path = getenv('ALUNOS_DB') ?: __DIR__ . '/data/alunos.sqlite';
if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
$db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('PRAGMA busy_timeout = 5000');
$db->exec(file_get_contents(__DIR__ . '/database.sql'));
$db->beginTransaction();
if (!(int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn()) {
    $insert = $db->prepare('INSERT INTO usuarios (nome, login, senha, perfil, nota) VALUES (?, ?, ?, ?, ?)');
    foreach ([['Professor', 'professor', 'Professor@123', 'professor', null], ['Ana Silva', 'ana', 'Ana@12345', 'aluno', 8.5], ['Bruno Santos', 'bruno', 'Bruno@12345', 'aluno', 7.0]] as $u) {
        $u[2] = password_hash($u[2], PASSWORD_DEFAULT);
        $insert->execute($u);
    }
}
$db->commit();

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function redirect(): never { header('Location: index.php'); exit; }
function field(string $key): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : ''; }
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$error = '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf'], field('csrf'))) {
            http_response_code(403);
            throw new RuntimeException('Sessão expirada. Atualize a página e tente novamente.');
        }
        $action = field('action');
        if ($action === 'logout') {
            $_SESSION = [];
            session_destroy();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
            redirect();
        }
        if ($action === 'login') {
            if (isset($_SESSION['user'])) throw new RuntimeException('Saia da conta atual antes de entrar em outra.');
            $q = $db->prepare('SELECT * FROM usuarios WHERE login = ?');
            $q->execute([field('login')]);
            $user = $q->fetch();
            if (!$user || !password_verify(field('senha'), $user['senha'])) throw new RuntimeException('Usuário ou senha incorretos.');
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $user['id'], 'nome' => $user['nome'], 'perfil' => $user['perfil']];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            redirect();
        }
        // A autorização é conferida no servidor, mesmo em requisições forjadas.
        if (($_SESSION['user']['perfil'] ?? '') !== 'professor') {
            http_response_code(403);
            throw new RuntimeException('Somente o professor pode alterar os cadastros.');
        }
        $id = filter_var(field('id'), FILTER_VALIDATE_INT);
        if ($action === 'delete') {
            $q = $db->prepare("DELETE FROM usuarios WHERE id = ? AND perfil = 'aluno'");
            $q->execute([$id]);
            if (!$q->rowCount()) throw new RuntimeException('Aluno não encontrado.');
            $_SESSION['success'] = 'Aluno excluído.';
        } elseif ($action === 'save') {
            $nome = field('nome');
            $nota = filter_var(str_replace(',', '.', field('nota')), FILTER_VALIDATE_FLOAT);
            if (strlen($nome) < 2 || strlen($nome) > 100) throw new RuntimeException('Informe um nome entre 2 e 100 caracteres.');
            if ($nota === false || $nota < 0 || $nota > 10) throw new RuntimeException('A nota deve ser um número entre 0 e 10.');
            if ($id) {
                $q = $db->prepare("UPDATE usuarios SET nome = ?, nota = ? WHERE id = ? AND perfil = 'aluno'");
                $q->execute([$nome, $nota, $id]);
                if (!$q->rowCount()) throw new RuntimeException('Aluno não encontrado.');
            } else {
                $login = field('login');
                $senha = field('senha');
                if (!preg_match('/^[a-zA-Z0-9._-]{3,40}$/', $login)) throw new RuntimeException('O usuário deve ter de 3 a 40 letras, números, pontos, hífens ou sublinhados.');
                if (strlen($senha) < 8 || strlen($senha) > 72) throw new RuntimeException('A senha deve ter entre 8 e 72 caracteres.');
                $q = $db->prepare("INSERT INTO usuarios (nome, nota, login, senha, perfil) VALUES (?, ?, ?, ?, 'aluno')");
                $q->execute([$nome, $nota, $login, password_hash($senha, PASSWORD_DEFAULT)]);
            }
            $_SESSION['success'] = 'Cadastro salvo com sucesso.';
        } else throw new RuntimeException('Ação inválida.');
        redirect();
    } catch (PDOException $ex) {
        $error = $ex->getCode() === '23000' ? 'Este usuário já está cadastrado.' : 'Não foi possível salvar. Tente novamente.';
    } catch (RuntimeException $ex) { $error = $ex->getMessage(); }
}
$user = $_SESSION['user'] ?? null;
$alunos = [];
$edit = null;
if ($user && $user['perfil'] === 'professor') {
    $alunos = $db->query("SELECT id, nome, login, nota FROM usuarios WHERE perfil = 'aluno' ORDER BY nome COLLATE NOCASE")->fetchAll();
    foreach ($alunos as $aluno) if ((string) $aluno['id'] === ($_GET['editar'] ?? '')) $edit = $aluno;
} elseif ($user) {
    // O aluno consulta apenas a própria nota, usando o ID da sessão.
    $q = $db->prepare("SELECT nome, nota FROM usuarios WHERE id = ? AND perfil = 'aluno'");
    $q->execute([$user['id']]);
    $boletim = $q->fetch();
    if (!$boletim) { $_SESSION = []; session_destroy(); redirect(); }
}
