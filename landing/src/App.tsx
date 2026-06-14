/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect, useRef } from 'react';
import { 
  ArrowRight, ShieldCheck, Zap, Layers, RefreshCw, SmartphoneIcon, Tablet, 
  Tv, Database, ShoppingBag, BarChart3, Check, Star, CheckCircle, Quote, Layout, MessageCircle
} from 'lucide-react';
import { motion, useScroll, useTransform, useSpring } from 'motion/react';
import Header from './components/Header';
import DeviceTablet from './components/DeviceTablet';
import DevicePhone from './components/DevicePhone';
import DeviceLaptop from './components/DeviceLaptop';
import { TESTIMONIALS } from './data';
import { useLanguage } from './i18n';

export default function App() {
  const { t } = useLanguage();
  const [activeSection, setActiveSection] = useState(0);
  const containerRef = useRef<HTMLDivElement>(null);

  // Declare discrete section element refs to track exact viewport scroll entries
  const heroRef = useRef<HTMLDivElement>(null);
  const transactionRef = useRef<HTMLDivElement>(null);
  const inventoryRef = useRef<HTMLDivElement>(null);
  const devicesRef = useRef<HTMLDivElement>(null);
  const analyticsRef = useRef<HTMLDivElement>(null);
  const testimonialsRef = useRef<HTMLDivElement>(null);
  const faqRef = useRef<HTMLDivElement>(null);
  const socialRef = useRef<HTMLDivElement>(null);
  const pricingRef = useRef<HTMLDivElement>(null);
  const ctaRef = useRef<HTMLDivElement>(null);

  const sectionRefs = [
    heroRef,
    transactionRef,
    inventoryRef,
    devicesRef,
    analyticsRef,
    testimonialsRef,
    faqRef,
    socialRef,
    pricingRef,
    ctaRef
  ];

  // Hook up responsive scroll tracker for real-time parallax scrolling per section
  const scrollS0 = useScroll({ target: heroRef, offset: ["start end", "end start"] });
  const scrollS1 = useScroll({ target: transactionRef, offset: ["start end", "end start"] });
  const scrollS2 = useScroll({ target: inventoryRef, offset: ["start end", "end start"] });
  const scrollS3 = useScroll({ target: devicesRef, offset: ["start end", "end start"] });
  const scrollS4 = useScroll({ target: analyticsRef, offset: ["start end", "end start"] });
  const scrollS5 = useScroll({ target: testimonialsRef, offset: ["start end", "end start"] });
  const scrollS_faq = useScroll({ target: faqRef, offset: ["start end", "end start"] });
  const scrollS6 = useScroll({ target: socialRef, offset: ["start end", "end start"] });
  const scrollS7 = useScroll({ target: pricingRef, offset: ["start end", "end start"] });
  const scrollS8 = useScroll({ target: ctaRef, offset: ["start end", "end start"] });

  // SECTION 1 (Hero) - Dynamic multi-depth parallax
  const yLaptop = useTransform(scrollS0.scrollYProgress, [0, 1], [0, -75]);
  const yTablet = useTransform(scrollS0.scrollYProgress, [0, 1], [40, -115]);
  const yPhone = useTransform(scrollS0.scrollYProgress, [0, 1], [80, -170]);
  const yDecor1 = useTransform(scrollS0.scrollYProgress, [0, 1], [-90, 90]);
  const yDecor2 = useTransform(scrollS0.scrollYProgress, [0, 1], [80, -80]);

  // SECTION 2 (Transaction Live Simulator Layout) - Glides cleanly below copy
  const yS2Tablet = useTransform(scrollS1.scrollYProgress, [0, 1], [50, -50]);
  const yDecor3 = useTransform(scrollS1.scrollYProgress, [0, 1], [-100, 100]);

  // SECTION 3 (Inventory PC Laptop) - Floating administrative interface
  const yS3Laptop = useTransform(scrollS2.scrollYProgress, [0, 1], [65, -65]);
  const yS3Decor = useTransform(scrollS2.scrollYProgress, [0, 1], [-95, 95]);

  // SECTION 4 (Multi Sycned Devices) - Splitting device speeds as you scroll
  const yS4Device1 = useTransform(scrollS3.scrollYProgress, [0, 1], [50, -50]);  // Handphone
  const yS4Device2 = useTransform(scrollS3.scrollYProgress, [0, 1], [20, -110]); // Tablet
  const yS4Device3 = useTransform(scrollS3.scrollYProgress, [0, 1], [90, -45]);  // PC Laptop
  const yS4Decor = useTransform(scrollS3.scrollYProgress, [0, 1], [-85, 85]);

  // SECTION 5 (Business Analytics Reports) - Elegant glide of charts and figures
  const yS5Laptop = useTransform(scrollS4.scrollYProgress, [0, 1], [70, -70]);
  const yS5Decor = useTransform(scrollS4.scrollYProgress, [0, 1], [-100, 100]);

  // SECTION 6 (Testimonials Storyboard) - Parallax between client portrait photo and backdraft spacer
  const yS6Image = useTransform(scrollS5.scrollYProgress, [0, 1], [35, -35]);
  const yS6Shadow = useTransform(scrollS5.scrollYProgress, [0, 1], [-45, 45]);

  // SECTION 6B (FAQ Accordions) - Parallax background drift
  const yS_faqFloat = useTransform(scrollS_faq.scrollYProgress, [0, 1], [35, -35]);
  const yS_faqDecor = useTransform(scrollS_faq.scrollYProgress, [0, 1], [-85, 85]);

  // SECTION 7 (Social Proof Storefront) - Dual-directional depth layers
  const yS7Storefront = useTransform(scrollS6.scrollYProgress, [0, 1], [55, -55]);
  const yS7Stats = useTransform(scrollS6.scrollYProgress, [0, 1], [-30, 30]);

  // SECTION 8 (Pricing Catalog) - Slow-motion parallax pricing card elements
  const yS8PricingFloat = useTransform(scrollS7.scrollYProgress, [0, 1], [40, -40]);
  const yS8PricingDecor = useTransform(scrollS7.scrollYProgress, [0, 1], [-80, 80]);

  // SECTION 9 (Final Call To Action Card) - Float up as footer is approached
  const yS9Content = useTransform(scrollS8.scrollYProgress, [0, 1], [65, -65]);

  // Smoothen all motion curves using high-fidelity spring curves
  const smoothLaptopY = useSpring(yLaptop, { stiffness: 95, damping: 20 });
  const smoothTabletY = useSpring(yTablet, { stiffness: 95, damping: 20 });
  const smoothPhoneY = useSpring(yPhone, { stiffness: 95, damping: 20 });
  const smoothDecor1Y = useSpring(yDecor1, { stiffness: 60, damping: 25 });
  const smoothDecor2Y = useSpring(yDecor2, { stiffness: 60, damping: 25 });
  const smoothDecor3Y = useSpring(yDecor3, { stiffness: 60, damping: 25 });

  const smoothS2TabletY = useSpring(yS2Tablet, { stiffness: 95, damping: 20 });

  const smoothS3LaptopY = useSpring(yS3Laptop, { stiffness: 95, damping: 20 });
  const smoothS3DecorY = useSpring(yS3Decor, { stiffness: 60, damping: 25 });

  const smoothS4Device1Y = useSpring(yS4Device1, { stiffness: 95, damping: 20 });
  const smoothS4Device2Y = useSpring(yS4Device2, { stiffness: 95, damping: 20 });
  const smoothS4Device3Y = useSpring(yS4Device3, { stiffness: 95, damping: 20 });
  const smoothS4DecorY = useSpring(yS4Decor, { stiffness: 60, damping: 25 });

  const smoothS5LaptopY = useSpring(yS5Laptop, { stiffness: 95, damping: 20 });
  const smoothS5DecorY = useSpring(yS5Decor, { stiffness: 60, damping: 25 });

  const smoothS6ImageY = useSpring(yS6Image, { stiffness: 95, damping: 20 });
  const smoothS6ShadowY = useSpring(yS6Shadow, { stiffness: 95, damping: 20 });

  const smoothS_faqFloatY = useSpring(yS_faqFloat, { stiffness: 95, damping: 20 });
  const smoothS_faqDecorY = useSpring(yS_faqDecor, { stiffness: 60, damping: 25 });

  const smoothS7StorefrontY = useSpring(yS7Storefront, { stiffness: 95, damping: 20 });
  const smoothS7StatsY = useSpring(yS7Stats, { stiffness: 95, damping: 20 });

  const smoothS8PricingFloatY = useSpring(yS8PricingFloat, { stiffness: 95, damping: 20 });
  const smoothS8PricingDecorY = useSpring(yS8PricingDecor, { stiffness: 60, damping: 25 });

  const smoothS9ContentY = useSpring(yS9Content, { stiffness: 95, damping: 20 });

  // Active sync state for section 4 demonstration
  const [syncCount, setSyncCount] = useState(0);
  const [lastSyncTotal, setLastSyncTotal] = useState(58000);
  const [lastSyncMethod, setLastSyncMethod] = useState('QRIS');

  // Billing cycle toggle state for Section 8 Pricing
  const [billingCycle, setBillingCycle] = useState<'monthly' | 'annually'>('monthly');

  // Active testimonial index for story section
  const [activeTestimonial, setActiveTestimonial] = useState(0);

  // Active FAQ index for accordion system
  const [activeFaq, setActiveFaq] = useState<number | null>(null);

  // Active custom modal system instead of window.alert()
  const [activeModal, setActiveModal] = useState<'none' | 'register' | 'demo' | 'consultation' | 'success'>('none');
  const [successSource, setSuccessSource] = useState<'register' | 'demo' | 'consultation'>('register');

  // Intersection Observer to detect scroll position cleanly
  useEffect(() => {
    const observerOptions = {
      root: null, // Relative to browser viewport for standard window scroll
      threshold: 0.3, // Trigger when 30% of the section is visible
    };

    const observers = sectionRefs.map((ref, idx) => {
      const observer = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
          setActiveSection(idx);
        }
      }, observerOptions);

      if (ref.current) {
        observer.observe(ref.current);
      }
      return observer;
    });

    return () => {
      observers.forEach((obs) => obs.disconnect());
    };
  }, []);

  // Smooth scroll helper
  const scrollToSection = (index: number) => {
    if (sectionRefs[index]?.current) {
      sectionRefs[index].current.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
      });
      setActiveSection(index);
    }
  };

  // Safe manual order sync triggers for section 4 sync demonstration
  const handleSimulateSync = (price: number, method: string) => {
    setLastSyncTotal(price);
    setLastSyncMethod(method);
    setSyncCount(prev => prev + 1);
  };

  return (
    <div className="bg-[#F4F4F2] text-[#1A1A1A] font-sans min-h-screen relative flex flex-col antialiased selection:bg-[#1A1A1A] selection:text-white">
      {/* Absolute Master Header */}
      <Header activeSection={activeSection} scrollToSection={scrollToSection} />

      {/* Floating Section Indicators (Dot Navigator) - Hidden on Mobile */}
      <div className="fixed right-6 top-1/2 -translate-y-1/2 z-40 hidden md:flex flex-col items-center gap-4 bg-white/80 backdrop-blur-sm px-2.5 py-5 rounded-[8px] border border-[#E5E5E1] shadow-sm">
        {sectionRefs.map((_, idx) => (
          <button
            key={idx}
            onClick={() => scrollToSection(idx)}
            className={`w-2.5 h-2.5 rounded-full transition-all duration-300 relative group cursor-pointer ${
              activeSection === idx 
                ? 'bg-[#1A1A1A] scale-125' 
                : 'bg-[#D1D1CC] hover:bg-[#666666]'
            }`}
            title={`Seksi ${idx + 1}`}
            id={`dot-nav-${idx}`}
          >
            <span className="absolute right-6 top-1/2 -translate-y-1/2 bg-[#1A1A1A] text-white text-[9px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-[4px] opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-sm">
              {idx === 0 && 'Beranda'}
              {idx === 1 && 'Transaksi'}
              {idx === 2 && 'Stok Gudang'}
              {idx === 3 && 'Multi Device'}
              {idx === 4 && 'Analitik'}
              {idx === 5 && 'Kisah Sukses'}
              {idx === 6 && 'FAQ'}
              {idx === 7 && 'Sosial Bukti'}
              {idx === 8 && 'Paket Berlangganan'}
              {idx === 9 && 'Mulai Gratis'}
            </span>
          </button>
        ))}
      </div>

      {/* Master Cinematic Scrolling Container - Normalized Scroll */}
      <div 
        ref={containerRef}
        className="flex-1 w-full min-h-screen flex flex-col"
        id="cinematic-scroller"
      >

        {/* ================= SECTION 1: HERO SECTION ================= */}
        <section 
          ref={sectionRefs[0]}
          className="min-h-screen w-full flex flex-col justify-center relative overflow-hidden py-16 md:py-24 bg-[#F4F4F2]"
          id="section-hero"
        >
          {/* Subtle blurred store photography background */}
          <div className="absolute inset-0 z-0">
            <img 
              src="/images/landing/retail_hero_bg_1781378962689.jpg" 
              alt="Retail Environment Background" 
              className="w-full h-full object-cover opacity-10 filter grayscale contrast-125 select-none"
              referrerPolicy="no-referrer"
            />
            {/* Elegant vignette overlay */}
            <div className="absolute inset-0 bg-radial-gradient-vignette" />
          </div>

          <div className="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center relative z-10 w-full">
            {/* Hero Copy */}
            <div className="lg:col-span-5 space-y-6 text-left">
              <div className="inline-block px-3 py-1 bg-[#1A1A1A]/5 rounded-[4px] text-[11px] font-bold text-[#1A1A1A] uppercase tracking-[0.2em] mb-4">
                {t('hero.badge')}
              </div>
              
              <h1 className="font-sans font-bold text-4xl sm:text-5xl lg:text-[54px] text-[#1A1A1A] leading-[1.05] tracking-tight mb-6">
                {t('hero.title1')}<br />
                {t('hero.title2')}<br />
                <span className="text-[#666666]">{t('hero.title3')}</span>
              </h1>

              <p className="font-sans text-[#555555] text-sm sm:text-base leading-relaxed max-w-lg font-medium">
                {t('hero.desc')}
              </p>

              <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 pt-2">
                <button 
                  onClick={() => scrollToSection(7)}
                  className="bg-[#1A1A1A] text-white font-sans text-xs font-bold px-8 py-4 rounded-[6px] hover:bg-[#1A1A1A]/90 transition-all shadow-sm active:scale-95 flex items-center justify-center gap-2 cursor-pointer"
                  id="hero-free-trial-btn"
                >
                  {t('hero.start')}
                  <ArrowRight className="w-4 h-4" />
                </button>
                <button 
                  onClick={() => scrollToSection(1)}
                  className="border border-[#D1D1CC] bg-white text-[#1A1A1A] font-sans text-xs font-bold px-8 py-4 rounded-[6px] hover:bg-[#F4F4F2] hover:border-[#1A1A1A]/30 transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-sm active:scale-95"
                  id="hero-demo-trigger"
                >
                  {t('hero.demo')}
                </button>
              </div>

              {/* Compliance Trust Tags */}
              <div className="pt-6 border-t border-[#E5E5E1] grid grid-cols-3 gap-4 text-left">
                <div>
                  <h4 className="font-mono text-xs font-bold text-[#1A1A1A]">{t('hero.cloud')}</h4>
                  <p className="text-[10px] text-[#888888] mt-0.5">{t('hero.cloud_sub')}</p>
                </div>
                <div>
                  <h4 className="font-mono text-xs font-bold text-[#1A1A1A]">{t('hero.qris')}</h4>
                  <p className="text-[10px] text-[#888888] mt-0.5">{t('hero.qris_sub')}</p>
                </div>
                <div>
                  <h4 className="font-mono text-xs font-bold text-[#1A1A1A]">{t('hero.offline')}</h4>
                  <p className="text-[10px] text-[#888888] mt-0.5">{t('hero.offline_sub')}</p>
                </div>
              </div>
            </div>

            {/* Foreground Parallax composition of device sizes */}
            <div className="lg:col-span-7 flex justify-center items-center relative h-[380px] lg:h-[480px]">
              {/* Subtle drifting background shapes for immediate 3D parallax feel */}
              <motion.div 
                style={{ y: smoothDecor1Y }}
                className="absolute -top-12 -left-12 w-24 h-24 rounded-full bg-slate-400/10 blur-xl pointer-events-none z-0" 
              />
              <motion.div 
                style={{ y: smoothDecor2Y }}
                className="absolute -bottom-16 -right-16 w-36 h-36 rounded-full bg-slate-350/15 blur-2xl pointer-events-none z-0" 
              />

              {/* Parallax Layer 1: Laptop HQ (Beige/Dark style) */}
              <motion.div 
                style={{ y: smoothLaptopY }}
                className="absolute top-4 left-4 lg:left-8 w-[92%] max-w-[480px] z-10 transition-shadow duration-1000 select-none shadow-xl border border-[#E5E5E1] rounded-[8px]"
              >
                <div className="bg-[#1A1A1A] rounded-[8px] p-2 overflow-hidden h-[240px] border border-[#1A1A1A]">
                  <div className="h-full bg-white rounded-[6px] p-4 text-[#1A1A1A] font-sans text-[10px] flex flex-col justify-between border border-gray-100">
                    <div className="flex justify-between items-center pb-2 border-b border-gray-100">
                      <div className="flex items-center gap-1.5">
                        <div className="w-3.5 h-3.5 bg-[#FF6600] rounded-[2px] flex items-center justify-center text-white text-[7px] font-bold">ZK</div>
                        <span className="font-bold text-[#1A1A1A] uppercase tracking-wider text-[8px]">Dashboard</span>
                      </div>
                      <span className="text-[7px] font-semibold text-[#888888]">Laporan Outlet 01</span>
                    </div>
                    {/* Visual graph mockup */}
                    <div className="flex-1 py-1 flex flex-col justify-end gap-1">
                      <div className="flex items-end justify-around h-24 gap-3">
                        <div className="w-6 bg-gray-100 h-[30%] rounded-[2px]" />
                        <div className="w-6 bg-gray-100 h-[50%] rounded-[2px]" />
                        <div className="w-6 bg-gray-150 h-[70%] rounded-[2px]" />
                        <div className="w-6 bg-[#FF6600] h-[90%] rounded-[2px] relative">
                          <span className="absolute -top-4 left-1/2 -translate-x-1/2 text-[7px] text-[#1A1A1A] font-extrabold font-mono">12.8M</span>
                        </div>
                      </div>
                      <div className="flex justify-between text-[7px] text-[#888888] px-2 pt-1 border-t border-gray-100">
                        <span>Minuman</span>
                        <span>Makanan</span>
                        <span>Kue Pastry</span>
                        <span>Total Omset</span>
                      </div>
                    </div>
                    {/* Foot metrics */}
                    <div className="flex justify-between text-[8px] border-t border-gray-100 pt-2 text-[#666666] font-bold uppercase tracking-wider">
                      <span>Total Transaksi: 285</span>
                      <span>Produk Populer: Kopi Aren</span>
                    </div>
                  </div>
                </div>
              </motion.div>

              {/* Parallax Layer 2: Tablet POS checkout page overlay (Moved forward) */}
              <motion.div 
                style={{ y: smoothTabletY }}
                className="absolute -bottom-2 right-4 lg:right-10 w-[62%] max-w-[325px] z-20 transition-shadow duration-750 select-none shadow-2xl"
              >
                <div className="bg-[#1A1A1A] rounded-[8px] p-1.5 border border-[#1A1A1A]">
                  <div className="bg-white rounded-[6px] h-[190px] p-3 flex flex-col justify-between text-gray-800">
                    <div className="flex justify-between border-b border-gray-100 pb-2">
                      <span className="text-[8px] font-extrabold tracking-wider text-[#1A1A1A] uppercase">Terminal POS #01</span>
                      <span className="text-[7px] bg-emerald-50 text-emerald-700 font-extrabold px-1.5 py-0.5 rounded border border-emerald-150 uppercase tracking-widest">LUNAS</span>
                    </div>
                    
                    <div className="space-y-1.5 py-1.5 flex-1 text-[9px] text-[#1A1A1A] font-medium leading-tight">
                      <div className="flex justify-between border-b border-dashed border-gray-100 pb-1">
                        <span>2x Kopi Susu Gula Aren</span>
                        <span className="font-mono font-bold">Rp 44.000</span>
                      </div>
                      <div className="flex justify-between border-b border-dashed border-gray-100 pb-1">
                        <span>1x Almond Croissant</span>
                        <span className="font-mono font-bold">Rp 28.000</span>
                      </div>
                    </div>

                    <div className="border-t border-dashed border-gray-100 pt-1.5 space-y-1">
                      <div className="flex justify-between text-[10px] font-extrabold text-[#1A1A1A]">
                        <span>TOTAL TUNAI</span>
                        <span className="font-mono">Rp 79.920</span>
                      </div>
                      <button className="w-full bg-[#FF6600] text-white text-[8px] py-1.5 rounded-[4px] font-bold uppercase tracking-wider">
                        Selesai & Cetak Struk
                      </button>
                    </div>
                  </div>
                </div>
              </motion.div>

              {/* Parallax Layer 3: Mobile POS terminal (Most Forward) */}
              <motion.div 
                style={{ y: smoothPhoneY }}
                className="absolute top-1/2 -translate-y-1/2 left-0 lg:-left-2 w-[34%] max-w-[170px] z-30 transition-shadow duration-500 select-none shadow-2xl"
              >
                <div className="bg-[#1A1A1A] rounded-[16px] p-1.5 border border-[#1A1A1A]">
                  <div className="bg-[#F4F4F2] rounded-[10px] h-[130px] p-2.5 text-[#1A1A1A] flex flex-col justify-between border border-gray-200">
                    <div className="flex justify-between text-[7px] text-[#666666] font-bold uppercase tracking-wider">
                      <span>ZonaKasir</span>
                      <span className="text-[6px] bg-emerald-50 text-emerald-800 font-bold px-1 rounded">Aktif</span>
                    </div>
                    <div className="text-center py-1">
                      <p className="text-[7px] text-[#666666] font-semibold uppercase tracking-wider">Saldo Tersedia</p>
                      <h4 className="text-[11px] font-mono font-extrabold text-[#FF6600] mt-0.5">Rp 4.250.000</h4>
                    </div>
                    <div className="bg-[#FF6600]/10 rounded-[4px] p-1 text-[6px] text-center text-[#FF6600] border border-[#FF6600]/20 font-bold uppercase tracking-wide">
                      Aplikasi Owner
                    </div>
                  </div>
                </div>
              </motion.div>
            </div>

          </div>
        </section>

        {/* ================= SECTION 2: REAL TRANSACTION EXPERIENCE ================= */}
        <section 
          ref={sectionRefs[1]}
          className="min-h-screen w-full flex flex-col justify-center bg-[#F4F4F2] relative p-6 py-20 md:py-28 overflow-hidden"
          id="section-transaction"
        >
          {/* Subtle slow drift background shape for additional premium parallax depth */}
          <motion.div 
            style={{ y: smoothDecor3Y }}
            className="absolute right-12 top-24 w-40 h-40 rounded-full bg-slate-300/10 blur-3xl pointer-events-none z-0" 
          />

          <motion.div 
            initial={{ opacity: 0, y: 35 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="max-w-4xl mx-auto w-full flex flex-col items-center text-center space-y-8 relative z-10"
          >
            {/* Story copy on TOP (Centered for clean, elegant layout) */}
            <div className="w-full max-w-2xl space-y-4 text-center">
              <span className="text-[10px] font-bold text-[#666666] tracking-wider uppercase block">
                {t('s2.label')}
              </span>
              <h2 className="font-sans font-bold text-3xl sm:text-4xl text-[#1A1A1A] leading-tight">
                {t('s2.title')}
              </h2>
              <p className="font-sans text-[#555555] text-sm sm:text-base leading-relaxed font-medium">
                {t('s2.desc')}
              </p>
            </div>

            {/* Interactive Tablet positioned BELOW the text with smooth non-clamping parallax */}
            <motion.div 
              style={{ y: smoothS2TabletY }}
              className="w-full flex justify-center items-center mt-6" 
              id="tablet-pos-wrapper"
            >
              <DeviceTablet interactive={true} />
            </motion.div>

            {/* Features layout under tablet */}
            <div className="w-full max-w-2xl grid grid-cols-1 md:grid-cols-2 gap-4 text-left pt-6 border-t border-[#E5E5E1] mt-4">
              <div className="flex items-start gap-2.5">
                <div className="w-5 h-5 bg-[#1A1A1A]/5 rounded-full flex items-center justify-center text-[#1A1A1A] shrink-0 mt-0.5">
                  <Check className="w-3 h-3" />
                </div>
                <div>
                  <h4 className="text-xs font-bold text-[#1A1A1A]">{t('s2.qris')}</h4>
                  <p className="text-[11px] text-[#666666] font-medium">{t('s2.qris_desc')}</p>
                </div>
              </div>

              <div className="flex items-start gap-2.5">
                <div className="w-5 h-5 bg-[#1A1A1A]/5 rounded-full flex items-center justify-center text-[#1A1A1A] shrink-0 mt-0.5">
                  <Check className="w-3 h-3" />
                </div>
                <div>
                  <h4 className="text-xs font-bold text-[#1A1A1A]">{t('s2.receipt')}</h4>
                  <p className="text-[11px] text-[#666666] font-medium">{t('s2.receipt_desc')}</p>
                </div>
              </div>
            </div>

            <div className="text-[10px] font-semibold text-[#888888] text-center pt-2">
              {t('s2.hint')}
            </div>

          </motion.div>
        </section>

        {/* ================= SECTION 3: STOCK MANAGEMENT ================= */}
        <section 
          ref={sectionRefs[2]}
          className="min-h-screen w-full flex flex-col justify-center bg-white relative p-6 py-20 md:py-28 overflow-hidden"
          id="section-inventory"
        >
          {/* Subtle slow drift background shape for additional premium parallax depth */}
          <motion.div 
            style={{ y: smoothS3DecorY }}
            className="absolute left-10 top-36 w-44 h-44 rounded-full bg-slate-100 blur-3xl pointer-events-none z-0" 
          />

          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10"
          >
            
            {/* Story copy on Left Grid (5 cols) */}
            <div className="lg:col-span-5 space-y-5 text-left order-2 lg:order-1">
              <span className="text-[10px] font-bold text-[#666666] tracking-wider uppercase block">
                {t('s3.label')}
              </span>
              <h2 className="font-sans font-bold text-3xl text-[#1A1A1A] leading-tight">
                {t('s3.title')}
              </h2>
              <p className="font-sans text-[#555555] text-sm leading-relaxed font-medium">
                {t('s3.desc')}
              </p>

              <div className="space-y-3 bg-[#F4F4F2] p-4 rounded-[6px] border border-[#E5E5E1]">
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="text-xs font-bold text-[#1A1A1A]">Almond Croissant (SKU-002)</h4>
                    <p className="text-[10px] text-red-600 font-semibold">Tinggal 18 pcs di rak</p>
                  </div>
                  <span className="bg-red-50 text-red-700 font-bold text-[9px] px-2 py-0.5 rounded-[4px] uppercase tracking-wide">{t('s3.restock_alert')}</span>
                </div>
                <p className="text-[11px] text-[#666666] leading-relaxed pt-1.5 border-t border-dashed border-[#D1D1CC] font-medium">
                  {t('s3.restock_desc')}
                </p>
              </div>

              <div className="flex items-center gap-3 pt-2">
                <div className="text-center p-3 bg-[#F4F4F2] rounded-[6px] border border-[#E5E5E1] flex-1">
                  <div className="font-mono text-lg font-bold text-[#1A1A1A]">0%</div>
                  <p className="text-[10px] text-[#666666] mt-0.5 font-semibold">{t('s3.zero')}</p>
                </div>
                <div className="text-center p-3 bg-[#F4F4F2] rounded-[6px] border border-[#E5E5E1] flex-1">
                  <div className="font-mono text-lg font-bold text-[#1A1A1A]">1-Klik</div>
                  <p className="text-[10px] text-[#666666] mt-0.5 font-semibold">{t('s3.oneclick')}</p>
                </div>
              </div>
            </div>

            {/* Dashboard Laptop on Right Grid (7 cols) opened on Inventory page with smooth non-clamping parallax */}
            <motion.div 
              style={{ y: smoothS3LaptopY }}
              className="lg:col-span-7 flex justify-center items-center order-1 lg:order-2"
            >
              <DeviceLaptop interactive={true} />
            </motion.div>

          </motion.div>
        </section>

        {/* ================= SECTION 4: MULTI DEVICE EXPERIENCE ================= */}
        <section 
          ref={sectionRefs[3]}
          className="min-h-screen w-full flex flex-col justify-center bg-[#F4F4F2] relative p-6 py-20 md:py-28 overflow-hidden"
          id="section-devices"
        >
          {/* Subtle slow drift background shape for additional premium parallax depth */}
          <motion.div 
            style={{ y: smoothS4DecorY }}
            className="absolute right-10 top-20 w-44 h-44 rounded-full bg-slate-300/10 blur-3xl pointer-events-none z-0" 
          />

          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="max-w-7xl mx-auto w-full text-center space-y-6 relative z-10"
          >
            <div className="max-w-3xl mx-auto space-y-2">
              <span className="text-[10px] font-bold text-[#666666] tracking-wider uppercase block">
                {t('s4.label')}
              </span>
              <h2 className="font-sans font-bold text-2xl sm:text-3xl text-[#1A1A1A] tracking-tight">
                {t('s4.title')}
              </h2>
              <p className="font-sans text-[#555555] text-xs sm:text-sm leading-relaxed font-medium">
                {t('s4.desc')}
              </p>
            </div>

            {/* Central Master Controller Bar - Elegant Horizontal Panel */}
            <div className="max-w-xl mx-auto bg-white border border-[#E5E5E1] p-3 px-5 rounded-[12px] shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3 text-left">
              <div>
                <h4 className="text-[10px] font-bold text-[#1A1A1A] uppercase tracking-wide">{t('s4.simulate')}</h4>
                <p className="text-[9px] text-[#666666] font-medium leading-normal">{t('s4.simulate_desc')}</p>
              </div>

              <div className="flex gap-2 shrink-0">
                <button
                  onClick={() => handleSimulateSync(42000, 'QRIS')}
                  className="text-[10px] font-extrabold bg-[#1A1A1A] text-white px-3.5 py-2 rounded-[6px] hover:bg-[#1A1A1A]/90 transition-all cursor-pointer active:scale-95 shadow-sm"
                >
                  + Rp 42k (QRIS)
                </button>

                <button
                  onClick={() => handleSimulateSync(120000, 'Tuan Cash')}
                  className="text-[10px] font-extrabold border border-[#D1D1CC] bg-white hover:bg-[#F4F4F2] text-[#1A1A1A] px-3.5 py-2 rounded-[6px] transition-all cursor-pointer active:scale-95 shadow-xs"
                >
                  + Rp 120k (Cash)
                </button>
              </div>

              <div className="hidden sm:flex flex-col items-end shrink-0 border-l border-dashed border-[#E5E5E1] pl-4 text-right">
                <span className="text-[8px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100 flex items-center gap-1 animate-pulse uppercase tracking-wider">
                  {t('s4.connected')}
                </span>
                <span className="text-[8px] text-[#888888] font-mono mt-0.5">Sync Count: #{syncCount}</span>
              </div>
            </div>

            {/* Simulated Live Synchronized Devices Showcase */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 items-end justify-center pt-2 max-w-5xl mx-auto">
              
              {/* DEVICE 1: MOBILE SMARTPHONE (Handphone Owner) - Floating Parallax Speed 1 */}
              <motion.div 
                style={{ y: smoothS4Device1Y }}
                className="flex flex-col items-center"
              >
                <span className="text-[10px] font-mono font-bold text-[#666666] uppercase tracking-wider mb-2.5 flex items-center gap-1.5 bg-white border border-[#E5E5E1] px-3 py-1 rounded-full shadow-xs">
                  <SmartphoneIcon className="w-3.5 h-3.5 text-[#1A1A1A]" /> {t('s4.device1')}
                </span>
                <p className="text-[9px] text-[#888888] mb-3 text-center max-w-[240px] font-medium leading-normal">
                  {t('s4.device1_desc')}
                </p>
                <div className="scale-[0.95] origin-bottom animate-fade-in">
                  <DevicePhone lastTransactionTotal={lastSyncTotal} lastPaymentMethod={lastSyncMethod} />
                </div>
              </motion.div>

              {/* DEVICE 2: TABLET POS - Floating Parallax Speed 2 */}
              <motion.div 
                style={{ y: smoothS4Device2Y }}
                className="flex flex-col items-center"
              >
                <span className="text-[10px] font-mono font-bold text-[#666666] uppercase tracking-wider mb-2.5 flex items-center gap-1.5 bg-white border border-[#E5E5E1] px-3 py-1 rounded-full shadow-xs">
                  <Tablet className="w-3.5 h-3.5 text-[#1A1A1A]" /> {t('s4.device2')}
                </span>
                <p className="text-[9px] text-[#888888] mb-3 text-center max-w-[240px] font-medium leading-normal animate-fade-in">
                  {t('s4.device2_desc')}
                </p>
                
                {/* Genuine Landscape Tablet Mockup - iPad style bezel frame */}
                <div className="w-full max-w-[340px] aspect-[4/3] flex items-center justify-center animate-fade-in pb-4">
                  <div className="w-[330px] h-[245px] bg-[#1C1C1E] rounded-[22px] p-3 shadow-2xl border border-gray-800 relative transition-transform hover:scale-[1.02] duration-300">
                    {/* Front Facing Camera lens of tablet (top center in landscape orientation) */}
                    <div className="absolute top-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-slate-900 border border-slate-700 z-30" />
                    
                    {/* Landscape screen glass viewport */}
                    <div className="bg-slate-50 rounded-[12px] h-[210px] p-3 text-gray-800 font-sans text-[9px] flex flex-col justify-between border border-gray-200 shadow-inner overflow-hidden">
                      
                      {/* Title Bar */}
                      <div className="flex justify-between items-center border-b border-[#E5E5E1] pb-1.5 shrink-0">
                        <span className="font-extrabold text-[#1A1A1A] uppercase tracking-wider text-[8px]">Terminal Kasir #01</span>
                        <span className="text-emerald-700 font-extrabold text-[7px] uppercase tracking-widest bg-emerald-50 px-1 rounded border border-emerald-100 flex items-center gap-1">
                          <span className="w-1 h-1 rounded-full bg-emerald-600 animate-pulse" /> Live
                        </span>
                      </div>

                      {/* Cashier cart list */}
                      <div className="py-1.5 space-y-1 my-1 flex-1 overflow-y-auto no-scrollbar font-medium">
                        <div className="flex justify-between font-bold text-gray-900 text-[8px] border-b border-gray-100 pb-0.5">
                          <span>Item Pesanan</span>
                          <span>Banyak x Harga</span>
                        </div>
                        <div className="flex justify-between text-gray-600 text-[8.5px]">
                          <span>Kopi Susu Gula Aren</span>
                          <span>2 Pcs &bull; Rp 48.000</span>
                        </div>
                        <div className="flex justify-between text-gray-600 text-[8.5px]">
                          <span>Almond Croissant</span>
                          <span>1 Pcs &bull; Rp 30.000</span>
                        </div>
                      </div>

                      {/* Live syncing transaction metadata footer */}
                      <div className="border-t border-[#E5E5E1] pt-1.5 flex justify-between items-center text-gray-900 shrink-0">
                        <div>
                          <span className="text-[7px] uppercase text-gray-400 font-bold block">Bayar via</span>
                          <span className="font-extrabold text-amber-600 uppercase text-[8px]">{lastSyncMethod}</span>
                        </div>
                        <div className="text-right">
                          <span className="text-[7.5px] font-bold uppercase text-gray-500 block">Nilai Transaksi</span>
                          <span className="font-mono font-extrabold text-[#1A1A1A] text-[10px]">
                            Rp {lastSyncTotal.toLocaleString('id-ID')}
                          </span>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </motion.div>

              {/* DEVICE 3: PC LAPTOP / BACK-OFFICE (Konsol Stok & Admin) - Floating Parallax Speed 3 */}
              <motion.div 
                style={{ y: smoothS4Device3Y }}
                className="flex flex-col items-center"
              >
                <span className="text-[10px] font-mono font-bold text-[#666666] uppercase tracking-wider mb-2.5 flex items-center gap-1.5 bg-white border border-[#E5E5E1] px-3 py-1 rounded-full shadow-xs">
                  <span className="text-[#1A1A1A]">🖥️</span> {t('s4.device3')}
                </span>
                <p className="text-[9px] text-[#888888] mb-3 text-center max-w-[240px] font-medium leading-normal animate-fade-in">
                  {t('s4.device3_desc')}
                </p>

                {/* Genuine Wide Display PC Laptop Mockup */}
                <div className="w-full max-w-[360px] flex flex-col items-center justify-end animate-fade-in pb-4">
                  
                  {/* Laptop Screen Bezel */}
                  <div className="w-[340px] bg-gradient-to-r from-gray-200 via-gray-100 to-gray-300 rounded-t-[14px] p-2 pb-0 shadow-lg border border-gray-300/85 relative">
                    {/* Web camera dot */}
                    <div className="absolute top-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-slate-900 border border-slate-500 z-40" />
                    
                    {/* Screen Viewport with Terminal theme (16:10 landscape) */}
                    <div className="bg-slate-950 rounded-t-[6px] h-[200px] p-3 text-slate-300 font-sans text-[8.5px] flex flex-col justify-between border border-black/35 shadow-inner">
                      
                      {/* Server Title */}
                      <div className="flex justify-between border-b border-slate-900 pb-1.5 shrink-0">
                        <span className="text-white uppercase font-bold text-[7.5px] flex items-center gap-1">
                          <span className="w-1 h-1 rounded-full bg-emerald-500 animate-pulse" /> MUTASI STOK REALTIME
                        </span>
                        <span className="text-slate-400 font-mono text-[7px]">ZK-SERVER-04</span>
                      </div>

                      {/* Real-time sync logs terminal style */}
                      <div className="space-y-1.5 py-2 flex-1 overflow-y-auto no-scrollbar font-medium leading-relaxed">
                        <div className="flex justify-between text-slate-400">
                          <span>Sinyal Webhook:</span>
                          <span className="font-mono text-emerald-400 font-bold">Sinkronisasi OK</span>
                        </div>
                        <div className="flex justify-between text-slate-400">
                          <span>Bahan Aren Kopi:</span>
                          <span className="font-mono text-white">-2 Pcs (Terjual)</span>
                        </div>
                        <div className="flex justify-between text-slate-400">
                          <span>Sisa Stok Gudang:</span>
                          <span className="font-mono text-amber-400 font-bold">45 Pcs (Aman)</span>
                        </div>
                        <div className="flex justify-between text-red-400 bg-red-950/25 px-1.5 py-0.5 rounded border border-red-500/10">
                          <span>Update Reorder:</span>
                          <span className="font-bold">Chocolate Cookie sisa 4</span>
                        </div>
                      </div>

                      {/* Metadata footer */}
                      <div className="border-t border-slate-900 pt-1.5 flex justify-between items-center text-slate-500 text-[6.5px] shrink-0">
                        <span>Firestore Cloud</span>
                        <span className="font-mono text-slate-300">Total Sync: #{syncCount}</span>
                      </div>

                    </div>
                  </div>

                  {/* Metallic Laptop Keyboard Base */}
                  <div className="relative w-[348px] h-3 bg-[#E0E0E0] rounded-b-[10px] border-t border-white shadow-xl flex justify-center items-start z-20 shrink-0">
                    {/* Trackpad */}
                    <div className="w-20 h-[6px] bg-[#CCCCCC] border border-gray-400/50 rounded-b-[3px] -mt-[0.5px] border-t-0 shadow-inner" />
                  </div>

                </div>
              </motion.div>

            </div>
          </motion.div>
        </section>

        {/* ================= SECTION 5: BUSINESS ANALYTICS ================= */}
        <section 
          ref={sectionRefs[4]}
          className="min-h-screen w-full flex flex-col justify-center bg-white relative p-6 py-20 md:py-28 overflow-hidden"
          id="section-analytics"
        >
          {/* Subtle slow drift background shape for additional premium parallax depth */}
          <motion.div 
            style={{ y: smoothS5DecorY }}
            className="absolute left-12 top-24 w-40 h-40 rounded-full bg-slate-150/40 blur-3xl pointer-events-none z-0" 
          />

          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-center relative z-10"
          >
            
            {/* Expanded Dashboard on Left Grid (7 cols) with smooth non-clamping parallax */}
            <motion.div 
              style={{ y: smoothS5LaptopY }}
              className="lg:col-span-7 flex justify-center items-center"
            >
              <DeviceLaptop interactive={true} />
            </motion.div>

            {/* Story copy on Right Grid (5 cols) */}
            <div className="lg:col-span-5 space-y-5 lg:pl-6 text-left">
              <span className="text-[10px] font-bold text-[#666666] tracking-wider uppercase block">
                {t('s5.label')}
              </span>
              <h2 className="font-sans font-bold text-3xl text-[#1A1A1A] leading-tight">
                {t('s5.title')}
              </h2>
              <p className="font-sans text-[#555555] text-sm leading-relaxed font-medium">
                {t('s5.desc')}
              </p>

              <div className="grid grid-cols-2 gap-3 pt-1">
                <div className="border border-[#E5E5E1] p-4 rounded-[6px] bg-[#F4F4F2]/50 text-left">
                  <h4 className="text-xs font-bold text-[#1A1A1A]">{t('s5.hourly')}</h4>
                  <p className="text-[10px] text-[#666666] mt-1 leading-normal font-medium">{t('s5.hourly_desc')}</p>
                </div>
                
                <div className="border border-[#E5E5E1] p-4 rounded-[6px] bg-[#F4F4F2]/50 text-left">
                  <h4 className="text-xs font-bold text-[#1A1A1A]">{t('s5.bestseller')}</h4>
                  <p className="text-[10px] text-[#666666] mt-1 leading-normal font-medium">{t('s5.bestseller_desc')}</p>
                </div>
              </div>

              <div className="flex items-center gap-3 pt-3">
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-4.5 h-4.5 text-[#1A1A1A]" />
                  <span className="text-xs font-bold text-[#555555]">{t('s5.export')}</span>
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-4.5 h-4.5 text-[#1A1A1A]" />
                  <span className="text-xs font-bold text-[#555555]">{t('s5.multi')}</span>
                </div>
              </div>
            </div>

          </motion.div>
        </section>

        {/* ================= SECTION 6: BUSINESS OWNER STORY ================= */}
        <section 
          ref={sectionRefs[5]}
          className="min-h-screen w-full flex flex-col justify-center bg-[#F4F4F2] relative p-6 py-20 md:py-28 overflow-hidden"
          id="section-testimonials"
        >
          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-center bg-white rounded-[6px] border border-[#E5E5E1] p-8 shadow-sm"
          >
            
            {/* Real edited photography on Left Grid (5 cols) with no illustration constraint with smooth 3D parallax depth */}
            <div className="lg:col-span-5 flex justify-center relative py-6">
              <motion.div 
                style={{ y: smoothS6ImageY }}
                className="w-full max-w-[340px] aspect-[4/3] rounded-[6px] overflow-hidden shadow-md border border-[#E5E5E1] z-10 relative"
              >
                <img 
                  src={TESTIMONIALS[activeTestimonial].imagePath} 
                  alt={TESTIMONIALS[activeTestimonial].name} 
                  className="w-full h-full object-cover grayscale-subtle hover:grayscale-0 transition-all duration-500"
                  referrerPolicy="no-referrer"
                />
              </motion.div>
              {/* Visual geometric shadow card */}
              <motion.div 
                style={{ y: smoothS6ShadowY }}
                className="absolute inset-x-0 bottom-3 top-9 bg-[#F4F4F2] rounded-[6px] z-0 w-full max-w-[340px] mx-auto border border-[#D1D1CC]" 
              />
            </div>

            {/* Testimonial Copy on Right Grid (7 cols) */}
            <div className="lg:col-span-7 flex flex-col justify-between h-full space-y-6 text-left">
              <div className="space-y-4">
                <span className="text-[10px] font-bold text-[#666666] tracking-wider uppercase block">
                  {t('s6.label')}
                </span>
                
                <h3 className="font-sans font-bold text-2xl sm:text-3xl text-[#1A1A1A] leading-tight">
                  &ldquo;Stok otomatis kami selalu ter-update secara presisi setiap malam.&rdquo;
                </h3>

                <Quote className="w-10 h-10 text-gray-200" />
                
                <p className="font-sans text-[#555555] text-sm italic leading-relaxed max-w-xl font-medium">
                  {TESTIMONIALS[activeTestimonial].quote}
                </p>
              </div>

              {/* Owner details and slider controls */}
              <div className="pt-6 border-t border-[#E5E5E1] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                  <h4 className="font-bold text-xs text-[#1A1A1A] font-sans">
                    {TESTIMONIALS[activeTestimonial].name}
                  </h4>
                  <p className="text-[10px] text-[#666666] font-medium">
                    {TESTIMONIALS[activeTestimonial].role} &bull; {TESTIMONIALS[activeTestimonial].businessName} ({TESTIMONIALS[activeTestimonial].businessType})
                  </p>
                  <p className="text-[9px] text-[#888888] italic font-medium">
                    {TESTIMONIALS[activeTestimonial].location}
                  </p>
                </div>

                {/* Slider switches */}
                <div className="flex gap-2">
                  {TESTIMONIALS.map((t, idx) => (
                    <button
                      key={t.id}
                      onClick={() => setActiveTestimonial(idx)}
                      className={`text-[11px] font-bold px-4 py-2 rounded-[6px] border transition-all cursor-pointer active:scale-95 ${
                        activeTestimonial === idx 
                          ? 'bg-[#1A1A1A] text-white border-[#1A1A1A] font-bold' 
                          : 'bg-white text-[#666666] hover:text-[#1A1A1A] border-[#D1D1CC]'
                      }`}
                    >
                      {t.businessName.split(' ')[1] || t.businessName.split(' ')[0]}
                    </button>
                  ))}
                </div>
              </div>

            </div>

          </motion.div>
        </section>

        {/* ================= SECTION 6B: FAQ ACCORDION ================= */}
        <section 
          ref={sectionRefs[6]}
          className="min-h-screen w-full flex flex-col justify-center bg-white relative p-6 py-20 md:py-28 overflow-hidden border-t border-[#E5E5E1]"
          id="section-faq"
        >
          {/* Subtle slow drift background shape */}
          <motion.div 
            style={{ y: smoothS_faqDecorY }}
            className="absolute right-10 top-20 w-44 h-44 rounded-full bg-[#1A1A1A]/5 blur-3xl pointer-events-none z-0" 
          />
          <motion.div 
            style={{ y: smoothS_faqDecorY }}
            className="absolute left-10 bottom-10 w-52 h-52 rounded-full bg-slate-300/20 blur-3xl pointer-events-none z-0" 
          />

          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="max-w-4xl mx-auto w-full space-y-12 relative z-10"
          >
            <div className="text-center space-y-2">
              <span className="text-[10px] font-bold text-[#666666] tracking-wider uppercase block">
                SEKSI 06B • PERTANYAAN UMUM
              </span>
              <h2 className="font-sans font-bold text-3xl text-[#1A1A1A] tracking-tight leading-tight">
                Pertanyaan yang Sering Diajukan
              </h2>
              <p className="font-sans text-[#555555] text-sm leading-relaxed max-w-xl mx-auto font-medium">
                Temukan jawaban lengkap seputar keunggulan, fungsionalitas, keamanan data, dan kemudahan dalam menggunakan ZonaKasir POS.
              </p>
            </div>

            {/* Accordion List wrapper */}
            <div className="space-y-4 max-w-3xl mx-auto">
              {[
                {
                  q: "Apakah ZonaKasir POS bisa digunakan tanpa koneksi internet (offline)?",
                  a: "Ya! ZonaKasir POS dirancang dengan teknologi Offline-First. Semua transaksi penjualan tetap dapat diproses tanpa gangguan sekalipun internet Anda terputus atau tidak stabil. Data transaksi akan disimpan sementara secara lokal dan tersinkronisasi otomatis ke cloud seketika koneksi terhubung kembali."
                },
                {
                  q: "Bagaimana sistem sinkronisasi multi-device ZonaKasir bekerja?",
                  a: "Kami menggunakan mesin sinkronisasi real-time terenkripsi. Ketika kasir melakukan transaksi di perangkat handphone atau tablet POS, data stok barang, laporan terjual, dan status tagihan langsung terbarui dalam hitungan milidetik secara paralel di semua perangkat admin atau laptop back-office yang terhubung."
                },
                {
                  q: "Apakah ada biaya tersembunyi selain biaya paket bulanan/tahunan?",
                  a: "Sama sekali tidak ada. Semua fitur utama yang tercantum pada Paket Pilihan Anda sudah mencakup fungsionalitas penuh tanpa ada batasan tersembunyi. Khusus untuk Paket Pro dan Enterprise, Anda bisa menggunakan transaksi, input produk, dan kasir tanpa batasan (unlimited)."
                },
                {
                  q: "Perangkat (hardware) apa saja yang didukung oleh ZonaKasir?",
                  a: "ZonaKasir POS sangat fleksibel dan dapat diakses melalui berbagai sistem operasi: Android, iOS, tablet iPad, macOS, serta PC/Laptop Windows melalui browser web dasar yang modern. Selain itu, sistem kami mendukung integrasi berbagai merk printer struk bluetooth/thermal, barcode scanner, dan laci uang (cash drawer) eksternal."
                },
                {
                  q: "Apakah data transaksi saya aman dan bagaimana jaminan pencadangan (backup) datanya?",
                  a: "Keamanan data Anda adalah prioritas utama kami. ZonaKasir menyimpan seluruh basis data pada server cloud dengan enkripsi end-to-end standar industri. Kami melakukan pencadangan (backup) otomatis secara berkala setiap hari guna memastikan data usaha Anda tetap aman dari risiko kehilangan akibat kerusakan hardware fisik."
                },
                {
                  q: "Bagaimana jika saya memerlukan bantuan teknis saat menggunakan aplikasi?",
                  a: "Untuk pengguna Paket Pro dan Enterprise, kami menyediakan dukungan prioritas harian secara langsung melalui WhatsApp Customer Success. Khusus paket Enterprise, tim kami dapat menyediakan pendampingan setup mandiri serta on-site training gratis untuk staf kasir Anda di lokasi."
                }
              ].map((item, index) => {
                const isOpen = activeFaq === index;
                return (
                  <div 
                    key={index} 
                    className="bg-white rounded-[6px] border border-[#E5E5E1] overflow-hidden transition-all duration-300 shadow-xs hover:border-[#CCCCCC]"
                    id={`faq-item-${index}`}
                  >
                    <button
                      onClick={() => setActiveFaq(isOpen ? null : index)}
                      className="w-full text-left p-5 flex justify-between items-center gap-4 cursor-pointer select-none group"
                    >
                      <span className="font-sans font-bold text-sm text-[#1A1A1A] group-hover:text-gray-600 transition-colors">
                        {item.q}
                      </span>
                      <span className="shrink-0 w-6 h-6 rounded-full bg-[#F4F4F2] flex items-center justify-center transition-transform duration-300">
                        <motion.svg 
                          className="w-3 h-3 text-[#1E1E1E]" 
                          fill="none" 
                          viewBox="0 0 24 24" 
                          stroke="currentColor" 
                          strokeWidth="3"
                          animate={{ rotate: isOpen ? 180 : 0 }}
                          transition={{ duration: 0.25 }}
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                        </motion.svg>
                      </span>
                    </button>
                    
                    <motion.div
                      initial={false}
                      animate={{ height: isOpen ? "auto" : 0, opacity: isOpen ? 1 : 0 }}
                      transition={{ duration: 0.3, ease: "easeInOut" }}
                      className="overflow-hidden"
                    >
                      <div className="p-5 pt-0 border-t border-gray-100/60 font-sans text-xs text-[#555555] leading-relaxed font-medium bg-[#FAFAFA]/50">
                        {item.a}
                      </div>
                    </motion.div>
                  </div>
                );
              })}
            </div>

            {/* Beautiful CTA banner at the end of FAQ */}
            <div className="mt-12 text-center border-t border-gray-100 pt-10 max-w-xl mx-auto space-y-4">
              <p className="font-sans text-xs text-[#555555] font-medium leading-relaxed">
                Belum menemukan jawaban yang Anda cari? Silakan tanyakan langsung permasalahan Anda kepada tim Customer Support kami.
              </p>
              <div>
                <button
                  onClick={() => setActiveModal('consultation')}
                  className="inline-flex items-center gap-2 bg-gray-900 text-white font-sans text-xs font-bold uppercase tracking-wider px-6 py-3 rounded-[4px] shadow-sm hover:bg-gray-800 transition-all cursor-pointer hover:scale-[1.02] active:scale-95"
                  id="faq-ask-admin-btn"
                >
                  <MessageCircle className="w-4 h-4 text-white" />
                  Tanya Admin
                </button>
              </div>
            </div>
          </motion.div>
        </section>

        {/* ================= SECTION 7: SOCIAL PROOF ================= */}
        <section 
          ref={sectionRefs[7]}
          className="min-h-screen w-full flex flex-col justify-center bg-white relative p-6 py-20 md:py-28 overflow-hidden"
          id="section-social"
        >
          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-center"
          >
            
            {/* Visual shelf / storefront image and stats grid left (6 cols) with dual-speed scroll */}
            <div className="lg:col-span-6 grid grid-cols-2 gap-3.5 relative">
              <motion.div 
                style={{ y: smoothS7StorefrontY }}
                className="col-span-2 aspect-[16/9] rounded-[6px] overflow-hidden border border-[#E5E5E1] shadow-md z-10"
              >
                <img 
                  src="/images/landing/storefront_shelf_1781378993505.jpg" 
                  alt="Modern Boutique Store Platform" 
                  className="w-full h-full object-cover filter contrast-105 transition-all hover:scale-102 duration-500"
                  referrerPolicy="no-referrer"
                />
              </motion.div>

              <motion.div 
                style={{ y: smoothS7StatsY }}
                className="bg-[#F4F4F2] p-4 border border-[#E5E5E1] rounded-[6px] text-left z-20"
              >
                <h4 className="font-mono text-xl font-extrabold text-[#1A1A1A]">1.240+</h4>
                <p className="text-[10px] font-bold text-[#666666] uppercase tracking-wide mt-0.5">Merchant Terdaftar</p>
                <p className="text-[10px] text-[#555555] font-medium mt-1 leading-normal">Cafe, F&B outlet, dan fashion butik di seluruh Indonesia.</p>
              </motion.div>

              <motion.div 
                style={{ y: smoothS7StatsY }}
                className="bg-[#F4F4F2] p-4 border border-[#E5E5E1] rounded-[6px] text-left z-20"
              >
                <h4 className="font-mono text-xl font-extrabold text-[#1A1A1A]">Rp 12,4M+</h4>
                <p className="text-[10px] font-bold text-[#666666] uppercase tracking-wide mt-0.5">Total GTV Diproses</p>
                <p className="text-[10px] text-[#555555] font-medium mt-1 leading-normal">Transaksi aman terjaga melalui server terenkripsi cloud.</p>
              </motion.div>
            </div>

            {/* Authentic values statement on right (6 cols) */}
            <div className="lg:col-span-6 space-y-6 lg:pl-6 text-left">
              <span className="text-[10px] font-bold text-[#666666] tracking-wider uppercase block">
                SEKSI 07 • KEAMANAN & SKALABILITAS
              </span>
              
              <h2 className="font-sans font-bold text-3xl text-[#1A1A1A] leading-tight">
                Dipercaya oleh Ribuan Pelaku Bisnis di Nusantara.
              </h2>
              
              <p className="font-sans text-[#555555] text-sm leading-relaxed font-medium">
                Dari warung kopi sudut jalan hingga butik pakaian premium, ZonaKasir melayani operasional harian merchant dengan kestabilan uptime server 99.9%, pengamanan ganda SSL, dan backup cloud kontinu otomatis.
              </p>

              <div className="grid grid-cols-2 gap-4 pt-1">
                <div className="space-y-1.5">
                  <div className="flex items-center gap-1.5 text-[#1A1A1A]">
                    <span className="w-2 h-2 rounded-full bg-emerald-500" />
                    <h4 className="text-xs font-bold uppercase tracking-wide">Uptime Sempurna 99.99%</h4>
                  </div>
                  <p className="text-[11px] text-[#666666] font-medium leading-normal animate-fade-in">Arsitektur multi-node cloud run memastikan kasir responsif di jam sibuk.</p>
                </div>

                <div className="space-y-1.5">
                  <div className="flex items-center gap-1.5 text-[#1A1A1A]">
                    <span className="w-2 h-2 rounded-full bg-emerald-500" />
                    <h4 className="text-xs font-bold uppercase tracking-wide">Standard keamanan data</h4>
                  </div>
                  <p className="text-[11px] text-[#666666] font-medium leading-normal">Sertifikasi pengamanan SSL aktif dan enkripsi transaksi m-Banking.</p>
                </div>
              </div>

              <div className="pt-3 border-t border-[#E5E5E1] flex items-center justify-between text-[11px] text-[#666666] font-medium">
                <span>Tersertifikasi Kominfo & Bank Indonesia QR Sandbox</span>
                <span className="font-bold text-[#1A1A1A] bg-[#1A1A1A]/5 px-2.5 py-0.5 rounded-[4px] border border-[#E5E5E1] text-[9px] uppercase tracking-wider">Terverifikasi</span>
              </div>
            </div>

          </motion.div>
        </section>

        {/* ================= SECTION 8: KATALOG PAKET BERLANGGANAN ================= */}
        <section 
          ref={sectionRefs[8]}
          className="min-h-screen w-full flex flex-col justify-center bg-[#F4F4F2] relative p-6 py-20 md:py-28 overflow-hidden border-t border-[#E5E5E1]"
          id="section-pricing"
        >
          {/* Subtle slow drift background shape for additional premium parallax depth */}
          <motion.div 
            style={{ y: smoothS8PricingDecorY }}
            className="absolute left-10 top-20 w-44 h-44 rounded-full bg-slate-300/30 blur-3xl pointer-events-none z-0" 
          />
          <motion.div 
            style={{ y: smoothS8PricingDecorY }}
            className="absolute right-10 bottom-10 w-52 h-52 rounded-full bg-slate-400/10 blur-3xl pointer-events-none z-0" 
          />

          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            className="max-w-7xl mx-auto w-full text-center space-y-6 relative z-10"
          >
            <div className="max-w-3xl mx-auto space-y-2">
              <span className="text-[10px] font-bold text-[#666666] tracking-wider uppercase block">
                SEKSI 08 • KATALOG PAKET BERLANGGANAN
              </span>
              <h2 className="font-sans font-bold text-3xl text-[#1A1A1A] tracking-tight leading-tight">
                Pilih Paket Sesuai Skala Bisnis Anda.
              </h2>
              <p className="font-sans text-[#555555] text-sm leading-relaxed max-w-xl mx-auto font-medium">
                Didesain khusus untuk menyokong efisiensi operasional UMKM retail mandiri hingga franchise besar multi-cabang di Nusantara.
              </p>
            </div>

            {/* Premium Billing Cycle Selector Slider */}
            <div className="flex justify-center items-center gap-3.5 pt-2 pb-6 z-10 relative">
              <span className={`text-[11.5px] font-bold uppercase tracking-wider transition-colors duration-200 ${billingCycle === 'monthly' ? 'text-[#1A1A1A]' : 'text-[#888888]'}`}>Bulanan</span>
              <button 
                onClick={() => setBillingCycle(billingCycle === 'monthly' ? 'annually' : 'monthly')}
                className="w-12 h-6.5 rounded-full bg-slate-300/80 relative transition-colors duration-300 p-0.5 flex items-center cursor-pointer"
                id="billing-cycle-toggle"
              >
                <motion.div 
                  className="w-5.5 h-5.5 rounded-full bg-white shadow-md"
                  animate={{ x: billingCycle === 'monthly' ? 0 : 25 }}
                  transition={{ type: "spring", stiffness: 250, damping: 22 }}
                />
              </button>
              <span className={`text-[11.5px] font-bold uppercase tracking-wider transition-colors duration-200 flex items-center gap-1.5 ${billingCycle === 'annually' ? 'text-[#1A1A1A]' : 'text-[#888888]'}`}>
                Tahunan 
                <span className="bg-emerald-500 text-white text-[9px] font-mono font-bold px-1.5 py-0.5 rounded-[4px] tracking-normal normal-case">Hemat 20%</span>
              </span>
            </div>

            {/* 3 Columns Subscriber Pricing Grid with subtle speed adjustment */}
            <motion.div 
              style={{ y: smoothS8PricingFloatY }}
              className="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto text-left"
            >
              
              {/* PLAN 1: LITE */}
              <div 
                className="bg-white rounded-[6px] border border-[#E5E5E1] p-6 shadow-sm flex flex-col justify-between relative overflow-hidden group min-h-[460px]"
                id="plan-lite"
              >
                <div>
                  <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">UMKM Pemula</span>
                  <h3 className="font-sans font-bold text-lg text-[#1A1A1A]">Paket Lite</h3>
                  <p className="text-[11px] text-[#666666] leading-relaxed mt-1 font-medium">Sempurna untuk kios kecil, warung kopi sudut, dan pedagang mandiri.</p>
                  
                  <div className="py-5 border-y border-gray-100 my-5 space-y-1">
                    <span className="font-mono text-3xl font-black text-[#1A1A1A]">Rp 0</span>
                    <span className="text-[10px] text-[#666666] font-bold block uppercase tracking-wider">Gratis Selamanya</span>
                  </div>

                  <span className="text-[10px] font-bold text-[#1A1A1A] uppercase tracking-wide block mb-3.5">Fasilitas Utama:</span>
                  <ul className="space-y-2.5 text-[11px] text-[#555555] font-medium">
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>1 Outlet / Lokasi Toko</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>1 Akun Kasir Aktif</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Manajemen Transaksi Dasar</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Cetak Struk dan Nota Digital</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Laporan Email Sederhana</span>
                    </li>
                  </ul>
                </div>

                <div className="pt-6">
                  <button 
                    onClick={() => setActiveModal('register')}
                    className="w-full bg-[#F4F4F2] text-[#1A1A1A] border border-[#E5E5E1] text-[11px] font-bold uppercase tracking-wider py-3 rounded-[4px] hover:bg-slate-200/50 transition-colors cursor-pointer text-center"
                  >
                    Daftar Gratis
                  </button>
                </div>
              </div>

              {/* PLAN 2: PRO (BEST VALUE HIGHLIGHTED) */}
              <div 
                className="bg-white rounded-[6px] border-2 border-[#1A1A1A] p-6 shadow-md flex flex-col justify-between relative overflow-hidden group min-h-[460px]"
                id="plan-pro"
              >
                {/* Popular Corner Tag */}
                <div className="absolute top-0 right-0 bg-[#1A1A1A] text-white text-[8px] font-mono font-bold uppercase tracking-widest px-3.5 py-1.5 rounded-bl-[4px]">
                  Terpopuler
                </div>

                <div>
                  <span className="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Rekomendasi Utama</span>
                  <h3 className="font-sans font-bold text-lg text-[#1A1A1A] flex items-center gap-1.5">
                    Paket Pro 
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse" />
                  </h3>
                  <p className="text-[11px] text-[#666666] leading-relaxed mt-1 font-medium">Cocok untuk kafe, resto, outlet retail busana, dan usaha mandiri berkembang.</p>
                  
                  <div className="py-5 border-y border-gray-100 my-5 space-y-1">
                    <span className="font-mono text-3xl font-black text-[#1A1A1A]">
                      {billingCycle === 'monthly' ? 'Rp 149.000' : 'Rp 119.000'}
                    </span>
                    <span className="text-[10px] text-[#666666] font-bold block uppercase tracking-wider">Per Outlet / Bulan, Ditagih {billingCycle === 'monthly' ? 'Bulanan' : 'Tahunan'}</span>
                  </div>

                  <span className="text-[10px] font-bold text-[#1A1A1A] uppercase tracking-wide block mb-3.5">Semua Fitur Lite, Ditambah:</span>
                  <ul className="space-y-2.5 text-[11px] text-[#555555] font-medium">
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span className="font-bold text-[#1A1A1A]">Transaksi & Kasir Tanpa Batas</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Sinkronisasi Stok Gudang (Real-Time)</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Multi-Device Autentik & Mode Offline</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Analitik Pendapatan & Ekspor Laporan</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Integrasi Printer, Scanner & Cashdrawer</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>WhatsApp Priority support harian</span>
                    </li>
                  </ul>
                </div>

                <div className="pt-6">
                  <button 
                    onClick={() => setActiveModal('register')}
                    className="w-full bg-[#1A1A1A] text-white hover:bg-black text-[11px] font-bold uppercase tracking-wider py-3.5 rounded-[4px] transition-colors cursor-pointer text-center shadow-xs"
                  >
                    Coba Pro Gratis (30 Hari)
                  </button>
                </div>
              </div>

              {/* PLAN 3: ENTERPRISE */}
              <div 
                className="bg-white rounded-[6px] border border-[#E5E5E1] p-6 shadow-sm flex flex-col justify-between relative overflow-hidden group min-h-[460px]"
                id="plan-enterprise"
              >
                <div>
                  <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Skala Besar & Franchise</span>
                  <h3 className="font-sans font-bold text-lg text-[#1A1A1A]">Enterprise</h3>
                  <p className="text-[11px] text-[#666666] leading-relaxed mt-1 font-medium">Dirancang untuk rantai toko waralaba, bisnis distribusi, & multi-cabang terpusat.</p>
                  
                  <div className="py-5 border-y border-gray-100 my-5 space-y-1">
                    <span className="font-mono text-3xl font-black text-[#1A1A1A]">
                      {billingCycle === 'monthly' ? 'Rp 299.000' : 'Rp 239.000'}
                    </span>
                    <span className="text-[10px] text-[#666666] font-bold block uppercase tracking-wider">Per Outlet / Bulan, Ditagih {billingCycle === 'monthly' ? 'Bulanan' : 'Tahunan'}</span>
                  </div>

                  <span className="text-[10px] font-bold text-[#1A1A1A] uppercase tracking-wide block mb-3.5">Semua Fitur Pro, Ditambah:</span>
                  <ul className="space-y-2.5 text-[11px] text-[#555555] font-medium">
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Konsolidasi Gudang Pusat Terpadu</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Manajemen Akses Karyawan Khusus</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Integrasi API Kustom & Sistem ERP</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>SLA Uptime Garansi 99.9%</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span>Dedicated Account Manager Khusus</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <span className="w-4 h-4 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                        <Check className="w-2.5 h-2.5 text-emerald-600" />
                      </span>
                      <span className="font-bold text-[#1A1A1A]">Setup Mandiri & Training On-Site</span>
                    </li>
                  </ul>
                </div>

                <div className="pt-6">
                  <button 
                    onClick={() => setActiveModal('demo')}
                    className="w-full bg-[#1A1A1A]/5 text-[#1A1A1A] border border-[#E5E5E1] text-[11px] font-bold uppercase tracking-wider py-3 rounded-[4px] hover:bg-slate-200/50 transition-colors cursor-pointer text-center"
                  >
                    Hubungi Sales
                  </button>
                </div>
              </div>

            </motion.div>
          </motion.div>
        </section>

        {/* ================= SECTION 9: FINAL CTA ================= */}
        <section 
          ref={sectionRefs[9]}
          className="min-h-screen w-full flex flex-col justify-between bg-[#1A1A1A] text-white relative py-20 md:py-28 overflow-hidden"
          id="section-cta"
        >
          {/* Subtle overlay lines */}
          <div className="absolute inset-0 z-0 bg-grid-lines opacity-5 pointer-events-none" />

          {/* Central CTA content container with smooth target-relative parallax */}
          <motion.div 
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-100px" }}
            transition={{ duration: 0.8, ease: "easeOut" }}
            style={{ y: smoothS9ContentY }}
            className="max-w-4xl mx-auto text-center px-6 py-12 space-y-7 relative z-10 self-center"
          >
            
            <span className="inline-flex items-center gap-1.5 text-[9px] font-mono font-bold text-white tracking-wider uppercase bg-white/10 border border-white/20 px-3 py-1 rounded-[4px]">
              <CheckCircle className="w-3.5 h-3.5 text-white" /> No credit card required &bull; Free setup
            </span>

            <h2 className="font-sans font-bold text-4xl sm:text-5xl text-white tracking-tight leading-tight">
              Mulai Gunakan<br />
              ZonaKasir Hari Ini.
            </h2>

            <p className="font-sans text-gray-300 text-sm max-w-xl mx-auto leading-relaxed font-normal">
              Bergabunglah dengan ribuan pemilik usaha yang telah meningkatkan omset dan melancarkan stok gudangnya bersama kami. Uji coba gratis 30 hari penuh.
            </p>

            <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3.5 pt-2 max-w-md mx-auto">
              <button 
                onClick={() => setActiveModal('register')}
                className="bg-white hover:bg-[#F4F4F2] text-[#1A1A1A] font-sans text-xs font-bold px-7 py-4 rounded-[6px] shadow-md active:scale-95 cursor-pointer flex items-center justify-center gap-1.5"
                id="cta-register"
              >
                {t('cta.start_free')}
                <ArrowRight className="w-4 h-4 text-[#1A1A1A]" />
              </button>
              
              <button 
                onClick={() => setActiveModal('demo')}
                className="bg-transparent text-white font-sans text-xs font-bold px-7 py-4 rounded-[6px] hover:bg-white/10 transition-all cursor-pointer flex items-center justify-center gap-1.5 border border-white/20 active:scale-95"
                id="cta-demo"
              >
                Jadwalkan Demo Privat
              </button>
            </div>

            {/* Small fine print */}
            <p className="text-[10px] text-gray-400 font-medium">
              {t('cta.no_credit')}
            </p>
          </motion.div>

          {/* Simple minimalist compliant footer */}
          <footer className="w-full bg-[#121214] border-t border-white/5 py-6 text-center text-[10px] text-gray-400 relative z-10 px-6">
            <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
              <div className="flex items-center gap-2">
                <span className="font-sans font-bold text-xs text-white">ZonaKasir<span className="text-white">.</span></span>
                <span>&bull; Copyright &copy; 2026 PT Zona Teknologi Nusantara.</span>
              </div>
              <div className="flex gap-4 font-medium">
                <a href="#privacy" className="hover:text-white">Kebijakan Privasi</a>
                <a href="#tos" className="hover:text-white">Ketentuan Layanan</a>
                <a href="#help" className="hover:text-white">Bantuan Kasir</a>
              </div>
            </div>
          </footer>

        </section>

      </div>

      {/* REACTIVE MODAL WINDOWS FOR STYLIZED BUSINESS REGISTER / DEMO INTERFACES */}
      {activeModal !== 'none' && (
        <div className="fixed inset-0 bg-black/75 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fade-in">
          <div className="bg-white rounded-[12px] w-full max-w-md overflow-hidden shadow-2xl border border-gray-100 p-6 relative animate-scale-up">
            
            {/* Close trigger button */}
            <button 
              onClick={() => setActiveModal('none')}
              className="absolute top-4 right-4 w-6 h-6 text-gray-400 hover:text-gray-900 rounded-full hover:bg-gray-100 flex items-center justify-center text-sm font-bold cursor-pointer"
            >
              ×
            </button>

            {activeModal === 'register' && (
              <div className="space-y-4 text-left">
                <div className="w-10 h-10 bg-gray-900 text-white rounded-[6px] flex items-center justify-center font-black text-sm shadow">ZK</div>
                <div>
                  <h3 className="font-sans font-bold text-base text-gray-950">Mulai Uji Coba Gratis Anda</h3>
                  <p className="text-xs text-gray-500 mt-1">Lengkapi data usaha di bawah ini untuk mengaktifkan lisensi demo 30 hari penuh.</p>
                </div>

                <form onSubmit={(e) => { e.preventDefault(); setSuccessSource('register'); setActiveModal('success'); }} className="space-y-3.5 pt-1">
                  <div>
                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Nama Pemilik Usaha</label>
                    <input required type="text" placeholder="e.g. Budi Siswanto" className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900" />
                  </div>
                  <div>
                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Nama Toko / Bisnis</label>
                    <input required type="text" placeholder="e.g. Kopi Khas Senopati" className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900" />
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Kategori Usaha</label>
                      <select className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900">
                        <option>Café & F&B Outlets</option>
                        <option>Fashion Boutique</option>
                        <option>Grocery & Minimarket</option>
                        <option>Jasa / Service</option>
                      </select>
                    </div>
                    <div>
                      <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">No. Handphone (WA)</label>
                      <input required type="tel" placeholder="e.g. 0812XXXXXXXX" className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900" />
                    </div>
                  </div>

                  <button 
                    type="submit" 
                    className="w-full bg-gray-900 text-white font-sans text-xs font-bold py-3 rounded-[6px] hover:bg-gray-800 transition-all cursor-pointer shadow-md flex items-center justify-center gap-1.5"
                  >
                    Daftarkan Merchant Saya
                    <ArrowRight className="w-4 h-4 text-white" />
                  </button>
                </form>
              </div>
            )}

            {activeModal === 'demo' && (
              <div className="space-y-4 text-left">
                <div className="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-[6px] flex items-center justify-center font-black text-sm shadow-sm">📅</div>
                <div>
                  <h3 className="font-sans font-bold text-base text-gray-950">Jadwalkan Demo Privat</h3>
                  <p className="text-xs text-gray-500 mt-1">Konsultan bisnis kami akan menghubungi Anda via WhatsApp untuk menjadwalkan demo sistem interaktif kasir.</p>
                </div>

                <form onSubmit={(e) => { e.preventDefault(); setSuccessSource('demo'); setActiveModal('success'); }} className="space-y-3.5 pt-1">
                  <div>
                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Nama Lengkap</label>
                    <input required type="text" placeholder="e.g. Ibu Andini Sari" className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900" />
                  </div>
                  <div>
                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">No. WhatsApp Aktif</label>
                    <input required type="tel" placeholder="e.g. 081398765432" className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900" />
                  </div>
                  <div>
                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Waktu Demo yang Diinginkan</label>
                    <select className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900">
                      <option>Pagi Hari (09.00 - 12.00 WIB)</option>
                      <option>Siang Hari (13.00 - 15.00 WIB)</option>
                      <option>Sore Hari (15.30 - 17.30 WIB)</option>
                    </select>
                  </div>

                  <button 
                    type="submit" 
                    className="w-full bg-emerald-600 text-white font-sans text-xs font-bold py-3 rounded-[6px] hover:bg-emerald-700 transition-all cursor-pointer shadow-md flex items-center justify-center gap-1.5"
                  >
                    Kirim Jadwal Pertemuan
                  </button>
                </form>
              </div>
            )}

            {activeModal === 'consultation' && (
              <div className="space-y-4 text-left">
                <div className="w-10 h-10 bg-gray-900 text-white rounded-[6px] flex items-center justify-center font-black text-sm shadow">ZK</div>
                <div>
                  <h3 className="font-sans font-bold text-base text-gray-950">Tanya Admin / Konsultasi</h3>
                  <p className="text-xs text-gray-500 mt-1">
                    Silakan isi pertanyaan Anda di bawah ini. Draft email pertanyaan Anda akan otomatis dibuat untuk dikirim ke tim admin ZonaKasir.
                  </p>
                </div>

                <form 
                  onSubmit={(e) => {
                    e.preventDefault();
                    const form = e.currentTarget;
                    const nameInput = form.elements.namedItem('consName') as HTMLInputElement;
                    const emailInput = form.elements.namedItem('consEmail') as HTMLInputElement;
                    const messageInput = form.elements.namedItem('consMessage') as HTMLTextAreaElement;
                    
                    const name = nameInput ? nameInput.value : '';
                    const email = emailInput ? emailInput.value : '';
                    const msg = messageInput ? messageInput.value : '';

                    const mailtoUrl = `mailto:support@zonakasir.id?subject=Pertanyaan%20ZonaKasir%20-%20${encodeURIComponent(name)}&body=Dear%20Admin%20ZonaKasir,%0A%0ASaya%20ingin%20bertanya%20mengenai%20layanan%20Anda.%0A%0ANama:%20${encodeURIComponent(name)}%0AEmail:%20${encodeURIComponent(email)}%0APertanyaan:%20${encodeURIComponent(msg)}`;
                    
                    // Trigger mailto composer
                    window.location.href = mailtoUrl;

                    setSuccessSource('consultation');
                    setActiveModal('success');
                  }} 
                  className="space-y-3.5 pt-1"
                >
                  <div>
                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Nama Lengkap</label>
                    <input required name="consName" type="text" placeholder="e.g. Ahmad Fauzi" className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900" />
                  </div>
                  <div>
                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Alamat Email Aktif</label>
                    <input required name="consEmail" type="email" placeholder="e.g. ahmad.fauzi@gmail.com" className="w-full bg-gray-55 border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900" />
                  </div>
                  <div>
                    <label className="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Pertanyaan / Pesan Masalah</label>
                    <textarea required name="consMessage" rows={3} placeholder="Tuliskan pertanyaan Anda secara detail di sini..." className="w-full bg-[#FAFAFA] border border-gray-200 rounded-[5px] p-2.5 text-xs font-semibold focus:outline-none focus:border-gray-900 resize-none font-sans" />
                  </div>

                  <button 
                    type="submit" 
                    className="w-full bg-gray-900 text-white font-sans text-xs font-bold py-3.5 rounded-[6px] hover:bg-gray-800 transition-all cursor-pointer shadow-md flex items-center justify-center gap-1.5"
                  >
                    Kirim Pertanyaan via Email
                    <ArrowRight className="w-4 h-4 text-white" />
                  </button>
                </form>
              </div>
            )}

            {activeModal === 'success' && (
              <div className="py-6 text-center space-y-4">
                <div className="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto text-xl font-bold">
                  ✓
                </div>
                <div>
                  <h3 className="font-sans font-bold text-base text-gray-950">
                    {successSource === 'consultation' ? 'Pertanyaan Siap Dikirim!' : 'Data Berhasil Terkirim!'}
                  </h3>
                  <p className="text-xs text-gray-500 mt-2 max-w-[300px] mx-auto leading-relaxed">
                    {successSource === 'consultation'
                      ? 'Draf email berisi pertanyaan Anda telah berhasil disiapkan dan diteruskan ke klien email perangkat Anda untuk dikirim langsung ke support@zonakasir.id.'
                      : 'Sistem simulasi kami telah menyimpan preferensi Anda. Konsultan PT Zona Teknologi Nusantara akan menghubungi Anda dalam waktu 1x24 jam kerja.'}
                  </p>
                </div>
                <button 
                  onClick={() => setActiveModal('none')}
                  className="w-full bg-gray-900 text-white font-semibold text-xs py-2.5 rounded-[6px] hover:bg-gray-800 transition-all cursor-pointer"
                >
                  Tutup
                </button>
              </div>
            )}

          </div>
        </div>
      )}

      <style>{`
        /* Dynamic aesthetic background enhancements */
        .bg-radial-gradient-vignette {
          background: radial-gradient(circle, transparent 20%, rgba(255,255,255,0.7) 100%);
        }
        .filter.grayscale-subtle {
          filter: grayscale(40%);
        }
        .filter.grayscale-subtle:hover {
          filter: grayscale(0%);
        }
        .bg-grid-lines {
          background-size: 40px 40px;
          background-image: linear-gradient(to right, rgba(255,255,255,0.05) 1px, transparent 1px),
                            linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px);
        }
        .animate-slide-down {
          animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-scale-up {
          animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slideDown {
          from { transform: translateY(-10px); opacity: 0; }
          to { transform: translateY(0); opacity: 1; }
        }
        @keyframes scaleUp {
          from { transform: scale(0.95); opacity: 0; }
          to { transform: scale(1); opacity: 1; }
        }
        .bg-gray-55 {
          background-color: #F8F8F6;
        }
      `}</style>
    </div>
  );
}
