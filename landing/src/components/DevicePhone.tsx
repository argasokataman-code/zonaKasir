/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React from 'react';
import { Smartphone, ChevronRight, CircleUser, ShoppingBag, BellRing, TrendingUp, DollarSign } from 'lucide-react';
import { Product } from '../types';
import { INITIAL_PRODUCTS } from '../data';

interface DevicePhoneProps {
  lastTransactionTotal?: number;
  lastPaymentMethod?: string;
  isSyncd?: boolean;
}

export default function DevicePhone({ lastTransactionTotal = 58000, lastPaymentMethod = 'QRIS', isSyncd = true }: DevicePhoneProps) {
  return (
    <div className="w-[280px] mx-auto scale-95 md:scale-100 transition-all font-sans" id="phone-pos-wrapper">
      {/* Phone Outer Shell - Pristine Minimalist Frame */}
      <div className="bg-[#1A1A1A] rounded-[24px] p-2.5 shadow-2xl border border-gray-800 relative">
        {/* Phone Speaker Notch */}
        <div className="absolute top-4 left-1/2 -translate-x-1/2 w-16 h-3.5 bg-[#1A1A1A] rounded-full z-40 flex items-center justify-center">
          <div className="w-6 h-0.5 bg-gray-700 rounded-full" />
        </div>

        {/* Screen Area with Light Harmonized Theme */}
        <div className="bg-white rounded-[18px] overflow-hidden flex flex-col h-[460px] text-[#1A1A1A] relative border border-gray-200">
          
          {/* Status Bar */}
          <div className="h-8 px-4 pt-3 flex items-center justify-between text-[10px] text-gray-500 font-semibold select-none">
            <span>10.45</span>
            <div className="flex items-center gap-1 text-[9px] tracking-wider text-[#1A1A1A]">
              <span>4G</span>
              <div className="w-5 h-2.5 bg-gray-150 rounded-xs p-0.5 border border-gray-200">
                <div className="h-full w-[85%] bg-[#1A1A1A] rounded-xs" />
              </div>
            </div>
          </div>

          {/* Clean App Header containing identical brand elements */}
          <div className="px-4 py-2 border-b border-[#E5E5E1] flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div className="w-5 h-5 bg-[#FF6600] rounded-[4px] flex items-center justify-center text-white font-black text-[9px]">
                ZK
              </div>
              <div>
                <h4 className="text-[10px] font-bold tracking-tight text-[#1A1A1A]">ZonaKasir</h4>
                <p className="text-[8px] text-[#666666]">Laporan Cabang Utama</p>
              </div>
            </div>
            
            <span className="text-[8px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded-[4px] uppercase tracking-wide">
              Aktif
            </span>
          </div>

          {/* Dashboard reports and feeds */}
          <div className="flex-1 overflow-y-auto p-3.5 space-y-3.5 no-scrollbar bg-[#F4F4F2]">
            
            {/* Saldo Tersedia KPI Box — matches real BalanceWidget */}
            <div className="bg-white p-3 rounded-[6px] border border-[#E5E5E1] shadow-xs">
              <div className="flex items-center justify-between text-[#666666] text-[8px] font-bold uppercase tracking-wider">
                <span>Saldo Tersedia</span>
                <DollarSign className="w-3.5 h-3.5 text-[#FF6600]" />
              </div>
              <div className="mt-1 flex items-baseline">
                <span className="text-[17px] font-mono font-extrabold tracking-tight text-[#FF6600]">
                  Rp 4.250.000
                </span>
              </div>
              <div className="flex items-center justify-between text-[8px] text-[#666666] mt-2.5 pt-2 border-t border-dashed border-[#E5E5E1]">
                <span>Today Revenue: <strong className="text-[#1A1A1A] font-bold">Rp 12.840.000</strong></span>
                <span className="text-emerald-700 font-bold">+12%</span>
              </div>
            </div>

            {/* Realtime sales activity logs */}
            <div className="space-y-1.5">
              <span className="text-[8px] text-[#666666] font-bold uppercase tracking-wider block px-1">
                Laporan Penjualan Baru
              </span>
              
              <div className="bg-white rounded-[6px] border border-[#E5E5E1] p-2 divide-y divide-gray-100 divide-dashed shadow-xs">
                {/* Active Sync transaction Item */}
                {lastTransactionTotal > 0 && (
                  <div className="flex justify-between items-center py-2 animate-pulse-soft">
                    <div>
                      <div className="text-[9px] font-bold text-[#1A1A1A] flex items-center gap-1">
                        <span>#ZK-TRX-285</span>
                        <span className="text-[7px] text-[#888888] font-mono">Baru</span>
                      </div>
                      <div className="text-[8px] text-gray-400 mt-0.5">Sandi • {lastPaymentMethod}</div>
                    </div>
                    <span className="font-mono text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                      +Rp {lastTransactionTotal.toLocaleString('id-ID')}
                    </span>
                  </div>
                )}

                {/* Static Transaction Stream items */}
                <div className="flex justify-between items-center py-2 text-[9px]">
                  <div>
                    <div className="font-bold text-[#1A1A1A]">#ZK-TRX-284</div>
                    <div className="text-[8px] text-[#666666] mt-0.5">Andri • QRIS • 10.35</div>
                  </div>
                  <span className="font-mono font-bold text-[#1A1A1A]">
                    +Rp 50.000
                  </span>
                </div>

                <div className="flex justify-between items-center py-2 text-[9px]">
                  <div>
                    <div className="font-bold text-[#1A1A1A]">#ZK-TRX-283</div>
                    <div className="text-[8px] text-[#666666] mt-0.5">Ranti • Tunai • 10.14</div>
                  </div>
                  <span className="font-mono font-bold text-[#1A1A1A]">
                    +Rp 120.000
                  </span>
                </div>
              </div>
            </div>

            {/* In-app stock alerts display list */}
            <div className="space-y-1.5">
              <span className="text-[8px] text-[#666666] font-bold uppercase tracking-wider block px-1">
                Pantau Stok Kritis
              </span>
              <div className="grid grid-cols-2 gap-2">
                <div className="bg-red-50 border border-red-200 p-2 rounded-[6px]">
                  <div className="text-[9px] font-bold text-[#1A1A1A] truncate">Almond Croissant</div>
                  <div className="flex justify-between items-center text-[8px] text-red-700 font-semibold mt-1">
                    <span>SKU ZK-002</span>
                    <span>18 Pcs</span>
                  </div>
                </div>

                <div className="bg-amber-50 border border-amber-200 p-2 rounded-[6px]">
                  <div className="text-[9px] font-bold text-[#1A1A1A] truncate">Chocolate Cookie</div>
                  <div className="flex justify-between items-center text-[8px] text-amber-700 font-semibold mt-1">
                    <span>SKU ZK-006</span>
                    <span>4 Pcs</span>
                  </div>
                </div>
              </div>
            </div>

          </div>

          {/* Safe humanized bottom navigation bar */}
          <div className="h-11 bg-white border-t border-[#E5E5E1] flex items-center justify-around px-2 text-[#666666] text-[8px] select-none">
            <button className="flex flex-col items-center gap-0.5 text-[#FF6600] font-bold">
              <Smartphone className="w-3.5 h-3.5" />
              <span>Ringkasan</span>
            </button>
            <button className="flex flex-col items-center gap-0.5 hover:text-[#1A1A1A]">
              <ShoppingBag className="w-3.5 h-3.5" />
              <span>Laporan</span>
            </button>
            <button className="flex flex-col items-center gap-0.5 hover:text-[#1A1A1A] relative">
              <BellRing className="w-3.5 h-3.5" />
              <span>Notifikasi</span>
              <span className="absolute top-0 right-3.5 w-1.5 h-1.5 bg-red-600 rounded-full" />
            </button>
            <button className="flex flex-col items-center gap-0.5 hover:text-[#1A1A1A]">
              <CircleUser className="w-3.5 h-3.5" />
              <span>Profil</span>
            </button>
          </div>

        </div>
      </div>

      <style>{`
        @keyframes pulseSoft {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.85; }
        }
        .animate-pulse-soft {
          animation: pulseSoft 2s ease-in-out infinite;
        }
      `}</style>
    </div>
  );
}
