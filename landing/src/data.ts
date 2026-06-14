/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { Product, StockMovement, Testimonial, SalesData, BestSeller } from './types';

export const INITIAL_PRODUCTS: Product[] = [
  { 
    id: '1', 
    sku: 'ZK-KSA-001', 
    name: 'Kopi Susu Gula Aren', 
    category: 'Minuman', 
    price: 22000, 
    stock: 124,
    image: 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=350&h=300'
  },
  { 
    id: '2', 
    sku: 'ZK-ACR-002', 
    name: 'Almond Croissant', 
    category: 'Makanan', 
    price: 28000, 
    stock: 18,
    image: 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&q=80&w=350&h=300'
  },
  { 
    id: '3', 
    sku: 'ZK-UML-003', 
    name: 'Uji Matcha Latte', 
    category: 'Minuman', 
    price: 26000, 
    stock: 84,
    image: 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&q=80&w=350&h=300'
  },
  { 
    id: '4', 
    sku: 'ZK-RBC-004', 
    name: 'Roti Bakar Cokelat Keju', 
    category: 'Makanan', 
    price: 24000, 
    stock: 45,
    image: 'https://images.unsplash.com/photo-1587314168485-3236d6710814?auto=format&fit=crop&q=80&w=350&h=300'
  },
  { 
    id: '5', 
    sku: 'ZK-PJT-005', 
    name: 'Peach Jasmine Tea', 
    category: 'Minuman', 
    price: 20000, 
    stock: 92,
    image: 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&q=80&w=350&h=300'
  },
  { 
    id: '6', 
    sku: 'ZK-CCC-006', 
    name: 'Chocolate Chip Cookie', 
    category: 'Makanan', 
    price: 15000, 
    stock: 4,
    image: 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&q=80&w=350&h=300'
  },
];

export const STOCK_MOVEMENTS: StockMovement[] = [
  { id: 'm1', timestamp: '10:42', productName: 'Kopi Susu Gula Aren', sku: 'ZK-KSA-001', type: 'OUT', quantity: 2, status: 'Complete' },
  { id: 'm2', timestamp: '10:38', productName: 'Almond Croissant', sku: 'ZK-ACR-002', type: 'OUT', quantity: 1, status: 'Complete' },
  { id: 'm3', timestamp: '10:15', productName: 'Kopi Susu Gula Aren', sku: 'ZK-KSA-001', type: 'IN', quantity: 50, status: 'Complete' },
  { id: 'm4', timestamp: '09:45', productName: 'Chocolate Chip Cookie', sku: 'ZK-CCC-006', type: 'OUT', quantity: 3, status: 'Complete' },
  { id: 'm5', timestamp: '09:00', productName: 'Uji Matcha Latte', sku: 'ZK-UML-003', type: 'IN', quantity: 30, status: 'Complete' },
];

export const TESTIMONIALS: Testimonial[] = [
  {
    id: 't1',
    name: 'Amanda Setiadi',
    role: 'Founder & Head Barista',
    businessName: 'Kopi Sana-Sini',
    businessType: 'Espresso Bar & Roastery',
    location: 'Senopati, Jakarta Selatan',
    quote: 'ZonaKasir mengubah cara kami mengendalikan bisnis harian. Antrean kasir berkurang 40% dan stok biji kopi kami selalu ter-update secara presisi tanpa perlu stock opname manual setiap malam.',
    imagePath: '/images/landing/cafe_owner_photo_1781378979010.jpg'
  },
  {
    id: 't2',
    name: 'Budi Hartono',
    role: 'Pemilik Toko',
    businessName: 'Minimarket Sentosa',
    businessType: 'Retail & Bahan Pokok',
    location: 'Dago, Bandung',
    quote: 'Sangat mudah melacak barang laku keras dan yang mengendap di gudang. Notifikasi stok menipis menyelamatkan omset harian kami sebelum barang benar-benar habis di rak.',
    imagePath: '/images/landing/storefront_shelf_1781378993505.jpg'
  },
  {
    id: 't3',
    name: 'Rena Sastro',
    role: 'Creative Director',
    businessName: 'Puan Atelier',
    businessType: 'Fashion & Curated Goods',
    location: 'Seminyak, Bali',
    quote: 'Kami menyukai desain antarmukanya yang sangat bersih dan profesional. Mengelola multi-device di butik Seminyak & Jakarta terasa serempak dan termonitor secara real-time.',
    imagePath: '/images/landing/storefront_shelf_1781378993505.jpg'
  }
];

export const HOURLY_SALES: SalesData[] = [
  { time: '08:00', sales: 420000, transactions: 15 },
  { time: '10:00', sales: 980000, transactions: 34 },
  { time: '12:00', sales: 1850000, transactions: 65 },
  { time: '14:00', sales: 1200000, transactions: 44 },
  { time: '16:00', sales: 1450000, transactions: 51 },
  { time: '18:00', sales: 2400000, transactions: 88 },
  { time: '20:00', sales: 1100000, transactions: 39 },
];

export const BEST_SELLERS: BestSeller[] = [
  { name: 'Kopi Susu Aren', sales: 342, revenue: 7524000 },
  { name: 'Uji Matcha Latte', sales: 184, revenue: 4784000 },
  { name: 'Almond Croissant', sales: 112, revenue: 3136000 },
  { name: 'Roti Bakar Keju', sales: 95, revenue: 2280000 },
];
