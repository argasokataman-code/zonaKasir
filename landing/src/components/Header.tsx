/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useRef, useEffect } from 'react';
import { Menu, X, Check, Globe, ChevronDown } from 'lucide-react';
import { useLanguage, LANGUAGES, type LangCode } from '../i18n';

interface HeaderProps {
  activeSection: number;
  scrollToSection: (index: number) => void;
}

export default function Header({ activeSection, scrollToSection }: HeaderProps) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [langOpen, setLangOpen] = useState(false);
  const langRef = useRef<HTMLDivElement>(null);
  const { lang, setLang, t } = useLanguage();

  const currentLang = LANGUAGES.find((l) => l.code === lang) ?? LANGUAGES[0];

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (langRef.current && !langRef.current.contains(e.target as Node)) {
        setLangOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const navItems = [
    { key: 'nav.home', section: 0 },
    { key: 'nav.transaction', section: 1 },
    { key: 'nav.stock', section: 2 },
    { key: 'nav.sync', section: 3 },
    { key: 'nav.analytics', section: 4 },
    { key: 'nav.testimonial', section: 5 },
    { key: 'nav.faq', section: 6 },
    { key: 'nav.gallery', section: 7 },
    { key: 'nav.pricing', section: 8 },
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
                key={item.key}
                onClick={() => handleNavClick(item.section)}
                className={`font-sans text-[11px] font-bold uppercase tracking-wider relative py-1 cursor-pointer transition-all duration-200 ${
                  isActive 
                    ? 'text-gray-900 scale-102' 
                    : 'text-gray-500 hover:text-gray-900'
                }`}
                id={`nav-item-${item.section}`}
              >
                {t(item.key)}
                {isActive && (
                  <span className="absolute -bottom-1.5 left-0 w-full h-[2.5px] bg-gray-900 rounded-full animate-fade-in" />
                )}
              </button>
            );
          })}
        </nav>

        {/* CTA Actions */}
        <div className="hidden sm:flex items-center gap-3">
          {/* Language Dropdown */}
          <div className="relative" ref={langRef}>
            <button 
              onClick={() => setLangOpen(!langOpen)}
              className="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider px-2.5 py-1.5 rounded-[5px] border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 cursor-pointer transition-all"
              id="header-lang-toggle"
            >
              <span className="text-sm">{currentLang.flag}</span>
              {currentLang.code}
              <ChevronDown className={`w-3 h-3 transition-transform ${langOpen ? 'rotate-180' : ''}`} />
            </button>
            {langOpen && (
              <div className="absolute right-0 top-full mt-1 bg-white border border-gray-200 rounded-[6px] shadow-lg py-1 min-w-[180px] z-50">
                {LANGUAGES.map((l) => (
                  <button
                    key={l.code}
                    onClick={() => { setLang(l.code); setLangOpen(false); }}
                    className={`w-full flex items-center gap-2.5 px-3 py-2 text-[11px] font-medium cursor-pointer transition-all ${
                      lang === l.code
                        ? 'bg-[#FF6600]/5 text-[#FF6600] font-bold'
                        : 'text-gray-700 hover:bg-gray-50'
                    }`}
                  >
                    <span className="text-sm">{l.flag}</span>
                    <span className="flex-1 text-left">{l.label}</span>
                    {lang === l.code && <Check className="w-3.5 h-3.5" />}
                  </button>
                ))}
              </div>
            )}
          </div>
          <a 
            href="/member"
            className="text-[12px] font-bold uppercase tracking-wider px-3.5 py-2 text-gray-500 hover:text-gray-900 cursor-pointer transition-colors"
            id="header-demo-btn"
          >
            {t('nav.login')}
          </a>
        </div>

        {/* Hamburger Mobile Menu Toggle Button */}
        <div className="flex items-center lg:hidden gap-2">
          {/* Mobile CTA: Simple styled quick start button */}
          <a 
            href="/member"
            className="sm:hidden bg-gray-900 text-white text-[9.5px] font-bold uppercase tracking-wider px-3.5 py-2 rounded-[5px] active:scale-95 transition-all text-center"
          >
            {t('nav.try_free')}
          </a>

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
            <span className="text-[9px] font-bold text-gray-400 uppercase tracking-widest block">{t('nav.menu')}</span>
            <div className="grid grid-cols-2 gap-2">
              {navItems.map((item) => {
                const isActive = activeSection === item.section;
                return (
                  <button
                    key={item.key}
                    onClick={() => handleNavClick(item.section)}
                    className={`flex items-center gap-2 p-2.5 rounded-[6px] text-[11px] font-bold uppercase tracking-wider text-left transition-all cursor-pointer ${
                      isActive 
                        ? 'bg-gray-900 text-white shadow-sm' 
                        : 'bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                    }`}
                  >
                    {isActive && <span className="w-1.5 h-1.5 rounded-full bg-white block" />}
                    {t(item.key)}
                  </button>
                );
              })}
            </div>

            <div className="pt-4 border-t border-gray-100 flex flex-col gap-2">
              {/* Language Toggle Mobile */}
              <button 
                onClick={() => setLang(lang === 'ID' ? 'EN' : 'ID')}
                className="w-full flex items-center justify-center gap-2 py-2.5 text-xs font-bold text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-[6px] transition-all"
              >
                <Globe className="w-4 h-4" />
                {lang === 'ID' ? 'English' : 'Bahasa Indonesia'}
              </button>
              <button 
                onClick={() => handleNavClick(9)}
                className="w-full text-center py-2.5 text-xs font-bold text-gray-600 bg-gray-50 hover:bg-gray-100 rounded-[6px] transition-all uppercase"
              >
                {t('nav.portal')}
              </button>
              <a 
                href="/member"
                className="w-full text-center py-3 text-xs font-bold text-white bg-gray-900 hover:bg-gray-800 rounded-[6px] transition-all shadow-sm flex items-center justify-center gap-1 uppercase"
              >
                {t('nav.try_now')}
              </a>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
