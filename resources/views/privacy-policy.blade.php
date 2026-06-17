<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi — ZonaKasir</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        :root{--bg:#F4F4F2;--bg-white:#FFF;--tp:#1A1A1A;--ts:#555;--tt:#888;--bd:#E5E5E1;--bl:#F0F0ED;--ac:#FF6600;--al:#FF660010}
        body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--bg);color:var(--tp);line-height:1.7;-webkit-font-smoothing:antialiased}
        .header{position:sticky;top:0;z-index:100;background:rgba(244,244,242,0.85);backdrop-filter:blur(20px);border-bottom:1px solid var(--bd)}
        .header-inner{max-width:1100px;margin:0 auto;padding:14px 24px;display:flex;align-items:center;justify-content:space-between}
        .logo{font-weight:800;font-size:18px;color:var(--tp);text-decoration:none;letter-spacing:-0.02em}
        .logo span{color:var(--ac)}
        .header-right{display:flex;align-items:center;gap:12px}
        .lang-select{font-size:12px;font-weight:600;color:var(--tp);background:var(--bg-white);border:1px solid var(--bd);border-radius:6px;padding:6px 10px;cursor:pointer;outline:none;font-family:inherit}
        .lang-select:focus{border-color:var(--ac)}
        .back-link{font-size:12px;font-weight:600;color:var(--tt);text-decoration:none;transition:color .2s}
        .back-link:hover{color:var(--tp)}
        .hero{max-width:1100px;margin:0 auto;padding:60px 24px 40px}
        .hero-badge{display:inline-block;font-size:10px;font-weight:700;color:var(--ac);text-transform:uppercase;letter-spacing:.2em;background:var(--al);border:1px solid #FF660020;padding:6px 14px;border-radius:4px;margin-bottom:20px}
        .hero h1{font-size:36px;font-weight:800;line-height:1.15;letter-spacing:-0.03em;margin-bottom:16px}
        .hero-meta{display:flex;align-items:center;gap:16px;font-size:12px;color:var(--tt);font-weight:500}
        .hero-meta .dot{width:3px;height:3px;background:var(--tt);border-radius:50%}
        .content-wrapper{max-width:1100px;margin:0 auto;padding:0 24px 80px;display:grid;grid-template-columns:240px 1fr;gap:60px}
        .sidebar{position:sticky;top:80px;align-self:start}
        .sidebar-title{font-size:10px;font-weight:700;color:var(--tt);text-transform:uppercase;letter-spacing:.15em;margin-bottom:12px}
        .sidebar-nav a{display:block;font-size:12px;font-weight:500;color:var(--ts);text-decoration:none;padding:6px 0;border-left:2px solid transparent;padding-left:12px;transition:all .2s}
        .sidebar-nav a:hover,.sidebar-nav a.active{color:var(--tp);border-left-color:var(--ac)}
        .article{background:var(--bg-white);border:1px solid var(--bd);border-radius:8px;padding:48px 44px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
        .article h2{font-size:20px;font-weight:700;margin-top:40px;margin-bottom:12px;letter-spacing:-.02em;padding-bottom:10px;border-bottom:1px solid var(--bl)}
        .article h2:first-child{margin-top:0}
        .article h3{font-size:15px;font-weight:700;margin-top:28px;margin-bottom:8px}
        .article p{font-size:14px;color:var(--ts);margin-bottom:12px;line-height:1.8}
        .article ul,.article ol{padding-left:20px;margin-bottom:12px}
        .article li{font-size:14px;color:var(--ts);margin-bottom:6px;line-height:1.7}
        .article li strong{color:var(--tp);font-weight:600}
        .hb{background:#FAFAF8;border:1px solid var(--bd);border-radius:6px;padding:20px 24px;margin:20px 0}
        .hb p{font-size:13px;margin-bottom:0}
        .cg{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:20px 0}
        .cc{background:#FAFAF8;border:1px solid var(--bd);border-radius:6px;padding:16px 20px}
        .cc .label{font-size:10px;font-weight:700;color:var(--tt);text-transform:uppercase;letter-spacing:.12em;margin-bottom:4px}
        .cc .value{font-size:13px;font-weight:600;color:var(--tp)}
        .footer{background:#121214;border-top:1px solid rgba(255,255,255,.05);padding:24px;text-align:center}
        .footer-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;font-size:11px;color:#888}
        .footer-links{display:flex;gap:20px}
        .footer-links a{color:#888;text-decoration:none;transition:color .2s}
        .footer-links a:hover{color:#fff}
        @media(max-width:768px){.content-wrapper{grid-template-columns:1fr;gap:24px}.sidebar{display:none}.article{padding:28px 20px}.hero h1{font-size:28px}.cg{grid-template-columns:1fr}.hero-meta{flex-wrap:wrap}.footer-inner{flex-direction:column;gap:12px}.header-right{gap:8px}.lang-select{padding:5px 8px;font-size:11px}}
    </style>
</head>
<body>

<header class="header">
    <div class="header-inner">
        <a href="/landing" class="logo">ZonaKasir<span>.</span></a>
        <div class="header-right">
            <select class="lang-select" id="langSwitcher" aria-label="Language"></select>
            <a href="/landing" class="back-link" id="backLink">← Kembali ke Beranda</a>
        </div>
    </div>
</header>

<main>
    <div class="hero">
        <div class="hero-badge" id="heroBadge">Legal & Compliance</div>
        <h1 id="heroTitle">Kebijakan Privasi</h1>
        <div class="hero-meta">
            <span id="metaEffective">Berlaku sejak: 1 Januari 2026</span>
            <span class="dot"></span>
            <span id="metaUpdated">Terakhir diperbarui: 17 Juni 2026</span>
            <span class="dot"></span>
            <span>PT Zona Teknologi Nusantara</span>
        </div>
    </div>

    <div class="content-wrapper">
        <aside class="sidebar">
            <div class="sidebar-title" id="tocTitle">Daftar Isi</div>
            <nav class="sidebar-nav" id="sidebarNav"></nav>
        </aside>
        <article class="article" id="articleContent"></article>
    </div>
</main>

<footer class="footer">
    <div class="footer-inner">
        <div><strong style="color:#fff">ZonaKasir</strong> &copy; 2026 PT Zona Teknologi Nusantara.</div>
        <div class="footer-links">
            <a href="/privacy" id="fpLink">Kebijakan Privasi</a>
            <a href="/terms" id="ftLink">Ketentuan Layanan</a>
            <a href="/landing" id="fhLink">Kembali ke Beranda</a>
        </div>
    </div>
</footer>

<script src="/landing/legal-i18n.js"></script>
<script src="/landing/privacy-content.js"></script>
<script>
(function(){
    var I = window.__LEGAL_I18N;
    var PC = window.__PRIVACY_CONTENT;
    var navLabels = I.nav;
    var langLabels = window.__LEGAL_LANG_LABELS;
    var langs = Object.keys(langLabels);
    var titles = {ID:'Kebijakan Privasi',EN:'Privacy Policy',AR:'سياسة الخصوصية',ES:'Política de Privacidad',PT:'Política de Privacidade',FR:'Politique de Confidentialité',ZH:'隐私政策',JA:'プライバシーポリシー',KO:'개인정보 처리방침',HI:'गोपनीयता नीति'};

    function getLang(){
        try{ var s=localStorage.getItem('zonakasir_lang'); if(s && langs.indexOf(s)>-1) return s; }catch(e){}
        return 'ID';
    }
    function setLang(c){ localStorage.setItem('zonakasir_lang',c); document.documentElement.lang=c.toLowerCase(); render(c); }

    var sel=document.getElementById('langSwitcher');
    langs.forEach(function(l){ var o=document.createElement('option');o.value=l;o.textContent=langLabels[l];sel.appendChild(o); });
    sel.value=getLang();
    sel.addEventListener('change',function(){ setLang(this.value); });

    var navIDS=['s1','s2','s3','s4','s5','s6','s7','s8','s9','s10','s11','s12','s13','s14'];

    function render(lang){
        var t=I[lang]||I.ID;
        var title=titles[lang]||titles.ID;
        document.getElementById('backLink').textContent=t.back;
        document.getElementById('heroBadge').textContent=t.badge;
        document.getElementById('heroTitle').textContent=title;
        document.getElementById('metaEffective').textContent=t.effective;
        document.getElementById('metaUpdated').textContent=t.updated;
        document.getElementById('tocTitle').textContent=t.toc;
        document.getElementById('fpLink').textContent=t.footerPrivacy;
        document.getElementById('ftLink').textContent=t.footerTerms;
        document.getElementById('fhLink').textContent=t.footerHome;
        document.title=title+' — ZonaKasir';
        var nav=navLabels[lang]||navLabels.ID;
        var navHtml='';nav.forEach(function(label,i){ navHtml+='<a href="#'+navIDS[i]+'">'+label+'</a>'; });
        document.getElementById('sidebarNav').innerHTML=navHtml;
        document.getElementById('articleContent').innerHTML=PC[lang]||PC.ID||'';
    }

    render(getLang());
})();
</script>
</body>
</html>
