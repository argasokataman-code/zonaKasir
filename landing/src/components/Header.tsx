/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { Menu, X, ArrowRight, Check } from 'lucide-react';

interface HeaderProps {
  activeSection: number;
  scrollToSection: (index: number) => void;
}

export default function Header({ activeSection, scrollToSection }: HeaderProps) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  const navItems = [
    { label: 'Beranda', section: 0 },
    { label: 'Transaksi', section: 1 },
    { label: 'Manajemen Stok', section: 2 },
    { label: 'Sinkronisasi', section: 3 },
    { label: 'Analitik Usaha', section: 4 },
    { label: 'Testimoni', section: 5 },
    { label: 'FAQ', section: 6 },
    { label: 'Galeri', section: 7 },
    { label: 'Paket', section: 8 },
  ];

  const handleNavClick = (sectionIndex: number) => {
    scrollToSection(sectionIndex);
    setIsMobileMenuOpen(false);
  };

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-200/80 transition-all duration-300 h-16 md:h-18">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 h-full flex items-center justify-between">
        {/* Brand Logo & Name */}
        <div 
          onClick={() => handleNavClick(0)} 
          className="flex items-center gap-2 px-1 cursor-pointer group select-none"
          id="header-logo"
        >
          <div className="w-8 h-8 bg-gray-900 rounded-[6px] flex items-center justify-center transition-transform group-hover:scale-105 shadow-sm">
            <div className="w-4 h-4 border-2 border-white rounded-[2px]" />
          </div>
          <span className="font-sans font-extrabold text-lg tracking-tight text-gray-900 group-hover:text-gray-600 transition-colors uppercase">
            Zonakasir
          </span>
        </div>

        {/* Desktop Navigation Links */}
        <nav className="hidden lg:flex items-center gap-5 xl:gap-6">
          {navItems.map((item) => {
            const isActive = activeSection === item.section;
            return (
              <button
                key={item.label}
                onClick={() => handleNavClick(item.section)}
                className={`font-sans text-[11px] font-bold uppercase tracking-wider relative py-1 cursor-pointer transition-all duration-200 ${
                  isActive 
                    ? 'text-gray-900 scale-102' 
                    : 'text-gray-500 hover:text-gray-900'
                }`}
                id={`nav-item-${item.section}`}
              >
                {item.label}
                {isActive && (
                  <span className="absolute -bottom-1.5 left-0 w-full h-[2.5px] bg-gray-900 rounded-full animate-fade-in" />
                )}
              </button>
            );
          })}
        </nav>

        {/* CTA Actions */}
        <div className="hidden sm:flex items-center gap-3">
          <button 
            onClick={() => handleNavClick(9)}
            className="text-[12px] font-bold uppercase tracking-wider px-3.5 py-2 text-gray-500 hover:text-gray-900 cursor-pointer transition-colors"
            id="header-demo-btn"
          >
            Masuk
          </button>
          <button 
            onClick={() => handleNavClick(9)}
            className="bg-gray-900 text-white text-[11px] font-bold uppercase tracking-widest px-5 py-2.5 rounded-[6px] shadow-sm hover:bg-gray-800 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer"
            id="header-cta-btn"
          >
            Coba Gratis
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        </div>

        {/* Hamburger Mobile Menu Toggle Button */}
        <div className="flex items-center lg:hidden gap-2">
          {/* Mobile CTA: Simple styled quick start button */}
          <button 
            onClick={() => handleNavClick(9)}
            className="sm:hidden bg-gray-900 text-white text-[9.5px] font-bold uppercase tracking-wider px-3.5 py-2 rounded-[5px] active:scale-95 transition-all text-center"
          >
            Coba Gratis
          </button>

          <button
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="p-1.5 rounded-full text-gray-700 hover:bg-gray-100 cursor-pointer transition-colors"
            aria-label="Toggle Menu"
          >
            {isMobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </div>

      {/* Slide-Down Mobile Navigation Drawer with elegant backdrop-blur */}
      {isMobileMenuOpen && (
        <div className="lg:hidden fixed inset-0 top-16 bg-slate-900/50 backdrop-blur-xs z-40 transition-opacity">
          <div className="bg-white border-b border-gray-200 shadow-xl p-6 space-y-4 animate-slide-down">
            <span className="text-[9px] font-bold text-gray-400 uppercase tracking-widest block">Menu Navigasi</span>
            <div className="grid grid-cols-2 gap-2">
              {navItems.map((item) => {
                const isActive = activeSection === item.section;
                return (
                  <button
                    key={item.label}
                    onClick={() => handleNavClick(item.section)}
                    className={`flex items-center gap-2 p-2.5 rounded-[6px] text-[11px] font-bold uppercase tracking-wider text-left transition-all cursor-pointer ${
                      isActive 
                        ? 'bg-gray-900 text-white shadow-sm' 
                        : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                    }`}
                  >
                    {isActive && <span className="w-1.5 h-1.5 rounded-full bg-white block" />}
                    {item.label}
                  </button>
                );
              })}
            </div>

            <div className="pt-4 border-t border-gray-100 flex flex-col gap-2">
              <button 
                onClick={() => handleNavClick(9)}
                className="w-full text-center py-2.5 text-xs font-bold text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-[6px] transition-all"
              >
                MASUK PORTAL KASIR
              </button>
              <button 
                onClick={() => handleNavClick(9)}
                className="w-full text-center py-3 text-xs font-bold text-white bg-gray-900 hover:bg-gray-800 rounded-[6px] transition-all shadow-sm flex items-center justify-center gap-1"
              >
                COBA GRATIS SEKARANG
                <ArrowRight className="w-3.5 h-3.5 animate-pulse" />
              </button>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
