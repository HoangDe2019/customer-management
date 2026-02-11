# Customer Management – React Frontend

React (Vite + TypeScript) frontend that uses all Customer Management Laravel APIs.

## Setup

1. Install dependencies:
   ```bash
   npm install
   ```

2. Configure API URL. Copy `.env.example` to `.env` and set your Laravel API base URL:
   ```
   VITE_API_URL=http://localhost:8000/api
   ```
   If the API is on the same host as the frontend, you can use a relative path: `VITE_API_URL=/api`.

3. Start the Laravel API (from project root):
   ```bash
   php artisan serve
   ```

4. Start the dev server:
   ```bash
   npm run dev
   ```
   Open the URL shown (e.g. http://localhost:5173).

## Build

```bash
npm run build
```
Output is in `dist/`. You can serve it with any static host or point Laravel to `dist` for production.

## Features (API coverage)

- **Auth**: Login, Register, Logout, Me (JWT)
- **Agents**: List, Create (admin), Update (admin), Delete (admin), Show, User agents dropdown
- **Transactions**: List (filters, pagination), Create, Detail, Update status, Export (date range, JSON download)
- **Daily advances**: Get by agent + date, Settle
- **Settlements**: EOD get/save, History (paginated)
- **Statistics**: By agent + period (all/today/3days/week/month), Summary for dashboard
- **Config**: Version, fees, currency, status values, amount suggestions
- **MoMo**: Generate QR (customer_name, amount, type)
- **CCCD**: Scan image (base64) via Gemini API

All requests use JWT from login/register; 401 redirects to `/login`.
