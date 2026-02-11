# Customer Management API (Laravel)

API base URL: `/api` (e.g. `https://your-domain.com/api`).

**Swagger UI**: `/api/documentation` — interactive docs; use **Authorize** and paste your JWT.

Authentication: **JWT** (tymon/jwt-auth). Send header: `Authorization: Bearer {access_token}`. Get token from `POST /auth/login` or `POST /auth/register`; refresh with `POST /auth/refresh`.

---

## 1. Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/login` | No | Login. Body: `email`, `password`. Returns `user`, `token`. |
| POST | `/auth/register` | No | Register. Body: `name`, `email`, `password`, `password_confirmation`. |
| POST | `/auth/logout` | Yes | Invalidate current JWT (blacklist). |
| POST | `/auth/refresh` | Yes | Return new JWT (send current token in Authorization). |
| GET | `/auth/me` | Yes | Current user + agents. |

---

## 2. Agents

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/agents` | Yes | List agents (admin: all active; user: accessible). |
| GET | `/agents/user-agents` | Yes | List agents for current user (for dropdown). |
| GET | `/agents/{id}` | Yes | Show agent (must have access). |
| POST | `/agents` | Admin | Create agent. Body: `name`, `allowed_users` (comma-separated emails). |
| PUT | `/agents/{id}` | Admin | Update agent. Body: `status`, `allowed_users`. |
| DELETE | `/agents/{id}` | Admin | Set agent status to Deleted (cannot delete default "Khách hàng"). |

---

## 3. Transactions

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/transactions` | Yes | List transactions. Query: `agent_id`, `status`, `date_from`, `date_to`, `transaction_type`, `per_page`. |
| POST | `/transactions` | Yes | Create transaction. Body: `agent_id`, `customer_name`, `cccd_number`, `total_amount`, `transaction_type`, `pos_fee_percent`, `agent_fee_percent`, `agent_advance`. |
| GET | `/transactions/{id}` | Yes | Show transaction. |
| PUT | `/transactions/{id}/status` | Yes | Update status. Body: `status` (one of: Chờ duyệt, Đã duyệt, Đang xử lý, Chờ DR, Hoàn thành, Thất bại, Đã hủy). |
| GET | `/transactions/export` | Yes | Export data. Query: `agent_id`, `date_from`, `date_to` (dd/mm/yyyy), `status`, `type`. Returns JSON with `transactions` and `summary`. |

---

## 4. Daily Advances & EOD Settlement

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/daily-advances` | Yes | Summary. Query: `agent_id`, `date` (dd/mm/yyyy). |
| POST | `/daily-advances/settle` | Yes | Mark advances as settled. Body: `agent_id`, `date`. |
| GET | `/settlements/eod` | Yes | EOD data (only completed transactions). Query: `agent_id`, `date`. |
| POST | `/settlements/eod` | Yes | Save EOD and settle advances. Body: `agent_id`, `date`, `settlement_data`. |
| GET | `/settlements/history` | Yes | EOD history. Query: `agent_id`, `per_page`. |

---

## 5. Statistics

**Only "Hoàn thành" (completed) transactions are counted** (same as GAS v5.0.0).

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/statistics` | Yes | Query: `agent_id`, `period` (all, today, 3days, week, month). Returns totals, dao_count, rut_count, recent, by_status. |
| GET | `/statistics/summary` | Yes | Summary per accessible agent. |

---

## 6. MoMo QR

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/momo/generate-qr` | Yes | Body: `customer_name`, `total_amount`, `transaction_type` (Đáo/Rút). Returns `qrCodeUrl`, `payUrl`, `deeplink`. |
| POST | `/momo/webhook` | No | IPN callback from MoMo (configure in MoMo dashboard). |

---

## 7. CCCD Scan (Gemini AI)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/cccd/scan` | Yes | Body: `image` (base64 string). Returns `cccdNumber`, `fullName`, `confidence`. |

---

## 8. Config

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/config` | Yes | Returns version, default_pos_fee, default_agent_fee, currency, timezone, status_values, amount_suggestions. |

---

## 9. Queue

Jobs are processed automatically by running `php artisan queue:work`. Use the API to add jobs.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/queue/dispatch` | Yes | Add job. Body: `type` (string), `payload` (object), `queue` (optional). Types: `cccd_scan`, `export_transactions`, `sync_to_staging`. |
| GET | `/queue/status` | Yes | Queue status (pending count for database driver). |

---

## Env / Config

- `ADMIN_EMAIL`: Admin user email (default from config).
- `GEMINI_API_KEY`: For CCCD scan (Gemini 2.0 Flash).
- `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY`, `MOMO_REDIRECT_URL`, `MOMO_IPN_URL`: For MoMo.
- `QUEUE_CONNECTION=database`: Then run `php artisan queue:work` to process jobs. Run `php artisan migrate` to create `jobs` table.
- `DB_STAGING_*`: Staging DB for `db:clone-to-staging` (scheduled every 30 mins via `php artisan schedule:work`).

Run migrations and seed: `php artisan migrate --seed` (creates admin, test user, default agent "Khách hàng").
