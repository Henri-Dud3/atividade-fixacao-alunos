"""Teste HTTP com banco temporário: não altera os dados usados na apresentação."""
import http.cookiejar
import os
from pathlib import Path
import re
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
PHP = ROOT / '.runtime/php/php.exe'
BASE = 'http://127.0.0.1:8011/'

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(c, data=None):
    payload = urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        response = c.open(BASE + 'index.php', payload)
    except urllib.error.HTTPError as exc:
        response = exc
    return response.status, response.read().decode()

def token(html):
    return re.search(r'name="csrf" value="([a-f0-9]+)"', html)[1]

def post(c, **data):
    data.setdefault('csrf', token(request(c)[1]))
    return request(c, data)

with tempfile.TemporaryDirectory() as tmp:
    env = dict(os.environ, ALUNOS_DB=str(Path(tmp) / 'test.sqlite'))
    command = [str(PHP), '-d', f'extension_dir={PHP.parent / "ext"}', '-d', 'extension=pdo_sqlite'] if PHP.exists() else ['php']
    process = subprocess.Popen(command + ['-S', '127.0.0.1:8011', '-t', str(ROOT / 'public')], env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        teacher, student = client(), client()
        for _ in range(50):
            try:
                request(teacher)
                break
            except urllib.error.URLError:
                time.sleep(.1)
        assert 'incorretos' in post(teacher, action='login', login='professor', senha='errada')[1]
        assert 'Alunos e notas' in post(teacher, action='login', login='professor', senha='Professor@123')[1]
        assert 'conta atual' in post(teacher, action='login', login='ana', senha='Ana@12345')[1]
        assert post(teacher, action='save', csrf='invalido')[0] == 403
        assert 'entre 0 e 10' in post(teacher, action='save', nome='Teste', nota='11', login='teste', senha='Teste@123')[1]
        assert 'sucesso' in post(teacher, action='save', nome='<script>Teste</script>', nota='0', login='teste', senha='Teste@123')[1]
        html = request(teacher)[1]
        assert '&lt;script&gt;Teste&lt;/script&gt;' in html and '<script>Teste</script>' not in html
        sid = re.search(r'href="\?editar=(\d+)" aria-label="Editar &lt;script', html)[1]
        assert 'já está cadastrado' in post(teacher, action='save', nome='Teste', nota='8', login='teste', senha='Teste@123')[1]
        assert 'sucesso' in post(teacher, action='save', id=sid, nome='Teste atualizado', nota='9.25')[1]
        html = post(student, action='login', login='teste', senha='Teste@123')[1]
        assert '9,25' in html and 'Ana Silva' not in html and 'Bruno Santos' not in html
        assert post(student, action='save', id=sid, nome='Forjado', nota='10')[0] == 403
        assert post(student, action='delete', id=sid)[0] == 403
        assert 'Acesse sua conta' in post(student, action='logout')[1]
        assert '9,25' in post(student, action='login', login='teste', senha='Teste@123')[1]
        assert 'excluído' in post(teacher, action='delete', id=sid)[1]
        assert 'Acesse sua conta' in request(student)[1]
        assert 'Acesse sua conta' in post(teacher, action='logout')[1]
        print('OK: login, exclusividade de sessao, CSRF, validacao, cadastro, XSS, duplicidade, edicao, persistencia, isolamento, permissoes, exclusao e logout.')
    finally:
        process.terminate()
        process.wait()
