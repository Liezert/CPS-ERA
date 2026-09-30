{{-- Pemulihan isian form: salinan isian disimpan di browser (localStorage) saat mengetik, lalu dipulihkan
     kalau halaman dimuat ulang (refresh tak sengaja, koneksi putus). Salinan dihapus saat form berhasil
     disimpan (server mengalihkan halaman). Berlaku untuk:
     - halaman "Create" panel admin (state form Filament = properti `data`);
     - elemen ber-atribut data-form-draft="prop1,prop2" di halaman aplikasi.
     Form CAPA punya mekanisme sendiri (ba/create.blade.php). Berkas unggahan tidak ikut disimpan. --}}
@auth
<script>
(() => {
    if (window.__cpsFormDraft) return;
    window.__cpsFormDraft = true;

    const userId = @js(auth()->id());
    const drafts = new Map(); // id komponen Livewire -> { key, save }
    // Referensi unggahan sementara tidak berlaku lagi setelah refresh: jangan ikut disimpan.
    const clean = (value) => JSON.parse(JSON.stringify(value ?? null, (k, v) => (typeof v === 'string' && v.startsWith('livewire-file:') ? undefined : v)));
    const read = (key) => { try { return JSON.parse(localStorage.getItem(key)); } catch (e) { return null; } };
    // Isi yang benar-benar diketik: nilai daun yang tidak kosong, tanpa kunci (kunci item repeater Filament
    // acak tiap muat ulang, dan null/''/[]/false hanyalah bentuk lain dari "belum diisi").
    const filled = (json) => { const out = []; JSON.parse(json, (k, v) => { if (v !== null && typeof v !== 'object' && v !== '' && v !== false) out.push(v); return v; }); return JSON.stringify(out); };
    const forget = (key) => { try { localStorage.removeItem(key); } catch (e) {} };

    function toast(onDiscard) {
        const box = document.createElement('div');
        box.setAttribute('role', 'status');
        box.style.cssText = 'position:fixed;z-index:9999;left:50%;transform:translateX(-50%);bottom:88px;display:flex;gap:16px;align-items:center;padding:12px 16px;background:#18181b;color:#fff;border-radius:6px;font:500 13px Inter,system-ui,sans-serif;box-shadow:0 8px 24px rgba(0,0,0,.2)';
        box.append('Isian terakhir Anda dipulihkan.');
        const discard = document.createElement('button');
        discard.type = 'button';
        discard.textContent = 'BUANG';
        discard.style.cssText = 'color:#fcd34d;font-weight:700;background:none;border:0;cursor:pointer';
        discard.onclick = () => { onDiscard(); };
        box.append(discard);
        document.body.append(box);
        setTimeout(() => box.remove(), 10000);
    }

    function setup(root, props) {
        const host = root.closest('[wire\\:id]');
        const component = host && window.Livewire?.all().find((c) => c.id === host.getAttribute('wire:id'));
        if (! component || drafts.has(component.id)) return;

        const key = `cps-era:form-draft:${userId}:${location.pathname}`;
        const snapshot = () => JSON.stringify(Object.fromEntries(props.map((prop) => [prop, clean(component.$wire[prop])])));
        const initial = snapshot();
        const save = () => {
            const now = snapshot();
            try { filled(now) === filled(initial) ? localStorage.removeItem(key) : localStorage.setItem(key, now); } catch (e) {}
        };
        drafts.set(component.id, { key, save });

        let timer;
        const queue = () => { clearTimeout(timer); timer = setTimeout(save, 500); };
        host.addEventListener('input', queue);
        host.addEventListener('change', queue);

        const saved = read(key);
        if (saved && filled(JSON.stringify(saved)) !== filled(initial)) {
            props.forEach((prop) => {
                const current = component.$wire[prop];
                const value = current && typeof current === 'object' && ! Array.isArray(current) ? { ...clean(current), ...saved[prop] } : saved[prop];
                component.$wire.$set(prop, value, false);
            });
            component.$wire.$commit();
            toast(() => { forget(key); location.reload(); });
        }
    }

    function init() {
        document.querySelectorAll('[data-form-draft]').forEach((el) => setup(el, el.dataset.formDraft.split(',')));
        if (/^\/admin\/.+\/create$/.test(location.pathname)) {
            const page = document.querySelector('.fi-page');
            if (page) setup(page, ['data']);
        }
    }

    document.addEventListener('livewire:initialized', () => {
        // Setelah tiap respons server: berhasil disimpan (dialihkan) -> hapus salinan; selain itu simpan state terbaru.
        Livewire.hook('commit', ({ component, succeed }) => {
            succeed(({ effects }) => {
                const draft = drafts.get(component.id);
                if (! draft) return;
                effects?.redirect ? (forget(draft.key), drafts.delete(component.id)) : queueMicrotask(draft.save);
            });
        });
        init();
    });
    document.addEventListener('livewire:navigated', () => {
        const alive = new Set(Livewire.all().map((c) => c.id));
        for (const id of drafts.keys()) if (! alive.has(id)) drafts.delete(id);
        init();
    });
})();
</script>
@endauth
