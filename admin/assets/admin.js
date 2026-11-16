const csrf = document.querySelector('meta[name="csrf"]')?.content ?? '';

async function api(acao, dados = {}) {
    const r = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ acao, ...dados }),
    });
    const j = await r.json().catch(() => ({ ok: false, erro: 'Resposta inválida' }));
    if (r.status === 401) { location.href = 'login.php'; throw new Error(j.erro); }
    if (!r.ok || !j.ok) throw new Error(j.erro || 'Falha na operação');
    return j;
}

// Chaveador da loja (US06)
const lojaSw = document.getElementById('loja-switch');
if (lojaSw) {
    lojaSw.addEventListener('click', async () => {
        const abrir = lojaSw.getAttribute('aria-checked') !== 'true';
        lojaSw.disabled = true;
        try {
            await api('toggle_loja', { aberta: abrir });
            lojaSw.setAttribute('aria-checked', String(abrir));
            const card = document.getElementById('loja-card');
            card.classList.toggle('aberta', abrir);
            card.classList.toggle('fechada', !abrir);
            document.getElementById('loja-texto').textContent = abrir ? 'ABERTA' : 'FECHADA';
            document.getElementById('loja-dica').textContent = abrir
                ? 'Recebendo pedidos. Toque para fechar.'
                : 'Pedidos pausados. Toque para abrir.';
        } catch (e) { alert(e.message); }
        lojaSw.disabled = false;
    });
}

// Toggle de disponibilidade dos produtos (US05)
document.querySelectorAll('.switch[data-produto]').forEach(sw => {
    sw.addEventListener('click', async () => {
        const ativar = sw.getAttribute('aria-checked') !== 'true';
        sw.disabled = true;
        try {
            await api('toggle_produto', { id: Number(sw.dataset.produto), disponivel: ativar });
            sw.setAttribute('aria-checked', String(ativar));
            const rot = sw.closest('.item').querySelector('.estado');
            rot.textContent = ativar ? 'Disponível' : 'Esgotado';
            rot.classList.toggle('off', !ativar);
        } catch (e) { alert(e.message); }
        sw.disabled = false;
    });
});

// Exclusão
document.querySelectorAll('[data-excluir]').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('Excluir "' + btn.dataset.nome + '"? Isso não pode ser desfeito.')) return;
        try {
            await api('excluir_produto', { id: Number(btn.dataset.excluir) });
            btn.closest('.item').remove();
        } catch (e) { alert(e.message); }
    });
});