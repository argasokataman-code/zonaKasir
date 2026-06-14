/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect, useRef } from 'react';
import { Search, ShoppingCart, CreditCard, ScanLine, Printer, CheckCircle, RefreshCw, Layers } from 'lucide-react';
import { Product, CartItem } from '../types';
import { INITIAL_PRODUCTS } from '../data';
import { useLanguage } from '../i18n';

interface DeviceTabletProps {
  interactive?: boolean;
}

export default function DeviceTablet({ interactive = true }: DeviceTabletProps) {
  const { t } = useLanguage();
  const [products, setProducts] = useState<Product[]>(INITIAL_PRODUCTS);
  const [cart, setCart] = useState<CartItem[]>([
    { product: INITIAL_PRODUCTS[0], quantity: 2 }, // 2x Kopi Susu Aren
    { product: INITIAL_PRODUCTS[1], quantity: 1 }  // 1x Almond Croissant
  ]);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('Semua');
  const [paymentStep, setPaymentStep] = useState<'idle' | 'billing' | 'paying' | 'success'>('idle');
  const [paymentMethod, setPaymentMethod] = useState<'QRIS' | 'Cash' | 'Debit' | null>(null);
  const [scanning, setScanning] = useState(false);
  const [showReceipt, setShowReceipt] = useState(false);
  const [activeTab, setActiveTab] = useState<'cashier' | 'history'>('cashier');

  // Mouse Drag & Scroll Physics for Smooth Scrolling with Desktop Mouse
  const scrollRef = useRef<HTMLDivElement>(null);
  const isDragging = useRef(false);
  const dragStartX = useRef(0);
  const dragScrollLeft = useRef(0);

  const handleMouseDown = (e: React.MouseEvent<HTMLDivElement>) => {
    if (!scrollRef.current) return;
    
    // Only scroll with left click
    if (e.button !== 0) return;

    // Skip drag if clicking on standard form controls, buttons or interactive slots
    const target = e.target as HTMLElement;
    if (
      target.closest('button') || 
      target.closest('input') || 
      target.closest('select') || 
      target.closest('textarea') ||
      target.closest('a')
    ) {
      return;
    }

    isDragging.current = true;
    dragStartX.current = e.pageX - scrollRef.current.offsetLeft;
    dragScrollLeft.current = scrollRef.current.scrollLeft;
    
    scrollRef.current.style.cursor = 'grabbing';
    scrollRef.current.style.userSelect = 'none';
    scrollRef.current.style.scrollBehavior = 'auto'; // Instant response
  };

  const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>) => {
    if (!isDragging.current || !scrollRef.current) return;
    e.preventDefault();
    const x = e.pageX - scrollRef.current.offsetLeft;
    const walk = (x - dragStartX.current) * 1.5; // Drag speed multiplier
    scrollRef.current.scrollLeft = dragScrollLeft.current - walk;
  };

  const handleMouseUpOrLeave = () => {
    if (!isDragging.current || !scrollRef.current) return;
    isDragging.current = false;
    scrollRef.current.style.cursor = 'grab';
    scrollRef.current.style.userSelect = '';
    scrollRef.current.style.scrollBehavior = 'smooth';
  };

  // Translate vertical scroll wheel action to horizontal scroll for mouse-users
  const handleWheel = (e: React.WheelEvent<HTMLDivElement>) => {
    if (!scrollRef.current) return;
    
    // Check if primarily vertical scrolling to map to horizontal
    if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
      e.preventDefault();
      scrollRef.current.scrollLeft += e.deltaY * 0.95; // responsive scroll factor
    }
  };

  // Multi-step Autoplay Demo for Section 2 Cinematic Flow
  const [isAutoplayActive, setIsAutoplayActive] = useState(!interactive);
  const [autoplayStage, setAutoplayStage] = useState(0);
  const autoplayTimerRef = useRef<NodeJS.Timeout | null>(null);

  // Categories list
  const categories = [t('device.tablet.category_all'), t('device.tablet.category_drinks'), t('device.tablet.category_food')];

  // Add to cart
  const addToCart = (product: Product) => {
    // Prevent modification if in payment steps
    if (paymentStep !== 'idle') return;
    
    // Stop autoplay on user interaction
    if (!interactive) setIsAutoplayActive(false);

    setCart(prev => {
      const existing = prev.find(item => item.product.id === product.id);
      if (existing) {
        return prev.map(item => 
          item.product.id === product.id 
            ? { ...item, quantity: item.quantity + 1 } 
            : item
        );
      }
      return [...prev, { product, quantity: 1 }];
    });
  };

  // Remove from cart
  const removeFromCart = (productId: string) => {
    if (paymentStep !== 'idle') return;
    setCart(prev => prev.filter(item => item.product.id !== productId));
  };

  // Adjust quantities
  const adjustQty = (productId: string, amount: number) => {
    if (paymentStep !== 'idle') return;
    setCart(prev => {
      return prev.map(item => {
        if (item.product.id === productId) {
          const newQty = item.quantity + amount;
          return newQty > 0 ? { ...item, quantity: newQty } : item;
        }
        return item;
      }).filter(item => item.quantity > 0);
    });
  };

  // Calculations
  const subtotal = cart.reduce((acc, item) => acc + (item.product.price * item.quantity), 0);
  const tax = Math.round(subtotal * 0.11); // PPN 11%
  const total = subtotal + tax;

  // Filter products
  const filteredProducts = products.filter(p => {
    const matchesSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase()) || p.sku.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = selectedCategory === 'Semua' || p.category === selectedCategory;
    return matchesSearch && matchesCategory;
  });

  // Simulated scan tool action
  const simulateBarcodeScan = () => {
    if (paymentStep !== 'idle') return;
    setScanning(true);
    // Beep sound representation & adding low-stock cookie product
    setTimeout(() => {
      setScanning(false);
      const cookie = INITIAL_PRODUCTS.find(p => p.id === '6');
      if (cookie) addToCart(cookie);
    }, 1200);
  };

  // Trigger payment flow
  const initiatePayment = () => {
    if (cart.length === 0) return;
    setPaymentStep('billing');
  };

  const handleSelectPayment = (method: 'QRIS' | 'Cash' | 'Debit') => {
    setPaymentMethod(method);
    setPaymentStep('paying');
    
    // Simulate transaction processing
    setTimeout(() => {
      setPaymentStep('success');
      setShowReceipt(true);
    }, 2500);
  };

  const resetRegister = () => {
    setCart([]);
    setPaymentStep('idle');
    setPaymentMethod(null);
    setShowReceipt(false);
    setSearchQuery('');
    setSelectedCategory('Semua');
  };

  // Autoplay loop definition for beautiful idle demo
  useEffect(() => {
    if (interactive) return;

    const playSteps = async () => {
      // Step 0: Idle state, reset
      if (autoplayStage === 0) {
        resetRegister();
        autoplayTimerRef.current = setTimeout(() => setAutoplayStage(1), 2000);
      }
      // Step 1: Simulate product search typing
      else if (autoplayStage === 1) {
        let text = "";
        const targetText = "Kopi";
        let i = 0;
        const typeInterval = setInterval(() => {
          if (i < targetText.length) {
            text += targetText[i];
            setSearchQuery(text);
            i++;
          } else {
            clearInterval(typeInterval);
            setAutoplayStage(2);
          }
        }, 150);
      }
      // Step 2: Add searched item and another item
      else if (autoplayStage === 2) {
        autoplayTimerRef.current = setTimeout(() => {
          const match = INITIAL_PRODUCTS[0]; // Kopi Susu Aren
          addToCart(match);
          setSearchQuery('');
          
          autoplayTimerRef.current = setTimeout(() => {
            // Simulate simulated barcode scan trigger
            setScanning(true);
            autoplayTimerRef.current = setTimeout(() => {
              setScanning(false);
              const secondItem = INITIAL_PRODUCTS[1]; // Croissant
              addToCart(secondItem);
              setAutoplayStage(3);
            }, 1000);
          }, 1200);
        }, 1000);
      }
      // Step 3: Go to Billing Screen
      else if (autoplayStage === 3) {
        autoplayTimerRef.current = setTimeout(() => {
          setPaymentStep('billing');
          setAutoplayStage(4);
        }, 1800);
      }
      // Step 4: Pay with QRIS
      else if (autoplayStage === 4) {
        autoplayTimerRef.current = setTimeout(() => {
          setPaymentMethod('QRIS');
          setPaymentStep('paying');
          
          autoplayTimerRef.current = setTimeout(() => {
            setPaymentStep('success');
            setShowReceipt(true);
            setAutoplayStage(5);
          }, 2200);
        }, 1500);
      }
      // Step 5: Finished, dwell on receipt, then reset
      else if (autoplayStage === 5) {
        autoplayTimerRef.current = setTimeout(() => {
          setAutoplayStage(0);
        }, 6000); // Look at receipt for 6s before restarting
      }
    };

    playSteps();

    return () => {
      if (autoplayTimerRef.current) clearTimeout(autoplayTimerRef.current);
    };
  }, [autoplayStage, interactive]);

  return (
    <div 
      className="w-full max-w-4xl mx-auto font-sans animate-fade-in overflow-visible" 
      id="tablet-pos-wrapper"
    >
      {/* Outer Shell - Sleek modern landscape tablet device bezel with aluminum design details */}
      <div className="w-full bg-[#141416] rounded-[24px] p-3 md:p-5 shadow-2xl relative border border-[#2B2B2E] ring-4 md:ring-8 ring-[#1E1E22] ring-offset-2 ring-offset-[#111112]">
        
        {/* Hardware Detail: Front Camera Lens Dot */}
        <div className="absolute top-2.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-[#202022] border border-[#353538] shadow-inner z-40" />
        
        {/* Hardware Detail: Sleek speaker slit on Left & Right edge */}
        <div className="absolute -left-1 top-1/2 -translate-y-1/2 w-1 h-8 bg-gray-700/80 rounded-r-xs" />
        <div className="absolute -right-1 top-1/2 -translate-y-1/2 w-1 h-8 bg-gray-700/80 rounded-l-xs" />
        
        {/* Anti-reflective Glass Screen Outer Frame */}
        <div className="bg-slate-50 rounded-[12px] overflow-hidden flex flex-col md:flex-row h-[620px] md:h-[560px] font-sans text-gray-800 relative border border-black/15 shadow-inner">
          
          {/* Main POS Interface Grid */}
          <div className="flex-1 flex flex-col h-[55%] md:h-full bg-slate-50 overflow-hidden">
            {/* Header / Search Controls */}
            <div className="p-4 bg-white border-b border-[#E5E5E1] flex items-center justify-between gap-4">
              <div className="flex items-center gap-2">
                <div className="w-6 h-6 bg-[#FF6600] rounded-[4px] flex items-center justify-center">
                  <span className="text-[10px] font-extrabold text-white">ZK</span>
                </div>
                <span className="font-sans text-[11px] font-bold uppercase tracking-wider text-[#1A1A1A]">
                  {t('device.tablet.pos_terminal')}
                </span>
              </div>
              
              {/* Category Toggles */}
              <div className="flex bg-[#F4F4F2] p-0.5 rounded-[6px] gap-0.5 border border-[#E5E5E1]">
                {categories.map((cat) => (
                  <button
                    key={cat}
                    onClick={() => setSelectedCategory(cat)}
                    className={`text-[10px] font-bold px-3 py-1 cursor-pointer transition-all rounded-[4px] uppercase tracking-wider ${
                      selectedCategory === cat 
                        ? 'bg-white text-[#1A1A1A] shadow-sm' 
                        : 'text-[#666666] hover:text-[#1A1A1A]'
                    }`}
                    id={`table-category-${cat}`}
                  >
                    {cat}
                  </button>
                ))}
              </div>
            </div>

            {/* Catalog Grid Area */}
            <div className="p-4 flex-1 overflow-y-auto no-scrollbar">
              <div className="flex items-center justify-between mb-4 gap-3">
                <div className="relative flex-1 max-w-[280px]">
                  <Search className="absolute left-2.5 top-2.5 w-3.5 h-3.5 text-[#888888]" />
                  <input
                    type="text"
                    placeholder={t('device.tablet.search_placeholder')}
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-full bg-white text-xs pl-8 pr-3 py-2 border border-[#D1D1CC] rounded-[6px] focus:outline-none focus:ring-1 focus:ring-[#1A1A1A] focus:border-[#1A1A1A] placeholder-[#888888] font-medium"
                    id="tablet-pos-search"
                  />
                  {searchQuery && (
                    <button 
                      onClick={() => setSearchQuery('')}
                      className="absolute right-2.5 top-2 ml-1 text-xs text-[#888888] hover:text-[#1A1A1A]"
                    >
                      ×
                    </button>
                  )}
                </div>

                <button
                  onClick={simulateBarcodeScan}
                  disabled={scanning}
                  className={`flex items-center gap-1.5 cursor-pointer text-[10px] uppercase tracking-wider font-bold px-4 py-2 border rounded-[6px] transition-all active:scale-95 ${
                    scanning 
                      ? 'bg-red-50 text-red-600 border-red-200' 
                      : 'bg-white text-[#1A1A1A] border-[#D1D1CC] hover:bg-[#F4F4F2]'
                  }`}
                  id="tablet-pos-scan-btn"
                >
                  <ScanLine className={`w-3.5 h-3.5 ${scanning ? 'animate-pulse text-red-500' : ''}`} />
                  {scanning ? t('device.tablet.scanning_sku') : t('device.tablet.scan_sku')}
                </button>
              </div>

              {/* Product Grid */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-2.5">
                {filteredProducts.map(product => {
                  const hasLowStock = product.stock <= 18;
                  const isCritical = product.stock <= 4;
                  return (
                    <div
                      key={product.id}
                      onClick={() => addToCart(product)}
                      className="bg-white border border-[#E5E5E1] hover:border-[#1A1A1A] hover:shadow-sm transition-all rounded-[6px] overflow-hidden flex flex-col cursor-pointer group active:scale-98"
                    >
                      {/* Beautiful Authentic Image */}
                      <div className="aspect-[4/3] w-full bg-[#F4F4F2] overflow-hidden relative border-b border-[#E5E5E1] select-none">
                        {product.image ? (
                          <img 
                            src={product.image} 
                            alt={product.name} 
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            referrerPolicy="no-referrer"
                          />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center font-bold text-xs text-[#888888]">
                            ZK
                          </div>
                        )}
                        {hasLowStock && (
                          <span className={`absolute top-2 right-2 text-[8px] font-bold px-1.5 py-0.5 rounded-[4px] shadow-sm tracking-wide z-10 ${
                            isCritical ? 'bg-red-600 text-white' : 'bg-amber-500 text-white'
                          }`}>
                            {t('device.tablet.stock')} {product.stock}
                          </span>
                        )}
                      </div>
                      
                      {/* Product copy */}
                      <div className="p-3 flex-1 flex flex-col justify-between">
                        <div>
                          <span className="font-mono text-[9px] text-[#888888] font-medium tracking-wider block mb-0.5">{product.sku}</span>
                          <h4 className="font-sans text-[11px] font-bold text-[#1A1A1A] leading-snug group-hover:text-[#666666] transition-colors line-clamp-2">
                            {product.name}
                          </h4>
                        </div>
                        <div className="mt-3 pt-2 border-t border-dashed border-[#E5E5E1] flex items-center justify-between">
                          <span className="text-[9px] text-[#888888] font-bold uppercase tracking-wide">{product.category}</span>
                          <span className="font-mono text-xs font-bold text-[#1A1A1A]">
                            Rp {(product.price).toLocaleString('id-ID')}
                          </span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>

              {filteredProducts.length === 0 && (
                <div className="py-12 text-center">
                  <p className="text-xs text-gray-400 font-medium">Menu tidak ditemukan</p>
                </div>
              )}
            </div>
          </div>

          {/* Checkout Cart Column (Right Panel) */}
          <div className="w-full md:w-[280px] lg:w-[310px] bg-white flex flex-col h-[40%] md:h-full border-t md:border-t-0 md:border-l border-[#E5E5E1] shrink-0">
            {/* Cart Header */}
            <div className="p-4 border-b border-gray-200 flex items-center justify-between bg-slate-50">
              <div className="flex items-center gap-2">
                <ShoppingCart className="w-4 h-4 text-gray-700" />
                <h3 className="font-sans text-xs font-bold text-gray-900">{t('device.tablet.active_cart')}</h3>
              </div>
              <span className="bg-gray-900 text-white font-semibold font-mono text-[10px] px-2 py-0.5 rounded-full">
                {cart.reduce((s, i) => s + i.quantity, 0)} {t('device.pcs')}
              </span>
            </div>

            {/* Cart Items List */}
            <div className="flex-1 overflow-y-auto p-4 space-y-3 no-scrollbar">
              {cart.map((item) => (
                <div key={item.product.id} className="flex gap-2.5 justify-between select-none">
                  <div className="flex-1">
                    <h5 className="font-sans text-xs font-semibold text-gray-900 leading-tight">
                      {item.product.name}
                    </h5>
                    <span className="font-mono text-[9px] text-gray-400">
                      Rp {item.product.price.toLocaleString('id-ID')} / {t('device.pcs')}
                    </span>
                  </div>

                  <div className="flex flex-col items-end gap-1">
                    <span className="font-mono text-xs font-bold text-gray-800">
                      Rp {(item.product.price * item.quantity).toLocaleString('id-ID')}
                    </span>
                    <div className="flex items-center border border-gray-200 rounded-[5px] overflow-hidden bg-slate-50">
                      <button 
                        onClick={() => adjustQty(item.product.id, -1)}
                        className="px-2 py-0.5 text-[10px] font-bold text-gray-500 hover:bg-gray-100"
                      >
                        -
                      </button>
                      <span className="px-2 font-mono text-[10px] font-bold text-gray-700">
                        {item.quantity}
                      </span>
                      <button 
                        onClick={() => adjustQty(item.product.id, 1)}
                        className="px-2 py-0.5 text-[10px] font-bold text-gray-500 hover:bg-gray-100"
                      >
                        +
                      </button>
                    </div>
                  </div>
                </div>
              ))}

              {cart.length === 0 && (
                <div className="h-full flex flex-col items-center justify-center py-12 text-center">
                  <div className="p-3 bg-gray-50 rounded-full mb-2">
                    <ShoppingCart className="w-5 h-5 text-gray-300" />
                  </div>
                  <p className="text-xs text-gray-400 font-medium">{t('device.tablet.empty_cart')}</p>
                  <p className="text-[10px] text-gray-300">{t('device.tablet.touch_menu')}</p>
                </div>
              )}
            </div>

            {/* Checkout Pricing Details */}
            <div className="p-4 border-t border-gray-100 bg-slate-50 space-y-2">
              <div className="flex justify-between text-[11px] text-gray-400 font-semibold">
                <span>{t('device.tablet.subtotal')}</span>
                <span className="font-mono text-xs">Rp {subtotal.toLocaleString('id-ID')}</span>
              </div>
              <div className="flex justify-between text-[11px] text-gray-400 font-semibold">
                <span>{t('device.tablet.tax')}</span>
                <span className="font-mono text-xs">Rp {tax.toLocaleString('id-ID')}</span>
              </div>
              <div className="flex justify-between items-center text-xs text-gray-900 font-bold pt-1.5 border-t border-dashed border-gray-200">
                <span>{t('device.tablet.total')}</span>
                <span className="font-mono text-[14px] text-red-600 font-bold">
                  Rp {total.toLocaleString('id-ID')}
                </span>
              </div>

              {/* Action Button */}
              {paymentStep === 'idle' ? (
                <button
                  onClick={initiatePayment}
                  disabled={cart.length === 0}
                  className={`w-full text-xs font-bold text-white py-3 rounded-[6px] transition-all flex items-center justify-center gap-1.5 cursor-pointer ${
                    cart.length > 0 
                      ? 'bg-gray-900 hover:bg-gray-800 shadow-md active:scale-95' 
                      : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                  }`}
                  id="tablet-pay-trigger"
                >
                  <CreditCard className="w-3.5 h-3.5" />
                  {t('device.tablet.select_payment')}
                </button>
              ) : (
                <button
                  onClick={resetRegister}
                  className="w-full text-xs font-bold text-gray-600 border border-gray-200 bg-white hover:bg-slate-50 py-2.5 rounded-[6px] transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                  id="tablet-reset-trigger"
                >
                  <RefreshCw className="w-3 h-3" />
                  {t('device.tablet.new_transaction')}
                </button>
              )}
            </div>
          </div>

          {/* Payment Modal/Overlay inside Tablet Frame */}
          {paymentStep !== 'idle' && (
            <div className="absolute inset-0 bg-gray-905/70 backdrop-blur-subtle flex items-center justify-center p-6 z-20">
              <div className="bg-white rounded-[10px] w-full max-w-[420px] shadow-2xl border border-gray-100 overflow-hidden text-center p-6">
                
                {paymentStep === 'billing' && (
                  <div>
                    <h3 className="font-sans text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">
                      {t('device.tablet.payment_method')}
                    </h3>
                    <p className="font-mono text-[18px] text-gray-900 font-bold mb-5">
                      Rp {total.toLocaleString('id-ID')}
                    </p>

                    <div className="grid grid-cols-3 gap-3 mb-6">
                      <button
                        onClick={() => handleSelectPayment('QRIS')}
                        className="border border-gray-200 hover:border-emerald-500 hover:bg-emerald-50/20 active:scale-98 p-3 rounded-[8px] flex flex-col items-center justify-center gap-2 cursor-pointer transition-all"
                        id="payment-qris"
                      >
                        <div className="w-8 h-8 rounded bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold">
                          QR
                        </div>
                        <span className="font-sans text-[11px] font-bold text-gray-700">QRIS</span>
                      </button>

                      <button
                        onClick={() => handleSelectPayment('Cash')}
                        className="border border-gray-200 hover:border-amber-500 hover:bg-amber-50/20 active:scale-98 p-3 rounded-[8px] flex flex-col items-center justify-center gap-2 cursor-pointer transition-all"
                        id="payment-cash"
                      >
                        <div className="w-8 h-8 rounded bg-amber-50 flex items-center justify-center text-amber-600 font-bold">
                          Rp
                        </div>
                        <span className="font-sans text-[11px] font-bold text-gray-700">Cash</span>
                      </button>

                      <button
                        onClick={() => handleSelectPayment('Debit')}
                        className="border border-gray-200 hover:border-sky-500 hover:bg-sky-50/20 active:scale-98 p-3 rounded-[8px] flex flex-col items-center justify-center gap-2 cursor-pointer transition-all"
                        id="payment-debit"
                      >
                        <div className="w-8 h-8 rounded bg-sky-50 flex items-center justify-center text-sky-600 font-bold">
                          💳
                        </div>
                        <span className="font-sans text-[11px] font-bold text-gray-700">Debit</span>
                      </button>
                    </div>

                    <button
                      onClick={() => setPaymentStep('idle')}
                      className="text-xs text-gray-400 hover:text-gray-600 underline font-medium"
                    >
                      {t('device.tablet.back_to_cashier')}
                    </button>
                  </div>
                )}

                {paymentStep === 'paying' && (
                  <div className="py-6 flex flex-col items-center">
                    {paymentMethod === 'QRIS' ? (
                      <div className="space-y-4">
                        <span className="text-[10px] font-mono font-bold tracking-wider uppercase text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded">
                          {t('device.tablet.qris_processing')}
                        </span>
                        
                        {/* Dynamic aesthetic QR Box */}
                        <div className="w-40 h-40 mx-auto bg-white border-2 border-gray-100 p-2 rounded-[8px] shadow-sm flex items-center justify-center relative">
                          <div className="absolute inset-0 bg-radial-scanner opacity-20 pointer-events-none" />
                          {/* Inner barcode look */}
                          <div className="grid grid-cols-4 gap-0.5 w-full h-full opacity-90 p-1">
                            {Array.from({ length: 16 }).map((_, i) => (
                              <div 
                                key={i} 
                                className={`rounded-sm ${
                                  (i%3===0 || i%5===0 || i===0) ? 'bg-gray-900' : 'bg-transparent'
                                }`} 
                              />
                            ))}
                          </div>
                          {/* Center point badge */}
                          <div className="absolute w-8 h-8 bg-white border border-gray-200 rounded flex items-center justify-center text-[8px] font-bold">
                            ZK
                          </div>
                        </div>

                        <p className="text-xs text-gray-500 max-w-[280px]">
                          {t('device.tablet.scan_qr_instruction')}
                        </p>
                      </div>
                    ) : (
                      <div className="space-y-4 py-4">
                        <div className="animate-spin rounded-full h-8 w-8 border-2 border-gray-900 border-t-transparent mx-auto" />
                        <p className="text-xs font-semibold text-gray-600">
                          {t('device.tablet.processing_payment')} {paymentMethod}...
                        </p>
                      </div>
                    )}
                  </div>
                )}

                {paymentStep === 'success' && (
                  <div className="py-4 space-y-4">
                    <div className="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mx-auto text-emerald-500">
                      <CheckCircle className="w-8 h-8" />
                    </div>
                    <div>
                      <h4 className="font-sans text-sm font-bold text-gray-950">{t('device.tablet.payment_success')}</h4>
                      <p className="text-xs font-mono text-gray-400 mt-0.5">{t('device.tablet.transaction_id')}: ZK-9830491823908</p>
                    </div>

                    <div className="bg-slate-50 rounded-[8px] p-3 text-left border border-gray-100 space-y-1">
                      <div className="flex justify-between text-[11px] text-gray-500">
                        <span>{t('device.tablet.total_paid')}</span>
                        <span className="font-mono font-bold text-gray-900">Rp {total.toLocaleString('id-ID')}</span>
                      </div>
                      <div className="flex justify-between text-[11px] text-gray-500">
                        <span>{t('device.tablet.method')}</span>
                        <span className="font-semibold text-gray-900">{paymentMethod}</span>
                      </div>
                    </div>

                    <button
                      onClick={resetRegister}
                      className="w-full bg-gray-900 text-white font-semibold text-xs py-2.5 rounded-[6px] hover:bg-gray-800 transition-all cursor-pointer"
                      id="payment-success-close"
                    >
                      {t('device.tablet.done')}
                    </button>
                  </div>
                )}

              </div>
            </div>
          )}
        </div>

        {/* Cinematic Receipt Rollout - Section 2 Hero Component */}
        {showReceipt && (
          <div className="hidden md:block absolute -right-48 -bottom-48 z-30 w-56 bg-white shadow-2xl border border-gray-200 p-4 rounded-b-[4px] border-t-4 border-t-gray-900 animate-receipt-roll font-sans text-gray-900 [transform-origin:top_center]">
            <div className="border-b border-dashed border-gray-200 pb-3 text-center">
              <h4 className="font-bold text-xs uppercase tracking-wide">KOPI SANA-SINI</h4>
              <p className="text-[9px] text-gray-400 leading-tight mt-0.5">Senopati, Jakarta Selatan</p>
            </div>

            <div className="py-3 border-b border-dashed border-gray-200 text-[10px] text-gray-500 font-mono space-y-1.5">
              <div className="flex justify-between">
                <span>SKU/TRX</span>
                <span>#ZK983049</span>
              </div>
              <div className="flex justify-between">
                <span>{t('device.tablet.waktu')}</span>
                <span>13-06-2026 10:43</span>
              </div>
              <div className="flex justify-between">
                <span>{t('device.tablet.cashier')}</span>
                <span>Andini</span>
              </div>
            </div>

            <div className="py-3 space-y-1.5 border-b border-dashed border-gray-200">
              {cart.map(item => (
                <div key={item.product.id} className="text-[10px] flex justify-between gap-2.5 text-gray-800 leading-tight">
                  <div className="flex-1">
                    <div>{item.product.name}</div>
                    <div className="text-[8px] text-gray-400 font-mono">{item.quantity} x Rp {item.product.price.toLocaleString('id-ID')}</div>
                  </div>
                  <span className="font-mono font-medium">Rp {(item.product.price * item.quantity).toLocaleString('id-ID')}</span>
                </div>
              ))}
            </div>

            <div className="py-3 text-[10px] font-semibold space-y-1 text-gray-800">
              <div className="flex justify-between">
                <span>{t('device.tablet.subtotal')}</span>
                <span className="font-mono">Rp {subtotal.toLocaleString('id-ID')}</span>
              </div>
              <div className="flex justify-between">
                <span>{t('device.tablet.tax')}</span>
                <span className="font-mono">Rp {tax.toLocaleString('id-ID')}</span>
              </div>
              <div className="flex justify-between text-xs font-bold text-red-600 pt-1.5 border-t border-dashed border-gray-100">
                <span>{t('device.tablet.total')}</span>
                <span className="font-mono">Rp {total.toLocaleString('id-ID')}</span>
              </div>
            </div>

            <div className="pt-3 text-center border-t border-dashed border-gray-200">
              <div className="w-16 h-16 bg-gray-50 mx-auto border border-gray-150 p-1 flex items-center justify-center rounded">
                <Printer className="w-8 h-8 text-gray-300" />
              </div>
              <p className="text-[8px] text-gray-400 mt-2 font-medium">{t('device.tablet.thanks')}</p>
              <p className="text-[7px] text-gray-300 mt-1 uppercase font-mono">powered by zonakasir</p>
            </div>
            
            <button
              onClick={() => setShowReceipt(false)}
              className="absolute -top-2 -right-2 w-5 h-5 bg-red-150 text-red-700 hover:bg-red-200 rounded-full flex items-center justify-center text-xs border border-red-200 cursor-pointer"
            >
              ×
            </button>
          </div>
        )}
      </div>

      {interactive && (
        <p className="text-gray-400 text-center font-sans text-xs mt-3 flex items-center justify-center gap-1.5 select-none md:hidden">
          <span>* {t('device.tablet.pos_terminal')} *</span>
        </p>
      )}

      {/* Embedded Animations Rule */}
      <style>{`
        @keyframes receiptRoll {
          0% {
            transform: scaleY(0);
            opacity: 0;
          }
          100% {
            transform: scaleY(1);
            opacity: 1;
          }
        }
        .animate-receipt-roll {
          animation: receiptRoll 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .bg-gray-905\/70 {
          background-color: rgba(18, 18, 20, 0.72);
        }
        .backdrop-blur-subtle {
          backdrop-filter: blur(4px);
          -webkit-backdrop-filter: blur(4px);
        }
      `}</style>
    </div>
  );
}
