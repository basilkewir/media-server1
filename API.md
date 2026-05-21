# MediaServer API Documentation

## Authentication

All API endpoints require authentication via a Bearer token.

### Headers
```
Authorization: Bearer <your-api-token>
```

### Generate Token
```bash
php artisan api:token:generate "My Application"
```

## Response Format

All responses follow a standardized envelope:

**Success:**
```json
{
  "success": true,
  "message": "...",
  "data": { ... },
  "meta": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "message": "...",
  "error_code": "ERROR_CODE",
  "errors": { ... }
}
```

## Endpoints

### Health
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Service health check |

### Channels
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/channels` | List all channels |
| POST | `/api/channels` | Create channel |
| GET | `/api/channels/{channel}` | Get channel |
| PUT | `/api/channels/{channel}` | Update channel |
| DELETE | `/api/channels/{channel}` | Delete channel |
| GET | `/api/channels/{channel}/status` | Channel status |
| GET | `/api/channels/{channel}/events` | Channel events |

### Streams
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/streams/start` | Start stream |
| POST | `/api/streams/stop` | Stop stream |
| POST | `/api/streams/probe` | Probe URL |
| GET | `/api/streams/{channel}/status` | Stream status |
| POST | `/api/streams/{channel}/fallback` | Switch to VOD |
| POST | `/api/streams/{channel}/recover` | Recover to live |
| GET | `/api/streams/{channel}/statistics` | Stream stats |
| GET | `/api/streams/{channel}/recent` | Recent streams |

### Output Targets
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/outputs/status` | Global status |
| POST | `/api/outputs/bulk-push` | Bulk push |
| GET | `/api/channels/{channel}/outputs` | List targets |
| POST | `/api/channels/{channel}/outputs` | Create target |
| GET | `/api/channels/{channel}/outputs/{target}` | Show target |
| PUT | `/api/channels/{channel}/outputs/{target}` | Update target |
| DELETE | `/api/channels/{channel}/outputs/{target}` | Delete target |
| POST | `/api/channels/{channel}/outputs/{target}/start` | Start target |
| POST | `/api/channels/{channel}/outputs/{target}/stop` | Stop target |
| POST | `/api/channels/{channel}/outputs/{target}/restart` | Restart target |
| GET | `/api/channels/{channel}/outputs/{target}/logs` | Target logs |
| POST | `/api/channels/{channel}/outputs/start-all` | Start all |
| POST | `/api/channels/{channel}/outputs/stop-all` | Stop all |
| POST | `/api/channels/{channel}/outputs/push` | Push to URLs |

### Icecast
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/icecast/{channel}/create` | Create stream |
| GET | `/api/icecast/{channel}/url` | Get URL |
| GET | `/api/icecast/{channel}/stats` | Get stats |
| DELETE | `/api/icecast/{channel}/disconnect` | Disconnect |
| PUT | `/api/icecast/{channel}/max-listeners` | Set max listeners |
| POST | `/api/icecast/{channel}/enable` | Enable |
| POST | `/api/icecast/{channel}/disable` | Disable |

### Relay
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/relay/servers` | List servers |
| POST | `/api/relay/servers` | Add server |
| POST | `/api/relay/{channel}/start` | Start relay |
| POST | `/api/relay/broadcast/{relay}/stop` | Stop relay |
| GET | `/api/relay/broadcast/{relay}/status` | Relay status |
| GET | `/api/relay/broadcast/{relay}/logs` | Relay logs |
| GET | `/api/relay/{channel}/broadcasts` | Channel relays |
| POST | `/api/relay/{channel}/enable` | Enable relay |
| POST | `/api/relay/{channel}/disable` | Disable relay |

### Access Codes
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/access-codes/validate` | Validate a code without redeeming |
| POST | `/api/access-codes/redeem` | Redeem an access code |
| GET | `/api/access-codes/status` | Check active subscriptions for current IP |

**Validate Code:**
```bash
curl -X POST /api/access-codes/validate \
  -H "Authorization: Bearer TOKEN" \
  -d '{"code": "ABCD-EFGH-IJKL"}'
```

**Redeem Code:**
```bash
curl -X POST /api/access-codes/redeem \
  -H "Authorization: Bearer TOKEN" \
  -d '{"code": "ABCD-EFGH-IJKL"}'
```

## Admin Panel

### Access Code Generation
Visit `/admin/access-codes/create` in your browser to generate subscription codes.

Supported options:
- **Type:** Library Only, Full Access, Premium
- **Duration:** 1 Month, 3 Months, 6 Months, 1 Year, 2 Years
- **Quantity:** 5, 10, 25, 50, 100 codes per batch
- **Code Length:** 8–32 characters (default 12, formatted as XXXX-XXXX-XXXX)
- **Max Uses:** How many times each code can be redeemed
- **Expires At:** Optional hard expiration date for the codes themselves

## Rate Limits

| Tier | Limit |
|------|-------|
| Unauthenticated | 60 req/min |
| Authenticated | 120 req/min |
| Stream Start | 10 req/min |

## Error Codes

| Code | HTTP | Description |
|------|------|-------------|
| UNAUTHORIZED | 401 | Missing or invalid API token |
| FORBIDDEN | 403 | Insufficient permissions / Access code required |
| NOT_FOUND | 404 | Resource not found |
| VALIDATION_ERROR | 422 | Invalid input data |
| RATE_LIMIT_EXCEEDED | 429 | Too many requests |
| INTERNAL_SERVER_ERROR | 500 | Server error |
| INVALID_ACCESS_CODE | 400 | Invalid, expired, or fully redeemed code |
