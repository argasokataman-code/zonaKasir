/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 *
 * Internationalization (i18n) system for ZonaKasir landing pages.
 * React Context-based with translation dictionaries for 10 languages.
 */

import { createContext, useContext, useState, useCallback, type ReactNode } from 'react';

export type LangCode = 'ID' | 'EN' | 'AR' | 'ES' | 'PT' | 'FR' | 'ZH' | 'JA' | 'KO' | 'HI';

export interface LangOption {
  code: LangCode;
  label: string;
  flag: string;
}

export const LANGUAGES: LangOption[] = [
  { code: 'ID', label: 'Bahasa Indonesia', flag: '🇮🇩' },
  { code: 'EN', label: 'English', flag: '🇺🇸' },
  { code: 'AR', label: 'العربية', flag: '🇸🇦' },
  { code: 'ES', label: 'Español', flag: '🇪🇸' },
  { code: 'PT', label: 'Português', flag: '🇧🇷' },
  { code: 'FR', label: 'Français', flag: '🇫🇷' },
  { code: 'ZH', label: '中文', flag: '🇨🇳' },
  { code: 'JA', label: '日本語', flag: '🇯🇵' },
  { code: 'KO', label: '한국어', flag: '🇰🇷' },
  { code: 'HI', label: 'हिन्दी', flag: '🇮🇳' },
];

type TranslationDict = Record<string, Record<LangCode, string>>;

const translations: TranslationDict = {
  // ==================== NAVIGATION ====================
  'nav.home': {
    ID: 'Beranda', EN: 'Home', AR: 'الرئيسية', ES: 'Inicio', PT: 'Início',
    FR: 'Accueil', ZH: '首页', JA: 'ホーム', KO: '홈', HI: 'होम',
  },
  'nav.transaction': {
    ID: 'Transaksi', EN: 'Transactions', AR: 'المعاملات', ES: 'Transacciones', PT: 'Transações',
    FR: 'Transactions', ZH: '交易', JA: '取引', KO: '거래', HI: 'लेनदेन',
  },
  'nav.stock': {
    ID: 'Manajemen Stok', EN: 'Inventory', AR: 'المخزون', ES: 'Inventario', PT: 'Inventário',
    FR: 'Inventaire', ZH: '库存管理', JA: '在庫管理', KO: '재고 관리', HI: 'इन्वेंट्री',
  },
  'nav.sync': {
    ID: 'Sinkronisasi', EN: 'Sync', AR: 'المزامنة', ES: 'Sincronización', PT: 'Sincronização',
    FR: 'Synchronisation', ZH: '同步', JA: '同期', KO: '동기화', HI: 'सिंक',
  },
  'nav.analytics': {
    ID: 'Analitik Usaha', EN: 'Analytics', AR: 'التحليلات', ES: 'Analíticas', PT: 'Análises',
    FR: 'Analytiques', ZH: '分析', JA: '分析', KO: '분석', HI: 'विश्लेषण',
  },
  'nav.testimonial': {
    ID: 'Testimoni', EN: 'Testimonials', AR: 'الشهادات', ES: 'Testimonios', PT: 'Depoimentos',
    FR: 'Témoignages', ZH: '客户评价', JA: 'お客様の声', KO: '고객 후기', HI: 'प्रशंसापत्र',
  },
  'nav.faq': {
    ID: 'FAQ', EN: 'FAQ', AR: 'الأسئلة الشائعة', ES: 'Preguntas frecuentes', PT: 'Perguntas frequentes',
    FR: 'Questions fréquentes', ZH: '常见问题', JA: 'よくある質問', KO: '자주 묻는 질문', HI: 'अक्सर पूछे जाने वाले प्रश्न',
  },
  'nav.gallery': {
    ID: 'Galeri', EN: 'Gallery', AR: 'المعرض', ES: 'Galería', PT: 'Galeria',
    FR: 'Galerie', ZH: '图库', JA: 'ギャラリー', KO: '갤러리', HI: 'गैलरी',
  },
  'nav.pricing': {
    ID: 'Paket', EN: 'Pricing', AR: 'الأسعار', ES: 'Precios', PT: 'Preços',
    FR: 'Tarifs', ZH: '价格方案', JA: '料金プラン', KO: '요금제', HI: 'मूल्य निर्धारण',
  },
  'nav.login': {
    ID: 'Masuk', EN: 'Login', AR: 'تسجيل الدخول', ES: 'Iniciar sesión', PT: 'Entrar',
    FR: 'Connexion', ZH: '登录', JA: 'ログイン', KO: '로그인', HI: 'लॉगिन',
  },
  'nav.try_free': {
    ID: 'Coba Gratis', EN: 'Try Free', AR: 'جرّب مجاناً', ES: 'Prueba gratis', PT: 'Experimente grátis',
    FR: 'Essai gratuit', ZH: '免费试用', JA: '無料で試す', KO: '무료 체험', HI: 'मुफ़्त में आज़माएँ',
  },
  'nav.menu': {
    ID: 'Menu Navigasi', EN: 'Navigation Menu', AR: 'القائمة', ES: 'Menú de navegación', PT: 'Menu de navegação',
    FR: 'Menu de navigation', ZH: '导航菜单', JA: 'ナビゲーションメニュー', KO: '내비게이션 메뉴', HI: 'नेविगेशन मेनू',
  },
  'nav.portal': {
    ID: 'Masuk Portal Kasir', EN: 'Cashier Portal', AR: 'بوابة الكاشير', ES: 'Portal de cajero', PT: 'Portal do caixa',
    FR: 'Portail caisse', ZH: '收银门户', JA: 'レジポータル', KO: '캐시어 포털', HI: 'कैशियर पोर्टल',
  },
  'nav.try_now': {
    ID: 'Coba Gratis Sekarang', EN: 'Try Now', AR: 'جرّب الآن', ES: 'Prueba ahora', PT: 'Experimente agora',
    FR: 'Essayer maintenant', ZH: '立即试用', JA: '今すぐ試す', KO: '지금 체험', HI: 'अभी आज़माएँ',
  },

  // ==================== HERO SECTION ====================
  'hero.badge': {
    ID: 'Sistem POS #1 untuk UMKM Indonesia',
    EN: '#1 POS System for SMEs',
    AR: 'نظام نقاط البيع رقم 1 للشركات الصغيرة',
    ES: 'Sistema POS #1 para PyMEs',
    PT: 'Sistema PDV #1 para PMEs',
    FR: 'Système POS #1 pour les PME',
    ZH: '中小企业首选 POS 系统',
    JA: '中小企業向けPOSシステムNo.1',
    KO: '중소기업을 위한 POS 시스템 1위',
    HI: 'SME के लिए #1 POS सिस्टम',
  },
  'hero.title1': {
    ID: 'Kelola Bisnis',
    EN: 'Manage Your Business',
    AR: 'أدر أعمالك',
    ES: 'Gestiona tu Negocio',
    PT: 'Gerencie seu Negócio',
    FR: 'Gérez Votre Entreprise',
    ZH: '管理您的业务',
    JA: 'ビジネスを管理',
    KO: '비즈니스를 관리하세요',
    HI: 'अपना व्यवसाय प्रबंधित करें',
  },
  'hero.title2': {
    ID: 'Kapan Saja,',
    EN: 'Anytime,',
    AR: 'في أي وقت،',
    ES: 'En cualquier momento,',
    PT: 'A qualquer momento,',
    FR: 'À tout moment,',
    ZH: '随时随地，',
    JA: 'いつでも、',
    KO: '언제든지,',
    HI: 'कभी भी,',
  },
  'hero.title3': {
    ID: 'Di Mana Saja.',
    EN: 'Anywhere.',
    AR: 'وأي مكان.',
    ES: 'En cualquier lugar.',
    PT: 'Em qualquer lugar.',
    FR: 'Partout.',
    ZH: '随心所欲。',
    JA: 'どこでも。',
    KO: '어디서든.',
    HI: 'कहीं भी.',
  },
  'hero.desc': {
    ID: 'Aplikasi kasir online & offline yang dirancang khusus untuk UMKM Indonesia. Transaksi cepat, laporan real-time, tanpa ribet.',
    EN: 'Online & offline POS app designed for SMEs. Fast transactions, real-time reports, hassle-free.',
    AR: 'تطبيق نقاطبيع أونلاين وأوفلاين مصمم للشركات الصغيرة. معاملات سريعة وتقارير فورية بدون تعقيد.',
    ES: 'App de puntos de venta online y offline diseñada para PyMEs. Transacciones rápidas, reportes en tiempo real.',
    PT: 'Aplicativo PDV online e offline para PMEs. Transações rápidas, relatórios em tempo real.',
    FR: 'Application de caisse en ligne et hors ligne pour les PME. Transactions rapides, rapports en temps réel.',
    ZH: '专为中小企业设计的在线离线收银系统。快速交易，实时报告，轻松无忧。',
    JA: '中小企業向けオンライン・オフラインPOSアプリ。高速取引、リアルタイムレポート、手軽に。',
    KO: '중소기업을 위한 온/오프라인 POS 앱. 빠른 거래, 실시간 리포트, 간편하게.',
    HI: 'SME के लिए डिज़ाइन किया गया ऑनलाइन और ऑफलाइन POS ऐप। तेज़ लेनदेन, रीयल-टाइम रिपोर्ट।',
  },
  'hero.start': {
    ID: 'Mulai Sekarang',
    EN: 'Get Started',
    AR: 'ابدأ الآن',
    ES: 'Comenzar',
    PT: 'Começar',
    FR: 'Commencer',
    ZH: '立即开始',
    JA: '今すぐ始める',
    KO: '시작하기',
    HI: 'शुरू करें',
  },
  'hero.demo': {
    ID: 'Lihat Demo',
    EN: 'View Demo',
    AR: 'عرض Demo',
    ES: 'Ver Demo',
    PT: 'Ver Demo',
    FR: 'Voir la Démo',
    ZH: '查看演示',
    JA: 'デモを見る',
    KO: '데모 보기',
    HI: 'डेमो देखें',
  },
  'hero.cloud': {
    ID: 'Cloud-Based',
    EN: 'Cloud-Based',
    AR: 'مبني على السحابة',
    ES: 'En la nube',
    PT: 'Baseado em nuvem',
    FR: 'Basé sur le Cloud',
    ZH: '云端部署',
    JA: 'クラウドベース',
    KO: '클라우드 기반',
    HI: 'क्लाउड-आधारित',
  },
  'hero.cloud_sub': {
    ID: 'Data tersimpan aman di cloud, bisa diakses dari mana saja.',
    EN: 'Secure cloud storage, accessible from anywhere.',
    AR: 'تخزين آمن في السحابة، يمكن الوصول من أي مكان.',
    ES: 'Almacenamiento seguro en la nube, accesible desde cualquier lugar.',
    PT: 'Armazenamento seguro na nuvem, acessível de qualquer lugar.',
    FR: 'Stockage cloud sécurisé, accessible de partout.',
    ZH: '数据安全存储在云端，随时随地访问。',
    JA: 'クラウドに安全に保存、どこからでもアクセス可能。',
    KO: '클라우드에 안전하게 저장, 어디서든 접근 가능.',
    HI: 'सुरक्षित क्लाउड स्टोरेज, कहीं से भी एक्सेस करें।',
  },
  'hero.qris': {
    ID: 'QRIS Payment',
    EN: 'QRIS Payment',
    AR: 'دفع QRIS',
    ES: 'Pago QRIS',
    PT: 'Pagamento QRIS',
    FR: 'Paiement QRIS',
    ZH: 'QRIS 支付',
    JA: 'QRIS決済',
    KO: 'QRIS 결제',
    HI: 'QRIS भुगतान',
  },
  'hero.qris_sub': {
    ID: 'Terima pembayaran QRIS dari semua e-wallet dan bank di Indonesia.',
    EN: 'Accept QRIS payments from all e-wallets and banks.',
    AR: 'استقبل مدفوعات QRIS من جميع المحافظ الإلكترونية والبنوك.',
    ES: 'Acepta pagos QRIS de todas las billeteras electrónicas y bancos.',
    PT: 'Aceite pagamentos QRIS de todas as carteiras digitais e bancos.',
    FR: 'Acceptez les paiements QRIS de tous les portefeuilles numériques et banques.',
    ZH: '支持所有电子钱包和银行的 QRIS 支付。',
    JA: 'すべての電子ウォレットと銀行のQRIS決済に対応。',
    KO: '모든 전자지갑 및 은행의 QRIS 결제를 수락합니다.',
    HI: 'सभी ई-वॉलेट और बैंकों से QRIS भुगतान स्वीकार करें।',
  },
  'hero.offline': {
    ID: 'Offline Mode',
    EN: 'Offline Mode',
    AR: 'وضع عدم الاتصال',
    ES: 'Modo sin conexión',
    PT: 'Modo offline',
    FR: 'Mode hors ligne',
    ZH: '离线模式',
    JA: 'オフラインモード',
    KO: '오프라인 모드',
    HI: 'ऑफ़लाइन मोड',
  },
  'hero.offline_sub': {
    ID: 'Tetap bisa berjualan meskipun tanpa koneksi internet.',
    EN: 'Keep selling even without internet connection.',
    ar: 'واصل البيع حتى بدون اتصال بالإنترنت.',
    ES: 'Sigue vendiendo sin conexión a internet.',
    PT: 'Continue vendendo mesmo sem conexão com a internet.',
    FR: 'Continuez à vendre même sans connexion internet.',
    ZH: '即使没有网络连接也能继续销售。',
    JA: 'インターネット接続がなくても販売を継続。',
    KO: '인터넷 연결 없이도 계속 판매하세요.',
    HI: 'बिना इंटरनेट कनेक्शन के भी बेचते रहें।',
  },

  // ==================== SECTION 2: TRANSACTION ====================
  's2.label': {
    ID: 'Transaksi', EN: 'Transactions', AR: 'المعاملات', ES: 'Transacciones', PT: 'Transações',
    FR: 'Transactions', ZH: '交易', JA: '取引', KO: '거래', HI: 'लेनदेन',
  },
  's2.title': {
    ID: 'Transaksi Kilat, Tanpa Ribet',
    EN: 'Lightning-Fast Transactions',
    AR: 'معاملات سريعة كالبرق',
    ES: 'Transacciones ultrarrápidas',
    PT: 'Transações ultrarrápidas',
    FR: 'Transactions ultra-rapides',
    ZH: '极速交易，轻松无忧',
    JA: '電光石火の取引、手間なし',
    KO: '번개처럼 빠른 거래',
    HI: 'बिजली की तेज़ लेनदेन, बिना किसी परेशानी के',
  },
  's2.desc': {
    ID: 'Proses pembayaran dalam hitungan detik dengan QRIS, kartu, dan tunai.',
    EN: 'Process payments in seconds with QRIS, cards, and cash.',
    AR: 'معالجة المدفوعات في ثوانٍ باستخدام QRIS والبطاقات والنقد.',
    ES: 'Procesa pagos en segundos con QRIS, tarjetas y efectivo.',
    PT: 'Processe pagamentos em segundos com QRIS, cartões e dinheiro.',
    FR: 'Traitez les paiements en secondes avec QRIS, cartes et espèces.',
    ZH: '通过 QRIS、银行卡和现金在数秒内完成支付处理。',
    JA: 'QRIS、カード、現金で数秒で決済処理。',
    KO: 'QRIS, 카드, 현금으로 몇 초 만에 결제 처리.',
    HI: 'QRIS, कार्ड और नकद से सेकंड में भुगतान प्रोसेस करें।',
  },
  's2.qris': {
    ID: 'Bayar QRIS',
    EN: 'QRIS Payment',
    AR: 'دفع QRIS',
    ES: 'Pago QRIS',
    PT: 'Pagamento QRIS',
    FR: 'Paiement QRIS',
    ZH: 'QRIS 支付',
    JA: 'QRIS決済',
    KO: 'QRIS 결제',
    HI: 'QRIS भुगतान',
  },
  's2.qris_desc': {
    ID: 'Scan QR langsung dari aplikasi. Mendukung semua e-wallet.',
    EN: 'Scan QR directly from the app. Supports all e-wallets.',
    AR: 'امسح QR مباشرة من التطبيق. يدعم جميع المحافظ الإلكترونية.',
    ES: 'Escanea QR directamente desde la app. Compatible con todas las billeteras.',
    PT: 'Escaneie QR direto do app. Compatível com todas as carteiras digitais.',
    FR: 'Scannez le QR directement depuis l\'app. Compatible avec tous les portefeuilles.',
    ZH: '直接从应用扫描 QR 码，支持所有电子钱包。',
    JA: 'アプリから直接QRスキャン。すべての電子ウォレット対応。',
    KO: '앱에서 직접 QR 스캔. 모든 전자지갑 지원.',
    HI: 'ऐप से सीधे QR स्कैन करें। सभी ई-वॉलेट समर्थित।',
  },
  's2.receipt': {
    ID: 'Struk Digital',
    EN: 'Digital Receipt',
    AR: 'إيصال رقمي',
    ES: 'Recibo digital',
    PT: 'Recibo digital',
    FR: 'Reçu numérique',
    ZH: '电子收据',
    JA: 'デジタルレシート',
    KO: '디지털 영수증',
    HI: 'डिजिटल रसीद',
  },
  's2.receipt_desc': {
    ID: 'Struk otomatis dikirim via WhatsApp & email.',
    EN: 'Receipts auto-sent via WhatsApp & email.',
    AR: 'يتم إرسال الإيصال تلقائياً عبر واتساب والبريد الإلكتروني.',
    ES: 'Recibos enviados automáticamente por WhatsApp y email.',
    PT: 'Recibos enviados automaticamente por WhatsApp e email.',
    FR: 'Reçus envoyés automatiquement par WhatsApp et email.',
    ZH: '收据自动通过 WhatsApp 和邮件发送。',
    JA: 'レシートをWhatsAppとメールで自動送信。',
    KO: '영수증을 WhatsApp과 이메일로 자동 전송.',
    HI: 'रसीदें WhatsApp और ईमेल से स्वतः भेजी जाती हैं।',
  },
  's2.hint': {
    ID: 'Klik di atas untuk mensimulasikan transaksi',
    EN: 'Click above to simulate a transaction',
    AR: 'انقر أعلاه لمحاكاة المعاملة',
    ES: 'Haz clic arriba para simular una transacción',
    PT: 'Clique acima para simular uma transação',
    FR: 'Cliquez ci-dessus pour simuler une transaction',
    ZH: '点击上方模拟交易',
    JA: 'クリックして取引をシミュレート',
    KO: '위를 클릭하여 거래 시뮬레이션',
    HI: 'लेनदेन का अनुकरण करने के लिए ऊपर क्लिक करें',
  },

  // ==================== SECTION 3: INVENTORY ====================
  's3.label': {
    ID: 'Manajemen Stok', EN: 'Inventory', AR: 'المخزون', ES: 'Inventario', PT: 'Inventário',
    FR: 'Inventaire', ZH: '库存管理', JA: '在庫管理', KO: '재고 관리', HI: 'इन्वेंट्री',
  },
  's3.title': {
    ID: 'Stok Selalu Terkendali',
    EN: 'Inventory Always Under Control',
    AR: 'المخزون دائماً تحت السيطرة',
    ES: 'Inventario siempre bajo control',
    PT: 'Inventário sempre sob controle',
    FR: 'Inventaire toujours sous contrôle',
    ZH: '库存始终尽在掌控',
    JA: '在庫を常にコントロール',
    KO: '재고를 항상 통제하세요',
    HI: 'इन्वेंट्री हमेशा नियंत्रण में',
  },
  's3.desc': {
    ID: 'Pantau stok real-time, atur restock otomatis, dan cegah kehabisan barang.',
    EN: 'Monitor stock in real-time, set auto-restock, prevent stockouts.',
    AR: 'راقب المخزون في الوقت الفعلي، اضبط إعادة التعبئة التلقائية.',
    ES: 'Monitorea el stock en tiempo real, configura auto-reposición.',
    PT: 'Monitore o estoque em tempo real, configure auto-reposição.',
    FR: 'Surveillez le stock en temps réel, configurez le réapprovisionnement auto.',
    ZH: '实时监控库存，设置自动补货，防止缺货。',
    JA: 'リアルタイムで在庫を監視、自動補充を設定、品切れを防止。',
    KO: '재고를 실시간으로 모니터링하고 자동 리스탁을 설정하세요.',
    HI: 'रीयल-टाइम में स्टॉक मॉनिटर करें, ऑटो-रीस्टॉक सेट करें।',
  },
  's3.restock_alert': {
    ID: 'Alert Restock',
    EN: 'Restock Alert',
    AR: 'تنبيه إعادة التعبئة',
    ES: 'Alerta de reposición',
    PT: 'Alerta de reposição',
    FR: 'Alerte de réapprovisionnement',
    ZH: '补货提醒',
    JA: '補充アラート',
    KO: '리스탁 알림',
    HI: 'रीस्टॉक अलर्ट',
  },
  's3.restock_desc': {
    ID: 'Notifikasi otomatis saat stok mencapai batas minimum.',
    EN: 'Auto notifications when stock hits minimum threshold.',
    ar: 'إشعارات تلقائية عند وصول المخزون للحد الأدنى.',
    ES: 'Notificaciones automáticas cuando el stock alcanza el mínimo.',
    PT: 'Notificações automáticas quando o estoque atinge o mínimo.',
    FR: 'Notifications automatiques lorsque le stock atteint le seuil minimum.',
    ZH: '库存达到最低阈值时自动通知。',
    JA: '在庫が最小閾値に達すると自動通知。',
    KO: '재고가 최소 임계값에 도달하면 자동 알림.',
    HI: 'स्टॉक न्यूनतम सीमा तक पहुँचने पर स्वचालित सूचनाएँ।',
  },
  's3.zero': {
    ID: 'Nol Komplikasi',
    EN: 'Zero Complications',
    AR: 'صفر تعقيد',
    ES: 'Cero complicaciones',
    PT: 'Zero complicações',
    FR: 'Zéro complication',
    ZH: '零复杂度',
    JA: '複雑さゼロ',
    KO: '복잡성 제로',
    HI: 'शून्य जटिलता',
  },
  's3.oneclick': {
    ID: 'Satu Klik untuk Lihat Semua',
    EN: 'One Click to See All',
    AR: 'نقرة واحدة لرؤية الكل',
    ES: 'Un clic para ver todo',
    PT: 'Um clique para ver tudo',
    FR: 'Un clic pour tout voir',
    ZH: '一键查看全部',
    JA: 'ワンクリックで全表示',
    KO: '한 번의 클릭으로 모두 보기',
    HI: 'सब कुछ देखने के लिए एक क्लिक',
  },

  // ==================== SECTION 4: MULTI DEVICE ====================
  's4.label': {
    ID: 'Multi Perangkat', EN: 'Multi-Device', AR: 'متعدد الأجهزة', ES: 'Multi-dispositivo', PT: 'Multi-dispositivo',
    FR: 'Multi-appareil', ZH: '多设备', JA: 'マルチデバイス', KO: '다중 디바이스', HI: 'मल्टी-डिवाइस',
  },
  's4.title': {
    ID: 'Satu Usaha, Banyak Perangkat',
    EN: 'One Business, Many Devices',
    AR: 'عمل واحد، أجهزة متعددة',
    ES: 'Un negocio, muchos dispositivos',
    PT: 'Um negócio, muitos dispositivos',
    FR: 'Une entreprise, plusieurs appareils',
    ZH: '一个门店，多台设备',
    JA: '一つの店舗、複数のデバイス',
    KO: '하나의 사업, 여러 디바이스',
    HI: 'एक व्यवसाय, कई डिवाइस',
  },
  's4.desc': {
    ID: 'Gunakan POS di HP, tablet, dan komputer secara bersamaan. Sinkron real-time.',
    EN: 'Use POS on phone, tablet, and computer simultaneously. Real-time sync.',
    AR: 'استخدم نقطة البيع على الهاتف والتابلت والحاسوب في نفس الوقت.',
    ES: 'Usa el POS en teléfono, tableta y computadora simultáneamente.',
    PT: 'Use o POS no celular, tablet e computador simultaneamente.',
    FR: 'Utilisez le POS sur téléphone, tablette et ordinateur simultanément.',
    ZH: '同时在手机、平板和电脑上使用 POS 系统，实时同步。',
    JA: 'スマホ、タブレット、PCで同時POS利用。リアルタイム同期。',
    KO: '스마트폰, 태블릿, 컴퓨터에서 동시에 POS를 사용하세요.',
    HI: 'फ़ोन, टैबलेट और कंप्यूटर पर एक साथ POS उपयोग करें।',
  },
  's4.simulate': {
    ID: 'Simulasi Multi-Device',
    EN: 'Multi-Device Simulation',
    AR: 'محاكاة الأجهزة المتعددة',
    ES: 'Simulación multi-dispositivo',
    PT: 'Simulação multi-dispositivo',
    FR: 'Simulation multi-appareil',
    ZH: '多设备模拟',
    JA: 'マルチデバイスシミュレーション',
    KO: '다중 디바이스 시뮬레이션',
    HI: 'मल्टी-डिवाइस सिमुलेशन',
  },
  's4.simulate_desc': {
    ID: 'Klik untuk melihat bagaimana sinkronisasi bekerja.',
    EN: 'Click to see how sync works.',
    AR: 'انقر لرؤية كيف تعمل المزامنة.',
    ES: 'Haz clic para ver cómo funciona la sincronización.',
    PT: 'Clique para ver como a sincronização funciona.',
    FR: 'Cliquez pour voir comment la synchronisation fonctionne.',
    ZH: '点击查看同步如何工作。',
    JA: 'クリックして同期の仕組みを確認。',
    KO: '클릭하여 동기화 작동 방식을 확인하세요.',
    HI: 'सिंक कैसे काम करता है देखने के लिए क्लिक करें।',
  },
  's4.connected': {
    ID: 'Terhubung',
    EN: 'Connected',
    AR: 'متصل',
    ES: 'Conectado',
    PT: 'Conectado',
    FR: 'Connecté',
    ZH: '已连接',
    JA: '接続済み',
    KO: '연결됨',
    HI: 'कनेक्टेड',
  },
  's4.device1': {
    ID: 'HP Kasir',
    EN: 'Cashier Phone',
    AR: 'هاتف الكاشير',
    ES: 'Teléfono del cajero',
    PT: 'Telefone do caixa',
    FR: 'Téléphone caisse',
    ZH: '收银手机',
    JA: 'レジスマホ',
    KO: '캐시어 폰',
    HI: 'कैशियर फ़ोन',
  },
  's4.device1_desc': {
    ID: 'Transaksi jualan langsung dari HP.',
    EN: 'Process sales directly from your phone.',
    ar: 'معالجة المبيعات مباشرة من هاتفك.',
    ES: 'Procesa ventas directamente desde tu teléfono.',
    PT: 'Processe vendas diretamente do seu telefone.',
    FR: 'Traitez les ventes directement depuis votre téléphone.',
    ZH: '直接从手机处理销售。',
    JA: 'スマホから直接販売を処理。',
    KO: '스마트폰에서 직접 판매를 처리하세요.',
    HI: 'अपने फ़ोन से सीधे बिक्री प्रोसेस करें।',
  },
  's4.device2': {
    ID: 'Tablet Display',
    EN: 'Tablet Display',
    AR: 'شاشة التابلت',
    ES: 'Pantalla de tableta',
    PT: 'Tela do tablet',
    FR: 'Affichage tablette',
    ZH: '平板展示',
    JA: 'タブレット表示',
    KO: '태블릿 디스플레이',
    HI: 'टैबलेट डिस्प्ले',
  },
  's4.device2_desc': {
    ID: 'Lihat menu dan daftar produk.',
    EN: 'View menu and product list.',
    ar: 'عرض القائمة وقائمة المنتجات.',
    ES: 'Ver menú y lista de productos.',
    PT: 'Ver menu e lista de produtos.',
    FR: 'Voir le menu et la liste des produits.',
    ZH: '查看菜单和产品列表。',
    JA: 'メニューと商品一覧を表示。',
    KO: '메뉴와 제품 목록을 확인하세요.',
    HI: 'मेनू और उत्पाद सूची देखें।',
  },
  's4.device3': {
    ID: 'PC Dashboard',
    EN: 'PC Dashboard',
    AR: 'لوحة تحكم الكمبيوتر',
    ES: 'Panel de PC',
    PT: 'Painel do PC',
    FR: 'Tableau de bord PC',
    ZH: '电脑仪表盘',
    JA: 'PCダッシュボード',
    KO: 'PC 대시보드',
    HI: 'PC डैशबोर्ड',
  },
  's4.device3_desc': {
    ID: 'Lihat laporan dan analitik lengkap.',
    EN: 'View full reports and analytics.',
    ar: 'عرض التقارير والتحليلات الكاملة.',
    ES: 'Ver reportes y analíticas completas.',
    PT: 'Ver relatórios e análises completos.',
    FR: 'Voir les rapports et analyses complets.',
    ZH: '查看完整报告和分析。',
    JA: '完全なレポートと分析を表示。',
    KO: '전체 리포트와 분석을 확인하세요.',
    HI: 'पूर्ण रिपोर्ट और विश्लेषण देखें।',
  },

  // ==================== SECTION 5: ANALYTICS ====================
  's5.label': {
    ID: 'Analitik Usaha', EN: 'Analytics', AR: 'التحليلات', ES: 'Analíticas', PT: 'Análises',
    FR: 'Analytiques', ZH: '分析', JA: '分析', KO: '분석', HI: 'विश्लेषण',
  },
  's5.title': {
    ID: 'Data Usaha, Langsung Jelas',
    EN: 'Business Data, Crystal Clear',
    AR: 'بيانات الأعمال، واضحة تماماً',
    ES: 'Datos del negocio, cristalinos',
    PT: 'Dados do negócio, cristalinos',
    FR: 'Données de l\'entreprise, claires comme le jour',
    ZH: '业务数据，一目了然',
    JA: 'ビジネスデータ、すみ通る透明さ',
    KO: '비즈니스 데이터, 명확하게',
    HI: 'व्यापार डेटा, एकदम स्पष्ट',
  },
  's5.desc': {
    ID: 'Dashboard analytics untuk memahami penjualan, stok, dan performa usaha.',
    EN: 'Analytics dashboard to understand sales, inventory, and business performance.',
    AR: 'لوحة تحليلات لفهم المبيعات وأداء الأعمال.',
    ES: 'Panel de analíticas para entender ventas, inventario y rendimiento.',
    PT: 'Painel de análises para entender vendas, estoque e desempenho.',
    FR: 'Tableau de bord analytique pour comprendre les ventes et la performance.',
    ZH: '分析仪表盘，了解销售、库存和业务表现。',
    JA: '売上・在庫・パフォーマンスを把握する分析ダッシュボード。',
    KO: '매출, 재고, 비즈니스 성과를 파악하는 분석 대시보드.',
    HI: 'बिक्री, इन्वेंट्री और व्यवसाय प्रदर्शन को समझने के लिए एनालिटिक्स डैशबोर्ड।',
  },
  's5.hourly': {
    ID: 'Penjualan Per Jam',
    EN: 'Hourly Sales',
    AR: 'المبيعات بالساعة',
    ES: 'Ventas por hora',
    PT: 'Vendas por hora',
    FR: 'Ventes par heure',
    ZH: '每小时销售',
    JA: '時間別売上',
    KO: '시간별 매출',
    HI: 'प्रति घंटा बिक्री',
  },
  's5.hourly_desc': {
    ID: 'Grafik penjualan sepanjang hari untuk optimasi jam operasional.',
    EN: 'Sales chart throughout the day for operational optimization.',
    ar: 'رسم بياني للمبيعات على مدار اليوم لتحسين العمليات.',
    ES: 'Gráfico de ventas durante el día para optimización operativa.',
    PT: 'Gráfico de vendas ao longo do dia para otimização operacional.',
    FR: 'Graphique des ventes tout au long de la journée pour l\'optimisation.',
    ZH: '全天销售图表，优化营业时间。',
    JA: '一日中の売上グラフで営業時間を最適化。',
    KO: '하루 종일 매출 차트로 영업 시간을 최적화하세요.',
    HI: 'परिचालन अनुकूलन के लिए पूरे दिन की बिक्री चार्ट।',
  },
  's5.bestseller': {
    ID: 'Produk Terlaris',
    EN: 'Best Sellers',
    AR: 'المنتجات الأكثر مبيعاً',
    ES: 'Más vendidos',
    PT: 'Mais vendidos',
    FR: 'Meilleures ventes',
    ZH: '热销产品',
    JA: 'ベストセラー',
    KO: '베스트셀러',
    HI: 'सर्वाधिक बिकने वाले',
  },
  's5.bestseller_desc': {
    ID: 'Lihat produk mana yang paling laris dan strategi promosi.',
    EN: 'See which products sell best and plan promotions.',
    ar: 'شاهد المنتجات الأكثر مبيعاً وخطط للترويج.',
    ES: 'Ve qué productos se venden mejor y planifica promociones.',
    PT: 'Veja quais produtos vendem mais e planeje promoções.',
    FR: 'Voyez les produits les plus vendus et planifiez les promotions.',
    ZH: '查看最畅销的产品并规划促销策略。',
    JA: '一番売れている商品を確認し、プロモーションを計画。',
    KO: '가장 많이 팔리는 제품을 확인하고 프로모션을 계획하세요.',
    HI: 'देखें कौन से उत्पाद सबसे अधिक बिकते हैं और प्रमोशन की योजना बनाएँ।',
  },
  's5.export': {
    ID: 'Export Laporan',
    EN: 'Export Reports',
    AR: 'تصدير التقارير',
    ES: 'Exportar reportes',
    PT: 'Exportar relatórios',
    FR: 'Exporter les rapports',
    ZH: '导出报告',
    JA: 'レポートをエクスポート',
    KO: '리포트 내보내기',
    HI: 'रिपोर्ट एक्सपोर्ट करें',
  },
  's5.multi': {
    ID: 'Multi-Cabang',
    EN: 'Multi-Branch',
    AR: 'متعدد الفروع',
    ES: 'Multi-sucursal',
    PT: 'Multi-filial',
    FR: 'Multi-agence',
    ZH: '多门店',
    JA: 'マルチブランチ',
    KO: '다중 지점',
    HI: 'मल्टी-ब्रांच',
  },

  // ==================== SECTION 6: TESTIMONIALS ====================
  's6.label': {
    ID: 'Testimoni', EN: 'Testimonials', AR: 'الشهادات', ES: 'Testimonios', PT: 'Depoimentos',
    FR: 'Témoignages', ZH: '客户评价', JA: 'お客様の声', KO: '고객 후기', HI: 'प्रशंसापत्र',
  },

  // ==================== CTA SECTION ====================
  'cta.start_free': {
    ID: 'Mulai Gratis Sekarang',
    EN: 'Start Free Now',
    AR: 'ابدأ مجاناً الآن',
    ES: 'Empieza gratis ahora',
    PT: 'Comece grátis agora',
    FR: 'Commencez gratuitement',
    ZH: '立即免费开始',
    JA: '今すぐ無料で始める',
    KO: '지금 무료로 시작하세요',
    HI: 'अभी मुफ़्त में शुरू करें',
  },
  'cta.no_credit': {
    ID: 'Tanpa kartu kredit. Tanpa komitmen.',
    EN: 'No credit card. No commitment.',
    AR: 'بدون بطاقة ائتمان. بدون التزام.',
    ES: 'Sin tarjeta de crédito. Sin compromiso.',
    PT: 'Sem cartão de crédito. Sem compromisso.',
    FR: 'Sans carte de crédit. Sans engagement.',
    ZH: '无需信用卡，无需承诺。',
    JA: 'クレジットカード不要。コミットなし。',
    KO: '신용카드 불필요. 약정 없음.',
    HI: 'कोई क्रेडिट कार्ड नहीं। कोई प्रतिबद्धता नहीं。',
  },
};

interface LanguageContextValue {
  lang: LangCode;
  setLang: (code: LangCode) => void;
  t: (key: string) => string;
}

const LanguageContext = createContext<LanguageContextValue | undefined>(undefined);

export function LanguageProvider({ children }: { children: ReactNode }) {
  const [lang, setLangState] = useState<LangCode>(() => {
    if (typeof window !== 'undefined') {
      const saved = localStorage.getItem('zonakasir_lang');
      if (saved && saved in translations[Object.keys(translations)[0]]) {
        return saved as LangCode;
      }
    }
    return 'ID';
  });

  const setLang = useCallback((code: LangCode) => {
    setLangState(code);
    localStorage.setItem('zonakasir_lang', code);
    document.documentElement.lang = code.toLowerCase();
  }, []);

  const t = useCallback((key: string): string => {
    const entry = translations[key];
    if (!entry) {
      console.warn(`Missing translation key: ${key}`);
      return key;
    }
    return entry[lang] ?? entry['EN'] ?? key;
  }, [lang]);

  return (
    <LanguageContext.Provider value={{ lang, setLang, t }}>
      {children}
    </LanguageContext.Provider>
  );
}

export function useLanguage(): LanguageContextValue {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useLanguage must be used within a LanguageProvider');
  }
  return context;
}
