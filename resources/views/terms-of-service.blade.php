<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ketentuan Layanan — ZonaKasir</title>
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
        <h1 id="heroTitle">Ketentuan Layanan</h1>
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
<script src="/landing/terms-content.js"></script>
<script>
(function(){
    var I = window.__LEGAL_I18N;
    var TC = window.__TERMS_CONTENT;
    var navLabels = I.nav;
    var langLabels = window.__LEGAL_LANG_LABELS;
    var langs = Object.keys(langLabels);
    var titles = {ID:'Ketentuan Layanan',EN:'Terms of Service',AR:'شروط الخدمة',ES:'Términos de Servicio',PT:'Termos de Serviço',FR:'Conditions d\'Utilisation',ZH:'服务条款',JA:'利用規約',KO:'이용약관',HI:'सेवा की शर्तें'};
    var tocNav = {
        ID:['1. Penerimaan Ketentuan','2. Deskripsi Layanan','3. Pendaftaran & Akun','4. Paket Berlangganan','5. Pembayaran & Penagihan','6. Penggunaan yang Diizinkan','7. Larangan Penggunaan','8. Kepemilikan Data','9. Keamanan & Uptime','10. Mode Offline','11. Pemrosesan QRIS','12. Hak Kekayaan Intelektual','13. Limitasi Tanggung Jawab','14. Ganti Rugi','15. Pemutusan Layanan','16. Force Majeure','17. Hukum & Sengketa','18. Perubahan Ketentuan','19. Hubungi Kami'],
        EN:['1. Acceptance of Terms','2. Service Description','3. Registration & Account','4. Subscription Plans','5. Payment & Billing','6. Permitted Use','7. Prohibited Use','8. Data Ownership','9. Security & Uptime','10. Offline Mode','11. QRIS Payment','12. Intellectual Property','13. Limitation of Liability','14. Indemnification','15. Service Termination','16. Force Majeure','17. Governing Law','18. Changes to Terms','19. Contact Us'],
        AR:['1. قبول الشروط','2. وصف الخدمة','3. التسجيل والحساب','4. خطط الاشتراك','5. الدفع والفوترة','6. الاستخدام المسموح','7. الاستخدام المحظور','8. ملكية البيانات','9. الأمان والتوافر','10. وضع عدم الاتصال','11. معالجة QRIS','12. الملكية الفكرية','13. تحديد المسؤولية','14. التعويض','15. إنهاء الخدمة','16. القوة القاهرة','17. القانون الحاكم','18. التغييرات','19. اتصل بنا'],
        ES:['1. Aceptación de Términos','2. Descripción del Servicio','3. Registro y Cuenta','4. Planes de Suscripción','5. Pago y Facturación','6. Uso Permitido','7. Uso Prohibido','8. Propiedad de Datos','9. Seguridad y Uptime','10. Modo Sin Conexión','11. Pago QRIS','12. Propiedad Intelectual','13. Limitación de Responsabilidad','14. Indemnización','15. Terminación','16. Fuerza Mayor','17. Ley Aplicable','18. Cambios en Términos','19. Contáctenos'],
        PT:['1. Aceitação dos Termos','2. Descrição do Serviço','3. Registro e Conta','4. Planos de Assinatura','5. Pagamento e Cobrança','6. Uso Permitido','7. Uso Proibido','8. Propriedade dos Dados','9. Segurança e Uptime','10. Modo Offline','11. Pagamento QRIS','12. Propriedade Intelectual','13. Limitação de Responsabilidade','14. Indenização','15. Rescisão do Serviço','16. Força Maior','17. Lei Aplicável','18. Alterações nos Termos','19. Contate-Nos'],
        FR:['1. Acceptation des Conditions','2. Description du Service','3. Inscription et Compte','4. Plans d\'Abonnement','5. Paiement et Facturation','6. Utilisation Autorisée','7. Utilisation Interdite','8. Propriété des Données','9. Sécurité et Uptime','10. Mode Hors Ligne','11. Paiement QRIS','12. Propriété Intellectuelle','13. Limitation de Responsabilité','14. Indemnisation','15. Résiliation du Service','16. Force Majeure','17. Loi Applicable','18. Modifications des Conditions','19. Contactez-Nous'],
        ZH:['1. 接受条款','2. 服务描述','3. 注册与账户','4. 订阅计划','5. 支付与计费','6. 允许使用','7. 禁止使用','8. 数据所有权','9. 安全与正常运行','10. 离线模式','11. QRIS支付','12. 知识产权','13. 责任限制','14. 赔偿','15. 服务终止','16. 不可抗力','17. 适用法律','18. 条款变更','19. 联系我们'],
        JA:['1. 利用規約の承諾','2. サービスの説明','3. 登録とアカウント','4. サブスクリプションプラン','5. 支払いと請求','6. 許可された利用','7. 禁止事項','8. データの所有権','9. セキュリティと稼働','10. オフラインモード','11. QRIS決済','12. 知的財産権','13. 責任の制限','14. 賠償','15. サービスの終了','16. 不可抗力','17. 準拠法','18. 規約の変更','19. お問い合わせ'],
        KO:['1. 약관 동의','2. 서비스 설명','3. 등록 및 계정','4. 구독 요금제','5. 결제 및 청구','6. 허용된 사용','7. 금지된 사용','8. 데이터 소유권','9. 보안 및 가동률','10. 오프라인 모드','11. QRIS 결제','12. 지적재산권','13. 책임 제한','14. 배상','15. 서비스 종료','16. 불가항력','17. 적용 법률','18. 약관 변경','19. 문의하기'],
        HI:['1. शर्तों की स्वीकृति','2. सेवा विवरण','3. पंजीकरण और खाता','4. सदस्यता योजनाएँ','5. भुगतान और बिलिंग','6. अनुमत उपयोग','7. निषिद्ध उपयोग','8. डेटा स्वामित्व','9. सुरक्षा और अपटाइम','10. ऑफ़लाइन मोड','11. QRIS भुगतान','12. बौद्धिक संपदा','13. दायित्व की सीमा','14. क्षतिपूर्ति','15. सेवा समाप्ति','16. बल मेजूद','17. लागू कानून','18. शर्तों में बदलाव','19. संपर्क करें']
    };

    function getLang(){
        try{ var s=localStorage.getItem('zonakasir_lang'); if(s && langs.indexOf(s)>-1) return s; }catch(e){}
        return 'ID';
    }
    function setLang(c){ localStorage.setItem('zonakasir_lang',c); document.documentElement.lang=c.toLowerCase(); render(c); }

    var sel=document.getElementById('langSwitcher');
    langs.forEach(function(l){ var o=document.createElement('option');o.value=l;o.textContent=langLabels[l];sel.appendChild(o); });
    sel.value=getLang();
    sel.addEventListener('change',function(){ setLang(this.value); });

    var navIDS=[]; for(var i=1;i<=19;i++) navIDS.push('s'+i);

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
        var nav=(tocNav[lang]||tocNav.ID);
        var navHtml='';nav.forEach(function(label,i){ navHtml+='<a href="#'+navIDS[i]+'">'+label+'</a>'; });
        document.getElementById('sidebarNav').innerHTML=navHtml;
        document.getElementById('articleContent').innerHTML=TC[lang]||TC.ID||'';
    }

    render(getLang());
})();
</script>
</body>
</html>
