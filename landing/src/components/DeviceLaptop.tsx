import React, { useState, useRef } from 'react';
import { 
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  BarChart, Bar, Cell 
} from 'recharts';
import { 
  LayoutDashboard, ClipboardList, TrendingUp, AlertTriangle, ArrowUpRight, 
  ArrowDownLeft, Box, Download, Settings, Users, Layers, ExternalLink,
  Zap, Banknote, CreditCard, Archive, FileText, Printer, ChevronRight
} from 'lucide-react';
import { Product, StockMovement } from '../types';
import { INITIAL_PRODUCTS, STOCK_MOVEMENTS, HOURLY_SALES, BEST_SELLERS } from '../data';
import { useLanguage } from '../i18n';

interface DeviceLaptopProps {
  interactive?: boolean;
}

export default function DeviceLaptop({ interactive = true }: DeviceLaptopProps) {
  const { t } = useLanguage();
  const [products, setProducts] = useState<Product[]>(INITIAL_PRODUCTS);
  const [movements, setMovements] = useState<StockMovement[]>(STOCK_MOVEMENTS);
  const [totalSales, setTotalSales] = useState<number>(12840000); // Rp 12.84M
  const [selectedTab, setSelectedTab] = useState<'dashboard' | 'inventory'>('dashboard');

  // Mouse Drag & Scroll Physics for Smooth Scrolling with Desktop Mouse
  const scrollRef = useRef<HTMLDivElement>(null);
  const isDragging = useRef(false);
  const dragStartX = useRef(0);
  const dragScrollLeft = useRef(0);

  const handleMouseDown = (e: React.MouseEvent<HTMLDivElement>) => {
    if (!scrollRef.current) return;
    if (e.button !== 0) return; // Only left click

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
    const walk = (x - dragStartX.current) * 1.5; // Drag multiplier
    scrollRef.current.scrollLeft = dragScrollLeft.current - walk;
  };

  const handleMouseUpOrLeave = () => {
    if (!isDragging.current || !scrollRef.current) return;
    isDragging.current = false;
    scrollRef.current.style.cursor = 'grab';
    scrollRef.current.style.userSelect = '';
    scrollRef.current.style.scrollBehavior = 'smooth';
  };

  // Maps vertical scroll to horizontal scroll ONLY when there's scrollable horizontal content
  const handleWheel = (e: React.WheelEvent<HTMLDivElement>) => {
    if (!scrollRef.current) return;
    if (scrollRef.current.scrollWidth > scrollRef.current.clientWidth) {
      if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
        e.preventDefault();
        scrollRef.current.scrollLeft += e.deltaY * 0.95;
      }
    }
  };

  // Interactive action - Restock an item instantly!
  const triggerRestock = (productId: string) => {
    setProducts(prev => 
      prev.map(p => {
        if (p.id === productId) {
          const addedAmt = 100;
          // Create movement log
          const newMov: StockMovement = {
            id: 'm-restock-' + Date.now(),
            timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
            productName: p.name,
            sku: p.sku,
            type: 'IN',
            quantity: addedAmt,
            status: 'Complete'
          };
          setMovements(prevM => [newMov, ...prevM]);
          return { ...p, stock: p.stock + addedAmt };
        }
        return p;
      })
    );
  };

  // Format IDR helper
  const formatIDR = (num: number) => {
    if (num >= 1000000) {
      return `Rp ${(num / 1000000).toFixed(2)}M`;
    }
    return `Rp ${num.toLocaleString('id-ID')}`;
  };

  return (
    <div 
      className="w-full max-w-4xl mx-auto font-sans animate-fade-in"
      id="laptop-dashboard-outer"
    >
      <div className="w-full" id="laptop-dashboard-wrapper">
        {/* Laptop MacBook Outer Shell - Top Display (Silver Aluminum + Slim Modern Profile) */}
        <div className="bg-gradient-to-r from-[#DFDFDF] via-[#EDEDED] to-[#D5D5D5] rounded-t-[18px] p-2.5 pb-0 shadow-xl border border-gray-300 relative">
          
          {/* Hardware Detail: Glossy Front Web Camera Lens */}
          <div className="absolute top-1 left-1/2 -translate-x-1/2 flex items-center gap-1.5 z-40">
            <div className="w-2.5 h-2.5 rounded-full bg-[#111] border border-gray-400/60 flex items-center justify-center">
              <div className="w-0.5 h-0.5 rounded-full bg-blue-500/90" />
            </div>
            {/* Active webcam Green LED */}
            <span className="w-1 h-1 rounded-full bg-emerald-500/80 border border-emerald-300 shadow-xs" />
          </div>

          {/* Anti-reflective Glass Screen Bezel */}
          <div className="bg-zinc-950 p-[6px] md:p-[10px] pb-0 rounded-t-[10px] overflow-hidden">
            {/* Screen Content Container */}
            <div className="bg-white rounded-t-[4px] overflow-hidden flex flex-row h-[420px] md:h-[510px] text-gray-800 relative shadow-inner">
              
              {/* Laptop Left Workspace Sidebar — matches real Filament light mode */}
              <div className="hidden md:flex w-[185px] bg-white border-r border-gray-200 flex-col justify-between h-full shrink-0 shadow-md">
                <div>
                  {/* Brand Header */}
                  <div className="p-4 border-b border-gray-200 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <div className="w-6 h-6 rounded bg-[#FF6600] flex items-center justify-center text-white font-extrabold text-xs">
                        ZK
                      </div>
                      <span className="font-sans font-bold text-xs tracking-wider text-gray-900 uppercase">
                        ZonaKasir
                      </span>
                    </div>
                  </div>

                  {/* Navigation Menu Links — matches real Filament panel */}
                  <nav className="p-2 space-y-0.5">
                    <button
                      onClick={() => setSelectedTab('dashboard')}
                      className={`w-full flex items-center gap-2.5 px-3 py-2 rounded-[5px] text-[11px] font-bold cursor-pointer transition-all ${
                        selectedTab === 'dashboard' 
                          ? 'bg-[#FF6600] text-white shadow-sm' 
                          : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                      }`}
                      id="laptop-tab-dashboard"
                    >
                      <LayoutDashboard className="w-3.5 h-3.5" />
                      {t('device.laptop.dashboard')}
                    </button>

                    <button
                      onClick={() => setSelectedTab('inventory')}
                      className={`w-full flex items-center gap-2.5 px-3 py-2 rounded-[5px] text-[11px] font-bold cursor-pointer transition-all ${
                        selectedTab === 'inventory' 
                          ? 'bg-[#FF6600] text-white shadow-sm' 
                          : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                      }`}
                      id="laptop-tab-inventory"
                    >
                      <Zap className="w-3.5 h-3.5" />
                      {t('device.laptop.pos')}
                    </button>

                    <a href="#analytics" className="w-full flex items-center gap-2.5 px-3 py-2 rounded-[5px] text-[11px] text-gray-600 font-bold cursor-pointer hover:bg-gray-100 hover:text-gray-900">
                      <Banknote className="w-3.5 h-3.5 text-gray-400" />
                      {t('device.laptop.selling_history')}
                    </a>

                    <div className="pt-3 px-3 pb-1 text-[8px] font-bold text-gray-400 tracking-wider uppercase">
                      {t('device.laptop.inventory')}
                    </div>

                    <div className="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-[5px] text-[11px] text-gray-500 font-bold cursor-default">
                      <Archive className="w-3.5 h-3.5 text-gray-400" />
                      {t('device.laptop.product')}
                    </div>
                    <div className="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-[5px] text-[11px] text-gray-500 font-bold cursor-default">
                      <Layers className="w-3.5 h-3.5 text-gray-400" />
                      {t('device.laptop.category')}
                    </div>

                    <div className="pt-3 px-3 pb-1 text-[8px] font-bold text-gray-400 tracking-wider uppercase">
                      {t('device.laptop.other')}
                    </div>

                    <div className="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-[5px] text-[11px] text-gray-400 font-bold cursor-not-allowed">
                      <Users className="w-3.5 h-3.5 text-gray-300" />
                      {t('device.laptop.member')}
                    </div>
                    <div className="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-[5px] text-[11px] text-gray-400 font-bold cursor-not-allowed">
                      <CreditCard className="w-3.5 h-3.5 text-gray-300" />
                      {t('device.laptop.payment_method')}
                    </div>
                    <div className="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-[5px] text-[11px] text-gray-400 font-bold cursor-not-allowed">
                      <FileText className="w-3.5 h-3.5 text-gray-300" />
                      {t('device.laptop.report')}
                    </div>
                    <div className="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-[5px] text-[11px] text-gray-400 font-bold cursor-not-allowed">
                      <Settings className="w-3.5 h-3.5 text-gray-300" />
                      {t('device.laptop.general_setting')}
                    </div>
                  </nav>
                </div>

                {/* Bottom staff info */}
                <div className="p-4 border-t border-gray-200">
                  <div className="flex items-center gap-2">
                    <div className="w-7 h-7 rounded-full bg-[#FF6600]/10 border border-[#FF6600]/20 flex items-center justify-center text-[10px] font-bold text-[#FF6600]">
                      AS
                    </div>
                    <div>
                      <h5 className="text-[10px] font-bold leading-tight truncate text-gray-900">Amanda Setiadi</h5>
                      <p className="text-[8px] text-gray-500">Admin • Kopi S&S</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Main Dashboard Panel Workspace */}
              <div className="flex-1 bg-gray-50 flex flex-col h-full overflow-hidden text-gray-800">
                {/* Top Workspace Bar */}
                <div className="p-4 bg-white border-b border-gray-200 flex items-center justify-between gap-4">
                  <div>
                    <h2 className="text-xs font-bold text-gray-900 tracking-widest uppercase">
                      {selectedTab === 'dashboard' ? t('device.laptop.dashboard_title') : t('device.laptop.inventory_title')}
                    </h2>
                    <p className="text-[9px] text-gray-500 mt-0.5">{t('device.laptop.management_system')}</p>
                  </div>

                  {selectedTab === 'dashboard' ? (
                    <div className="flex items-center gap-2.5">
                      <button 
                        onClick={() => setTotalSales(prev => prev + 120000)}
                        className="bg-white border border-gray-200 text-gray-700 text-[10px] font-bold uppercase px-3 py-1.5 rounded-[5px] hover:bg-gray-50 transition-all cursor-pointer"
                      >
                        {t('device.laptop.simulate_transaction')}
                      </button>
                    </div>
                  ) : (
                    <span className="text-[10px] text-red-600 font-semibold bg-red-50 border border-red-200 px-2.5 py-1.5 rounded-[5px] flex items-center gap-1.5 uppercase tracking-wide">
                      <AlertTriangle className="w-3.5 h-3.5" />
                      {t('device.laptop.stock_low')}
                    </span>
                  )}
                </div>

                {/* Inner scrollable area */}
                <div className="p-4 flex-1 overflow-y-auto space-y-4 no-scrollbar">
                  
                  {selectedTab === 'dashboard' ? (
                    /* TAB 1: DASHBOARD REALTIME OVERVIEW */
                    <>
                      {/* Realtime KPI stats blocks — matches real BalanceWidget + SellingOverview */}
                      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div className="bg-[#FF6600]/10 border border-[#FF6600]/20 p-3 rounded-[6px]">
                          <span className="text-[9px] text-[#FF6600] font-bold uppercase tracking-wide">{t('device.phone.balance')}</span>
                          <p className="text-sm font-mono font-bold text-[#FF6600] mt-1">
                            Rp 4.250.000
                          </p>
                          <span className="text-[8px] text-emerald-600 font-semibold block mt-1.5 flex items-center gap-0.5">
                            <ArrowUpRight className="w-2.5 h-2.5" /> +14.2% {t('device.laptop.vs_yesterday')}
                          </span>
                        </div>

                        <div className="bg-white border border-gray-200 p-3 rounded-[6px]">
                          <span className="text-[9px] text-gray-500 font-bold uppercase tracking-wide">{t('device.phone.today_revenue')}</span>
                          <p className="text-sm font-mono font-bold text-gray-900 mt-1">
                            {formatIDR(totalSales)}
                          </p>
                          <span className="text-[8px] text-emerald-600 font-semibold block mt-1.5 flex items-center gap-0.5">
                            <ArrowUpRight className="w-2.5 h-2.5" /> +8.5% {t('device.laptop.this_hour')}
                          </span>
                        </div>

                        <div className="bg-white border border-gray-200 p-3 rounded-[6px]">
                          <span className="text-[9px] text-gray-500 font-bold uppercase tracking-wide">{t('device.laptop.sales_today')}</span>
                          <p className="text-sm font-mono font-bold text-gray-900 mt-1">
                            284 Transaksi
                          </p>
                          <span className="text-[8px] text-gray-500 font-semibold block mt-1.5">
                            {t('device.laptop.stable')}
                          </span>
                        </div>

                        <div className="bg-white border border-gray-200 p-3 rounded-[6px]">
                          <span className="text-[9px] text-gray-500 font-bold uppercase tracking-wide">{t('device.laptop.discount_today')}</span>
                          <p className="text-sm font-mono font-bold text-gray-900 mt-1">
                            Rp 125.000
                          </p>
                          <span className="text-[8px] text-gray-500 font-semibold block mt-1.5">
                            {t('device.laptop.total_discount')}
                          </span>
                        </div>
                      </div>

                      {/* Main Analytics Graphs Section */}
                      <div className="grid grid-cols-1 lg:grid-cols-3 gap-3">
                        
                        {/* Hourly Sales Area Chart curve */}
                        <div className="bg-white border border-gray-200 p-3.5 rounded-[6px] lg:col-span-2">
                          <div className="flex items-center justify-between mb-3">
                            <div>
                              <h4 className="text-xs font-bold text-gray-900">{t('device.laptop.sales_chart_title')}</h4>
                              <p className="text-[8px] text-gray-500 mt-0.5">{t('device.laptop.sales_chart_sub')}</p>
                            </div>
                            <span className="text-[9px] font-semibold text-gray-500 border border-gray-200 px-2 py-0.5 rounded">
                              {t('device.laptop.today')}
                            </span>
                          </div>

                          <div className="h-44 w-full select-none text-[9px]">
                            <ResponsiveContainer width="100%" height="100%">
                              <AreaChart data={HOURLY_SALES} margin={{ top: 5, right: 10, left: -20, bottom: 0 }}>
                                <defs>
                                  <linearGradient id="salesGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#FF6600" stopOpacity={0.2} />
                                    <stop offset="95%" stopColor="#FF6600" stopOpacity={0} />
                                  </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
                                <XAxis dataKey="time" stroke="#9ca3af" />
                                <YAxis stroke="#9ca3af" tickFormatter={(v) => `${v/1000}k`} />
                                <Tooltip 
                                  contentStyle={{ backgroundColor: '#ffffff', borderColor: '#e5e7eb', borderRadius: '6px' }}
                                  labelStyle={{ color: '#6b7280', fontWeight: 'bold', fontSize: '9px' }}
                                />
                                <Area 
                                  type="monotone" 
                                  dataKey="sales" 
                                  stroke="#FF6600" 
                                  strokeWidth={1.5} 
                                  fillOpacity={1} 
                                  fill="url(#salesGrad)" 
                                />
                              </AreaChart>
                            </ResponsiveContainer>
                          </div>
                        </div>

                        {/* Best Selling Products bar list */}
                        <div className="bg-white border border-gray-200 p-3.5 rounded-[6px] flex flex-col justify-between">
                          <div>
                            <h4 className="text-xs font-bold text-gray-900">{t('device.laptop.best_seller_title')}</h4>
                            <p className="text-[8px] text-gray-500 mt-0.5">{t('device.laptop.best_seller_sub')}</p>
                          </div>

                          <div className="space-y-2 mt-4 flex-1 flex flex-col justify-center">
                            {BEST_SELLERS.map((item, idx) => (
                              <div key={item.name} className="space-y-1">
                                <div className="flex justify-between text-[10px] text-gray-600">
                                  <span className="font-semibold">{item.name}</span>
                                  <span className="font-mono font-bold text-gray-900">{item.sales} Pcs</span>
                                </div>
                                <div className="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                  <div 
                                    className="bg-[#FF6600] h-full rounded-full" 
                                    style={{ width: `${(item.sales / BEST_SELLERS[0].sales) * 100}%` }}
                                  />
                                </div>
                              </div>
                            ))}
                          </div>

                          <div className="text-[9px] text-gray-500 pt-2 border-t border-gray-200 flex items-center justify-between mt-2">
                            <span>{t('device.laptop.stable')}</span>
                            <span className="font-bold flex items-center gap-0.5 text-emerald-600">
                              98% {t('device.laptop.accuracy')}
                            </span>
                          </div>
                        </div>

                      </div>

                      {/* Activity and alerts footer logs */}
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        
                        {/* Aliran Stok - Stock movement list */}
                        <div className="bg-white border border-gray-200 p-3 rounded-[6px]">
                          <div className="flex justify-between items-center mb-2 pb-2 border-b border-gray-200">
                            <span className="text-[10px] font-bold text-gray-900 uppercase tracking-wider flex items-center gap-1.5">
                              <Box className="w-3.5 h-3.5 text-gray-500" /> {t('device.laptop.stock_movement')}
                            </span>
                            <span className="text-[8px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded font-mono uppercase">{t('device.laptop.main_warehouse')}</span>
                          </div>

                          <div className="space-y-2 h-28 overflow-y-auto no-scrollbar">
                            {movements.map((m) => (
                              <div key={m.id} className="flex justify-between items-center text-[10px] py-1 border-b border-gray-100 last:border-b-0">
                                <div className="flex items-center gap-2">
                                  {m.type === 'IN' ? (
                                    <ArrowDownLeft className="w-3.5 h-3.5 text-emerald-600 bg-emerald-50 p-0.5 rounded-sm" />
                                  ) : (
                                    <ArrowUpRight className="w-3.5 h-3.5 text-amber-600 bg-amber-50 p-0.5 rounded-sm" />
                                  )}
                                  <div>
                                    <span className="font-semibold text-gray-700">{m.productName}</span>
                                    <span className="text-[8px] text-gray-500 ml-1.5">SKU {m.sku}</span>
                                  </div>
                                </div>
                                <div className="text-right">
                                  <span className={`font-semibold ${m.type === 'IN' ? 'text-emerald-600' : 'text-amber-600'}`}>
                                    {m.type === 'IN' ? '+' : '-'}{m.quantity} {t('device.pcs')}
                                  </span>
                                  <span className="text-[8px] text-gray-500 block">{m.timestamp}</span>
                                </div>
                              </div>
                            ))}
                          </div>
                        </div>

                        {/* Stock Alert Quick Click Restock Actions */}
                        <div className="bg-white border border-gray-200 p-3 rounded-[6px] flex flex-col justify-between">
                          <div>
                            <div className="flex justify-between items-center mb-2 pb-2 border-b border-gray-200">
                              <span className="text-[10px] font-bold text-gray-900 uppercase tracking-wider flex items-center gap-1.5">
                                <AlertTriangle className="w-3.5 h-3.5 text-red-500 animate-pulse" /> {t('device.laptop.restock_alert_title')}
                              </span>
                            </div>

                            <div className="space-y-1.5 h-28 overflow-y-auto no-scrollbar">
                              {products.map(p => {
                                const isLow = p.stock <= 18;
                                if (!isLow) return null;
                                return (
                                  <div key={p.id} className="flex justify-between items-center bg-red-50 p-2 border border-red-200 rounded-[4px]">
                                    <div>
                                      <div className="text-[10px] font-semibold text-gray-700 leading-tight">{p.name}</div>
                                      <div className="text-[8px] text-red-600 mt-0.5">{t('device.laptop.remaining_stock')}: {p.stock} {t('device.pcs')} • {t('device.laptop.safety_limit')} 18</div>
                                    </div>
                                    <button
                                      onClick={() => triggerRestock(p.id)}
                                      className="text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 px-2.5 py-1 rounded-[4px] cursor-pointer active:scale-95 transition-all"
                                    >
                                      {t('device.laptop.restock_btn')}
                                    </button>
                                  </div>
                                );
                              })}
                            </div>
                          </div>
                        </div>

                      </div>
                    </>
                  ) : (
                    /* TAB 2: FULL INVENTORY LIST WITH REAL TIME ACTIONS */
                    <div className="bg-white border border-gray-200 rounded-[6px] overflow-hidden">
                      <table className="w-full text-left border-collapse text-[11px]">
                        <thead>
                          <tr className="bg-gray-50 text-gray-500 border-b border-gray-200">
                            <th className="p-3 font-semibold text-[10px] uppercase">{t('device.laptop.sku')}</th>
                            <th className="p-3 font-semibold text-[10px] uppercase">{t('device.laptop.name')}</th>
                            <th className="p-3 font-semibold text-[10px] uppercase">{t('device.laptop.category')}</th>
                            <th className="p-3 font-semibold text-[10px] uppercase text-right">{t('device.laptop.price')}</th>
                            <th className="p-3 font-semibold text-[10px] uppercase text-center">{t('device.laptop.warehouse_stock')}</th>
                            <th className="p-3 font-semibold text-[10px] uppercase text-center">{t('device.laptop.status')}</th>
                            <th className="p-3 font-semibold text-[10px] uppercase text-center">{t('device.laptop.action')}</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                          {products.map(p => {
                            const isLow = p.stock <= 18;
                            const isCritical = p.stock <= 4;
                            return (
                              <tr key={p.id} className="hover:bg-gray-50">
                                <td className="p-3 font-mono text-[10px] text-gray-500">{p.sku}</td>
                                <td className="p-3 font-bold text-gray-900">{p.name}</td>
                                <td className="p-3 text-gray-500">{p.category}</td>
                                <td className="p-3 text-right font-mono text-gray-700">
                                  Rp {p.price.toLocaleString('id-ID')}
                                </td>
                                <td className="p-3 text-center font-mono font-bold text-gray-900">
                                  {p.stock} {t('device.pcs')}
                                </td>
                                <td className="p-3 text-center">
                                  {isCritical ? (
                                    <span className="bg-red-50 text-red-600 border border-red-200 px-2 py-0.5 rounded-[4px] font-bold text-[8px] uppercase">{t('device.laptop.status_critical')}</span>
                                  ) : isLow ? (
                                    <span className="bg-amber-50 text-amber-600 border border-amber-200 px-2 py-0.5 rounded-[4px] font-bold text-[8px] uppercase">{t('device.laptop.status_low')}</span>
                                  ) : (
                                    <span className="bg-emerald-50 text-emerald-600 border border-emerald-200 px-2 py-0.5 rounded-[4px] font-bold text-[8px] uppercase">{t('device.laptop.status_safe')}</span>
                                  )}
                                </td>
                                <td className="p-3 text-center">
                                  <button
                                    onClick={() => triggerRestock(p.id)}
                                    className="bg-gray-900 hover:bg-gray-800 text-white px-3 py-1 rounded-[4px] text-[10px] font-semibold active:scale-95 transition-all cursor-pointer"
                                  >
                                    {t('device.laptop.restock_btn')}
                                  </button>
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                      </table>
                    </div>
                  )}

                </div>
              </div>

            </div>
          </div>

          {/* Laptop MacBook Premium Keyboard Base with sleek premium silver look */}
          <div className="relative w-[101.5%] -ml-[0.75%] h-5 bg-gradient-to-b from-[#E0E0E0] via-[#EDEDED] to-[#AEAEAE] rounded-b-[16px] border-t border-white shadow-2xl flex justify-center items-start z-20">
            {/* Trackpad Indent */}
            <div className="w-28 h-[10px] bg-gradient-to-b from-[#CCCCCC] to-[#DFDFDF] border border-gray-400/50 rounded-b-[5px] -mt-[1px] border-t-0 shadow-inner" />
          </div>

        </div>
      </div>
      
      <style>{`
        .bg-gray-905\\/70 {
          background-color: rgba(18, 18, 20, 0.72);
        }
      `}</style>
    </div>
  );
}
