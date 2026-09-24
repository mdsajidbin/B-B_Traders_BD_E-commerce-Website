# B&B TRADERS BD — Premium E-commerce Website (PHP + MySQL)

## What's included
- Full storefront: home, shop (search/filter/sort/pagination), product detail (gallery, variants, reviews), cart, checkout, order tracking
- Customer account area: dashboard, orders, order detail with review submission, wishlist, profile/password
- Admin panel: dashboard, products (add/edit/delete + variants + image upload), categories, orders (status management), customers, coupons, reviews moderation, settings
- `database.sql` — your original schema + seed data (16 tables)

## Setup (XAMPP / local PHP)
1. Copy the `bbtraders` folder into your server root (e.g. `htdocs/bbtraders`).
2. Create a database named **bb_traders_bd** and import `database.sql` (phpMyAdmin → Import, or `mysql -u root bb_traders_bd < database.sql`).
3. Open `config/db.php` and set your DB credentials if they differ from `root` / (empty password).
4. Visit `http://localhost/bbtraders/` in your browser.
5. Log in as admin at `http://localhost/bbtraders/admin/` using an account from the `users` table that has `role = 'admin'`. If none exists, create one via phpMyAdmin (password must be a `password_hash()` value — or register a normal account then update its `role` to `admin` directly in the database).

## Tested
- Every PHP file passes `php -l` with no syntax errors.
- Ran on PHP's built-in server against a live MySQL import of your schema — homepage, shop, product, cart, login, and admin all return 200 and render real data from the database.

## Notes / next steps
- Payment gateways (Stripe/bKash/Nagad) are wired as architecture only (settings fields + `pending` payment records) — plug in real API calls in `checkout.php` when you're ready to go live.
- Product images: use the "Main Image URL" field or upload a file in the admin product form; uploads are stored in `assets/uploads/products/`.
- `.htaccess` includes optional pretty URLs and basic hardening — requires `mod_rewrite` enabled.
- This build covers the core end-to-end flow of your spec; if you want additional pieces from your original 100-section brief (e.g. sales/reports dashboard, digital-download delivery, multi-address book, abandoned-cart emails), tell me which ones and I'll add them next.
