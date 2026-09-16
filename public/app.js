// A interface ajuda na navegação; as permissões são verificadas no PHP.
document.querySelector('#show-password')?.addEventListener('change', (event) => {
    document.querySelector('#senha').type = event.target.checked ? 'text' : 'password';
});
document.querySelector('#search')?.addEventListener('input', (event) => {
    const query = event.target.value.toLocaleLowerCase('pt-BR').trim();
    const rows = [...document.querySelectorAll('[data-student]')];
    rows.forEach(row => { row.hidden = !row.dataset.student.toLocaleLowerCase('pt-BR').includes(query); });
    document.querySelector('#empty').hidden = rows.some(row => !row.hidden);
});
document.querySelectorAll('[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!confirm(form.dataset.confirm)) event.preventDefault();
    });
});
