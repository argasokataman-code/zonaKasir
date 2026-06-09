<div align="center">

  <h1>zonaKasir</h1>

  <p>zonaKasir is a Point of Sale (POS) web application built with Laravel and Filament Admin Panel.</p>
  
</div>

## Requirements
* php 8.4
* mysql 5.7 or higher
* php-ext.* base on laravel extenstion requirement


## Features
- **Role Management**: Define roles and permissions for users.
- **Transaction Management**: Handle sales transactions seamlessly.
- **Product Management**: Manage your inventory and products effectively.
- **Unit Price**: The product will have a different price base on the basic unit.
- **Discount**: You can sell the product with a discount per item or global discount.
- **Purchasing**: Manage purchase orders and supplier relationships.
- **Stock Opname**: Conduct stock taking and inventory audits to ensure accuracy.
- **Receivable Management**: Track and manage receivables owed by and to your business.
- **Payment Method Management**: Define and manage various payment methods.
- **Voucher Management**: Create, distribute, and track the usage of vouchers.
- **Reporting**: Generate reports for insights into sales and performance.
- **Simple Accounting**: Basic accounting features to track income, expenses, and profits.
- **Real-time Dashboard**: Monitor business metrics and performance in real-time.
- **Web usb direct printing**: support the thermal printer using usb feature from browser (Chrome, Firefox)
- **Barcode support**: we can use the barcode on stock opname, purchasing, and POS feature

## Screenshots

<div align="center">
  <img src="./public/assets/images/dashboard.png" alt="Dashboard" width="400" />
  &emsp;
  <img src="./public/assets/images/cashier-transaction.png" alt="Cashier" width="400" />
  <br/>
  <img src="./public/assets/images/product-management.png" alt="Product Management" width="400" />
  &emsp;
  <img src="./public/assets/images/member.png" alt="Member" width="400" />
  <br/>
  <img src="./public/assets/images/dashboard-transaction.png" alt="Transaction" width="400" />
  &emsp;
  <img src="./public/assets/images/stock-management.png" alt="Stock Management" width="400" />
  <br/>
  <img src="./public/assets/images/supplier-management.png" alt="Supplier" width="400" />
  &emsp;
  <img src="./public/assets/images/payment-methods.png" alt="Payment Methods" width="400" />
  <br/>
  <img src="./public/assets/images/category-management.png" alt="Categories" width="400" />
  &emsp;
  <img src="./public/assets/images/cashier-report.png" alt="Cashier Report" width="400" />
</div>

## Technologies Used
* **Backend**: [Laravel](https://laravel.com)
* **Frontend**: [Filament Admin Panel](https://filamentphp.com)

## Installation
1. Clone the repository: `git clone https://github.com/argasokataman-code/zonaKasir.git`
2. Navigate to the project directory: `cd zonaKasir`
3. Install dependencies:
4. Laravel:
   * `cp .env.example .env`
   * edit the env based on your local configuration
   * `composer install`
   * `php artisan key:generate`
   * `php artisan migrate --path=database/migrations/tenant --seed`
   * `php artisan filament:assets`
   * `php artisan livewire:publish --assets`
5. Javascript:
  * `npm install`
  * `npm run build or npm run dev`
6. Create the user using `php artisan app:create-user`

## Usage
* api: localdomain.test/api/test
* webapp: localdomain.test/member/login

## Contributing

We welcome contributions from the community! If you'd like to contribute to zonaKasir, please follow these steps:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature/new-feature`).
3. Make your changes and commit them (`git commit -am 'Add new feature'`).
4. Push to the branch (`git push origin feature/new-feature`).
5. Create a new Pull Request.
   
## License
This project is licensed under the GPL-3.0 license.

## Contact
For any inquiries or support, please contact zonakasirapp@gmail.com or you can open discussion in discussion features

