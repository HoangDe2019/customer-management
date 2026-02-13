# customer-management

## Chạy nhanh (một script)

```powershell
.\scripts\run-all.ps1
```

Script sẽ: cài đặt dependency (Composer + npm), chạy migration, build frontend, rồi khởi động **API** (http://localhost:8000), **Queue worker**, **Reverb (WebSocket)** và **Frontend** (http://localhost:5173). Dừng bằng Ctrl+C.

Hoặc chỉ chạy services (đã deploy trước đó):

```powershell
.\scripts\run.ps1
```

Deploy trước rồi chạy:

```powershell
.\scripts\run.ps1 -DeployFirst
```

## WebSocket (Reverb)

Khi có dữ liệu thay đổi (agents, transactions, settlements), server broadcast qua channel `data-updates`. Frontend subscribe và tự động load lại dữ liệu, không cần refresh trang. Job hoàn thành broadcast qua `job-updates`. Cần bật Reverb: trong `.env` đặt `BROADCAST_CONNECTION=reverb` và chạy `php artisan reverb:start` (đã gộp trong `run.ps1` / `run-all.ps1`).

## GraphQL (API chính)

Toàn bộ API đã chuyển sang **GraphQL** (POST /graphql, JWT giống REST). Frontend gọi qua `graphqlApi`; **login/register** vẫn dùng REST để lấy token.

### Request → Notification

Mutations không trả dữ liệu trực tiếp trong response mà trả **RequestAck** (`accepted`, `request_id`, `message`). Server xử lý xong sẽ **broadcast** qua WebSocket channel `request-results` với event `request.completed` (payload: `request_id`, `success`, `entity`, `action`, `data` hoặc `error`). Frontend sau khi gửi mutation sẽ chờ notification trùng `request_id` rồi cập nhật UI (hoặc dùng `data` trong notification). Cần bật Reverb và subscribe channel `request-results` để nhận kết quả.

### Schema

- **Queries**: `me`, `config`, `agents`, `agent(id)`, `userAgents`, `transactions`, `transaction(id)`, `statistics`, `summary`, `dailyAdvances`, `eodSettlement`, `settlementHistory`, `queueStatus`, `cloneStatus`, `momoCheckStatus(orderId)`.
- **Mutations** (trả RequestAck, kết quả qua notification): `logout`, `createAgent`, `updateAgent`, `deleteAgent`, `createTransaction`, `updateTransactionStatus`, `settleDailyAdvances`, `saveEodSettlement`, `generateMoMoQR`, `scanCCCD`, `queueDispatch`, `triggerClone`.
- **Types**: `User`, `Agent`, `Transaction`, `RequestAck`, `Config`, `Statistics`, v.v. Xem thêm trong `app/GraphQL/`.