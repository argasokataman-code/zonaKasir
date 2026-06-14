/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

export interface Product {
  id: string;
  sku: string;
  name: string;
  category: string;
  price: number;
  stock: number;
  image?: string;
}

export interface CartItem {
  product: Product;
  quantity: number;
}

export interface StockMovement {
  id: string;
  timestamp: string;
  productName: string;
  sku: string;
  type: 'IN' | 'OUT';
  quantity: number;
  status: 'Complete' | 'Pending';
}

export interface Testimonial {
  id: string;
  name: string;
  role: string;
  businessName: string;
  businessType: string;
  location: string;
  quote: string;
  imagePath: string;
}

export interface SalesData {
  time: string;
  sales: number;
  transactions: number;
}

export interface BestSeller {
  name: string;
  sales: number;
  revenue: number;
}
