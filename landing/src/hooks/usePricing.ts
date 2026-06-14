import { useState, useEffect } from 'react';

export interface Plan {
  id: number;
  name: string;
  slug: string;
  price_monthly: number;
  price_yearly: number | null;
  features: Record<string, string>;
  max_stores: number;
  max_users: number;
  is_popular: boolean;
  cta: { label: string; action: string };
  is_on_premise?: boolean;
}

const API_URL = '/api/pricing';
const CACHE_KEY = 'pricing_cache_v2';

const FALLBACK_PLANS: Plan[] = [
  {
    id: 1, name: 'Paket Lite', slug: 'lite',
    price_monthly: 0, price_yearly: null,
    features: { pos: 'Point of Sale (POS)', print_selling_a5: 'Print Selling A5' },
    max_stores: 1, max_users: 1, is_popular: false,
    cta: { label: 'Daftar Gratis', action: 'register' },
  },
  {
    id: 2, name: 'Paket Pro', slug: 'pro',
    price_monthly: 149000, price_yearly: 119000,
    features: { pos: 'Point of Sale (POS)', report: 'Reports & Analytics', stock_management: 'Stock Management', voucher: 'Voucher / Discount', member_management: 'Member Management' },
    max_stores: 3, max_users: 5, is_popular: true,
    cta: { label: 'Coba Gratis 7 Hari', action: 'register' },
  },
  {
    id: 3, name: 'Enterprise', slug: 'enterprise',
    price_monthly: 299000, price_yearly: 239000,
    features: { pos: 'Point of Sale (POS)', report: 'Reports & Analytics', stock_management: 'Stock Management', api_access: 'API Access', multi_store: 'Multi Store' },
    max_stores: 99, max_users: 99, is_popular: false,
    cta: { label: 'Hubungi Sales', action: 'contact' },
  },
  {
    id: 4, name: 'On-Premise', slug: 'on-premise',
    price_monthly: 0, price_yearly: null,
    features: { pos: 'Point of Sale (POS)', report: 'Reports & Analytics', stock_management: 'Stock Management', multi_store: 'Multi Store', api_access: 'API Access' },
    max_stores: 99, max_users: 99, is_popular: false,
    cta: { label: 'Konsultasi Sekarang', action: 'contact' },
    is_on_premise: true,
  },
];

export function usePricing() {
  const [plans, setPlans] = useState<Plan[]>(FALLBACK_PLANS);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    const cached = localStorage.getItem(CACHE_KEY);
    if (cached) {
      try {
        const parsed = JSON.parse(cached);
        if (Date.now() - parsed.ts < 3600000) {
          setPlans(parsed.data);
          setLoading(false);
          return;
        }
      } catch { /* ignore */ }
    }

    fetch(API_URL)
      .then(r => r.json())
      .then(json => {
        if (cancelled) return;
        if (json.success && Array.isArray(json.data) && json.data.length > 0) {
          setPlans(json.data);
          localStorage.setItem('pricing_cache', JSON.stringify({ data: json.data, ts: Date.now() }));
        }
      })
      .catch(() => { /* use fallback */ })
      .finally(() => { if (!cancelled) setLoading(false); });

    return () => { cancelled = true; };
  }, []);

  return { plans, loading };
}
